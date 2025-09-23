<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Content extractor class for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/lib/modinfolib.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->libdir . '/weblib.php');
require_once($CFG->dirroot.'/blocks/tutoring_machine/lib.php');

/**
 * Class to extract content from course resources for use as context
 * 
 * This class extracts content from various Moodle resource types such as text pages,
 * glossaries, PDFs, forums, etc. and provides it as a structured context for the AI.
 * It includes a robust caching system to improve performance.
 */
class block_tutoring_machine_content_extractor {
    /** @var object $course The course object */
    private $course;

    /** @var array $config Configuration settings */
    private $config;

    /** @var string $context Extracted context content */
    private $context = '';

    /** @var object $modinfo Course module information */
    private $modinfo;

    /** @var array $files_for_upload Collected files to pass to external APIs */
    private $files_for_upload = [];

    /** @var bool $file_upload_mode Flag to indicate that raw files should be provided instead of extracted text */
    private $file_upload_mode = false;

    /**
     * Normalize HTML or text to plain text with stable line breaks.
     *
     * @param string $text Raw HTML/text
     * @return string Normalized text
     */
    private function normalise_text($text) {
        if ($text === null || $text === '') {
            return '';
        }

        $plain = html_to_text($text, 0, false);
        $plain = preg_replace("/\r\n|\r/", "\n", $plain);
        $plain = preg_replace("/\n{3,}/", "\n\n", $plain);

        return trim($plain);
    }

    /**
     * Clamp larger pieces of text to a sensible limit to avoid exceeding API context sizes.
     *
     * @param string $text
     * @param int $limit
     * @return string
     */
    private function clamp_text($text, $limit = 12000) {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (core_text::strlen($text, 'UTF-8') > $limit) {
            $text = core_text::substr($text, 0, $limit, 'UTF-8') . "\n\n…";
        }

        return $text;
    }

    // MIME types for Office documents
    const MIMETYPE_DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    const MIMETYPE_DOC = 'application/msword';
    const MIMETYPE_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const MIMETYPE_XLS = 'application/vnd.ms-excel';
    const MIMETYPE_PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    const MIMETYPE_PPT = 'application/vnd.ms-powerpoint';

    /**
     * Constructor
     *
     * @param int $courseid The course ID
     * @param array|object $config Configuration settings
     */
    public function __construct($courseid, $config = null) {
        global $DB;

        // Get course information
        $this->course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        if (!$this->course) {
            throw new moodle_exception('invalidcourseid', 'error', '', $courseid);
        }

        $globalincludecontext = get_config('block_tutoring_machine', 'default_include_context');
        $includecontextdefault = !empty($globalincludecontext);

        // Set default configuration
        $this->config = [
            'include_context' => $includecontextdefault,
            'use_textpages' => true,
            'use_glossaries' => true,
            'use_internet' => false,
            'use_h5p' => true,
            'use_pdfs' => true,
            'use_office' => true, // New option for Office documents
            'use_forums' => true,
            'use_quizzes' => true,
            'use_books' => true,
            'use_assignments' => true,
            'use_labels' => true,
            'use_urls' => true,
            'use_lessons' => true,
            'use_specific_activities' => false,
            'specific_activities' => [],
            'context_links_enable' => false,
            'blockid' => 0
        ];

        // Override with provided configuration
        if ($config) {
            $config_array = (array)$config;
            $boolkeys = [
                'include_context', 'use_textpages', 'use_glossaries', 'use_internet', 'use_h5p',
                'use_pdfs', 'use_office', 'use_forums', 'use_quizzes', 'use_books', 'use_assignments',
                'use_labels', 'use_urls', 'use_lessons', 'use_specific_activities', 'context_links_enable'
            ];
           
            // Process main settings
            foreach ($config_array as $key => $value) {
                $setting_key = preg_replace('/^config_/', '', $key);
                if (array_key_exists($setting_key, $this->config)) {
                    if (in_array($setting_key, $boolkeys, true)) {
                        $this->config[$setting_key] = (bool)$value;
                    } else if ($setting_key === 'blockid') {
                        $this->config[$setting_key] = (int)$value;
                    }
                }
                
                // Erfasse ausgewählte spezifische Aktivitäten
                if (preg_match('/^activity_(\d+)$/', $key, $matches) && (bool)$value) {
                    $cm_id = intval($matches[1]);
                    $this->config['specific_activities'][] = $cm_id;
                }
            }
            
            // Wenn selektive Aktivitätsauswahl aktiviert ist, füge einen Timestamp hinzu,
            // um den Cache zu umgehen (da diese Funktion neu ist)
            if (!empty($this->config['use_specific_activities'])) {
                $this->config['cache_timestamp'] = time();
            }

            if (isset($config_array['blockid'])) {
                $this->config['blockid'] = (int)$config_array['blockid'];
            } elseif (isset($config_array['instanceid'])) {
                $this->config['blockid'] = (int)$config_array['instanceid'];
            }
        }

        // Get module information
        $this->modinfo = get_fast_modinfo($this->course);
    }

    /**
     * Enable file upload mode so that original files are collected for external processing.
     */
    public function enable_file_upload_mode() {
        $this->file_upload_mode = true;
    }

    /**
     * Get the list of files that should be uploaded to external services.
     *
     * @return array List of arrays with keys: file (stored_file), label, type, filename, mimetype
     */
    public function get_files_for_upload() {
        return array_values($this->files_for_upload);
    }

    /**
     * Register a file so that it can be uploaded to an external AI service.
     *
     * @param stored_file $file Moodle stored file object
     * @param string $label Human readable label (e.g. activity name)
     * @param string $type Logical type (pdf, word, etc.)
     */
    private function register_file_for_upload($file, $label, $type, $extracted_text = '') {
        if (!$file instanceof stored_file) {
            return;
        }

        $hash = $file->get_contenthash();
        if (isset($this->files_for_upload[$hash])) {
            return;
        }

        $this->files_for_upload[$hash] = [
            'file' => $file,
            'label' => $label,
            'type' => $type,
            'filename' => $file->get_filename(),
            'mimetype' => $file->get_mimetype(),
            'courseid' => $this->course->id,
            'extracted_text' => $extracted_text
        ];
    }

    /**
     * Get context from all enabled sources with caching
     *
     * @return string The formatted contex
     */
    public function get_context() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        // Reset collected files at the beginning of each extraction run
        $this->files_for_upload = [];

        if (empty($this->config['include_context'])) {
            return '';
        }

        // Cache-Key immer generieren, unabhängig davon, ob wir ihn jetzt verwenden
        $cache_key = block_tutoring_machine_cache_manager::get_course_content_key($this->course->id, $this->config);
        
        // Überprüfe, ob ein Cache-Buster in der URL ist
        $skip_cache = isset($_GET['cache_buster']);

        // Wenn wir Dateien direkt bereitstellen möchten, arbeiten wir immer ohne Cache,
        // damit wir die aktuelle Dateiliste erhalten und keine alten Daten verwenden.
        if ($this->file_upload_mode) {
            $skip_cache = true;
        }

        if (!$skip_cache) {
            // Try to get from cache
            $cached_context = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_COURSE, $cache_key);
    
            // Return cached context if it's valid
            if ($cached_context && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_COURSE, $cache_key)) {
                // Check if we need to update the information sources instructions
                if ((strpos($cached_context, '# Internetsuche') !== false && !$this->config['use_internet']) ||
                    (strpos($cached_context, '# WICHTIG: Strikte Informationsquellen') !== false && $this->config['use_internet'])) {
                    error_log("Cache contains outdated internet search settings - regenerating");
                    // Internet setting has changed, regenerate context
                } else {
                    // Wenn selektive Aktivitätsauswahl aktiviert ist, aber der Cache stammt aus einer Zeit,
                    // als dies noch nicht implementiert war, dann Cache ignorieren
                    if (empty($this->config['use_specific_activities']) || strpos($cached_context, 'Aktivitätsauswahl') !== false) {
                        return $cached_context;
                    } else {
                        error_log("Selektive Aktivitätsauswahl aktiviert, aber Cache enthält veraltete Daten - generiere neu");
                    }
                }
            }
        } else {
            error_log("Cache-Buster in URL gefunden - Cache wird umgangen");
        }

        // If not cached or expired, generate the context
        $sections = [];

        // Process text pages
        if ($this->config['use_textpages']) {
            $pages_context = $this->extract_text_pages();
            if (!empty($pages_context)) {
                $sections['textpages'] = [
                    'title' => get_string('use_textpages', 'block_tutoring_machine'),
                    'content' => $pages_context
                ];
            }
        }

        // Process glossaries
        if ($this->config['use_glossaries']) {
            $glossaries_context = $this->extract_glossaries();
            if (!empty($glossaries_context)) {
                $sections['glossaries'] = [
                    'title' => get_string('use_glossaries', 'block_tutoring_machine'),
                    'content' => $glossaries_context
                ];
            }
        }

        // Process H5P activities
        if ($this->config['use_h5p']) {
            $h5p_context = $this->extract_h5p_activities();
            if (!empty($h5p_context)) {
                $sections['h5p'] = [
                    'title' => get_string('use_h5p', 'block_tutoring_machine'),
                    'content' => $h5p_context
                ];
            }
        }

        // Process PDF documents
        if ($this->config['use_pdfs']) {
            $pdf_context = $this->extract_pdf_documents();
            if (!empty($pdf_context)) {
                $sections['pdfs'] = [
                    'title' => get_string('use_pdfs', 'block_tutoring_machine'),
                    'content' => $pdf_context
                ];
            }
        }
        
        // Process Office documents (Word, Excel, PowerPoint)
        if ($this->config['use_office']) {
            $office_context = $this->extract_office_documents();
            if (!empty($office_context)) {
                $sections['office'] = [
                    'title' => get_string('use_office', 'block_tutoring_machine'),
                    'content' => $office_context
                ];
            }
        }

        // Process forum discussions
        if ($this->config['use_forums']) {
            $forums_context = $this->extract_forum_discussions();
            if (!empty($forums_context)) {
                $sections['forums'] = [
                    'title' => get_string('use_forums', 'block_tutoring_machine'),
                    'content' => $forums_context
                ];
            }
        }

        // Process quizzes
        if ($this->config['use_quizzes']) {
            $quizzes_context = $this->extract_quiz_questions();
            if (!empty($quizzes_context)) {
                $sections['quizzes'] = [
                    'title' => get_string('use_quizzes', 'block_tutoring_machine'),
                    'content' => $quizzes_context
                ];
            }
        }

        // Process books
        if ($this->config['use_books']) {
            $books_context = $this->extract_book_chapters();
            if (!empty($books_context)) {
                $sections['books'] = [
                    'title' => get_string('use_books', 'block_tutoring_machine'),
                    'content' => $books_context
                ];
            }
        }

        // Process assignments
        if ($this->config['use_assignments']) {
            $assignments_context = $this->extract_assignments();
            if (!empty($assignments_context)) {
                $sections['assignments'] = [
                    'title' => get_string('use_assignments', 'block_tutoring_machine'),
                    'content' => $assignments_context
                ];
            }
        }

        // Process labels
        if ($this->config['use_labels']) {
            $labels_context = $this->extract_labels();
            if (!empty($labels_context)) {
                $sections['labels'] = [
                    'title' => get_string('use_labels', 'block_tutoring_machine'),
                    'content' => $labels_context
                ];
            }
        }

        // Process URLs
        if ($this->config['use_urls']) {
            $urls_context = $this->extract_urls();
            if (!empty($urls_context)) {
                $sections['urls'] = [
                    'title' => get_string('use_urls', 'block_tutoring_machine'),
                    'content' => $urls_context
                ];
            }
        }

        // Process lessons
        if ($this->config['use_lessons']) {
            $lessons_context = $this->extract_lessons();
            if (!empty($lessons_context)) {
                $sections['lessons'] = [
                    'title' => get_string('use_lessons', 'block_tutoring_machine'),
                    'content' => $lessons_context
                ];
            }
        }

        if (!empty($this->config['context_links_enable'])) {
            $links_context = $this->extract_context_links();
            if (!empty($links_context)) {
                $sections['links'] = [
                    'title' => get_string('context_links', 'block_tutoring_machine'),
                    'content' => $links_context
                ];
            }
        }

        // Combine all sections into a structured context
        $context = '';

        if (!empty($sections)) {
            $context = "# Kursinhalte als Kontext\n\n";
            
            // Wenn Aktivitätsauswahl aktiviert ist, einen Hinweis hinzufügen
            if (!empty($this->config['use_specific_activities'])) {
                $context .= "## Hinweis zur Aktivitätsauswahl\n";
                $context .= "Der folgende Kontext enthält nur ausgewählte Aktivitäten. " .
                           "Andere Aktivitäten im Kurs wurden bewusst ausgeschlossen.\n\n";
            }

            foreach ($sections as $section) {
                $context .= "## {$section['title']}\n";
                $context .= $section['content'] . "\n\n";
            }
        }

        // Add instructions about information sources
        if (!empty($context)) {
            $context .= "\n";
        }

        if ($this->config['use_internet']) {
            // Internet search enabled
            $context .= "# Internetsuche\n";
            $context .= "Wenn du keine passende Antwort in den Kursinhalten findest, darfst du auf dein allgemeines Wissen zurückgreifen oder Informationen aus dem Internet verwenden, um die Frage zu beantworten. Bitte kennzeichne in diesem Fall in deiner Antwort, dass die Information nicht aus dem Kursinhalt stammt, sondern aus externen Quellen.\n\n";
        } else {
            // Internet search disabled - but worded more carefully
            $context .= "# WICHTIG: Strikte Informationsquellen\n";
            $context .= "Beantworte Fragen basierend auf den gegebenen Kursinhalten. Du kannst Informationen, die in den Kursinhalten explizit enthalten sind, flexibel und hilfreich erklären, umformulieren und zusammenfassen.\n\n";
            $context .= "Wenn du eine Frage bekommst, die mit den vorhandenen Kursinhalten beantwortet werden kann, gib eine möglichst hilfreiche Antwort. Nur wenn die Frage überhaupt nicht mit den vorhandenen Informationen beantwortet werden kann, antworte: \"Entschuldigung, aber zu diesem Thema finde ich keine Informationen in den Kursmaterialien. Bitte wende dich an deinen Kursleiter oder stelle eine andere Frage.\"\n\n";
        }

        if ($context !== '' && core_text::strlen($context, 'UTF-8') > 60000) {
            $context = core_text::substr($context, 0, 60000, 'UTF-8') . "\n\n…";
        }

        // Cache the generated contex
        block_tutoring_machine_cache_manager::set(block_tutoring_machine_cache_manager::CACHE_TYPE_COURSE, $cache_key, $context);

        return $context;
    }

    /**
     * Prüft, ob eine Aktivität in den Kontext einbezogen werden soll
     * 
     * @param object $cm Course module instance
     * @return bool True, wenn die Aktivität einbezogen werden soll
     */
    private function should_include_activity($cm) {
        if (empty($this->config['include_context'])) {
            return false;
        }

        // Prüfe, ob selektive Aktivitätsauswahl aktiviert ist
        if (!empty($this->config['use_specific_activities'])) {
            // Wenn selektive Auswahl aktiv ist, nur ausgewählte Aktivitäten einbeziehen
            return in_array($cm->id, $this->config['specific_activities']);
        }
        
        // Sonst nach globalen Einstellungen prüfen - je nach Modultyp
        $modname = $cm->modname;
        
        switch ($modname) {
            case 'page':
                return !empty($this->config['use_textpages']);
            case 'glossary':
                return !empty($this->config['use_glossaries']);
            case 'hvp':
                return !empty($this->config['use_h5p']);
            case 'resource': // Für PDF-Dokumente und Office-Dokumente
                // Note: We'll check specific MIME types in the extraction methods
                return !empty($this->config['use_pdfs']) || !empty($this->config['use_office']);
            case 'forum':
                return !empty($this->config['use_forums']);
            case 'quiz':
                return !empty($this->config['use_quizzes']);
            case 'book':
                return !empty($this->config['use_books']);
            case 'assign':
                return !empty($this->config['use_assignments']);
            case 'label':
                return !empty($this->config['use_labels']);
            case 'url':
                return !empty($this->config['use_urls']);
            case 'lesson':
                return !empty($this->config['use_lessons']);
            default:
                // Bei unbekannten Typen standardmäßig nicht einbeziehen
                return false;
        }
    }
    
    /**
     * Extract content from text pages with caching
     *
     * @return string Formatted content from text pages
     */
    private function extract_text_pages() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $pages_context = '';

        foreach ($this->modinfo->get_instances_of('page') as $page_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$page_instance->uservisible || !$this->should_include_activity($page_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_page_content_key($page_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_PAGE, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_PAGE, $cache_key)) {
                $pages_context .= $cached_content;
                continue;
            }

            // Get page record
            $page = $DB->get_record('page', ['id' => $page_instance->instance], '*', MUST_EXIST);
            if ($page) {
                $page_content = "### Textseite: {$page->name}\n";

                $intro = $this->normalise_text($page->intro ?? '');
                if ($intro !== '') {
                    $page_content .= "Beschreibung: {$intro}\n\n";
                }

                $main = $this->normalise_text($page->content ?? '');
                if ($main !== '') {
                    $page_content .= $main . "\n\n";
                } else {
                    $page_content .= "\n";
                }

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_PAGE,
                    block_tutoring_machine_cache_manager::get_page_content_key($page->id),
                    $page_content,
                    ['timemodified' => $page->timemodified]
                );

                // Add to outpu
                $pages_context .= $page_content;
            }
        }

        return $pages_context;
    }

    /**
     * Extract content from glossaries with caching
     *
     * @return string Formatted content from glossaries
     */
    private function extract_glossaries() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $glossaries_context = '';

        foreach ($this->modinfo->get_instances_of('glossary') as $glossary_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$glossary_instance->uservisible || !$this->should_include_activity($glossary_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_glossary_content_key($glossary_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_GLOSSARY, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_GLOSSARY, $cache_key)) {
                $glossaries_context .= $cached_content;
                continue;
            }

            // Get glossary record
            $glossary = $DB->get_record('glossary', ['id' => $glossary_instance->instance], '*', MUST_EXIST);
            if ($glossary) {
                $glossary_content = "### Glossar: {$glossary->name}\n";

                $intro = $this->normalise_text($glossary->intro ?? '');
                if ($intro !== '') {
                    $glossary_content .= "Einführung: {$intro}\n\n";
                }

                // Get approved entries
                $entries = $DB->get_records('glossary_entries', ['glossaryid' => $glossary->id, 'approved' => 1]);

                foreach ($entries as $entry) {
                    $glossary_content .= "- Begriff: {$entry->concept}\n";
                    $glossary_content .= "  Definition: " . strip_tags($entry->definition) . "\n";
                }

                $glossary_content .= "\n";

                // Store in cache with timemodified and entry count as metadata
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_GLOSSARY,
                    block_tutoring_machine_cache_manager::get_glossary_content_key($glossary->id),
                    $glossary_content,
                    [
                        'timemodified' => $glossary->timemodified,
                        'entry_count' => count($entries)
                    ]
                );

                // Add to outpu
                $glossaries_context .= $glossary_content;
            }
        }

        return $glossaries_context;
    }

    /**
     * Extract content from H5P activities with caching
     *
     * @return string Formatted content from H5P activities
     */
    private function extract_h5p_activities() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $h5p_context = '';

        // Check if H5P is available in this Moodle installation
        // First check if the module exists at all before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('hvp', array_keys($modules))) {
                // H5P module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $h5p_instances = $this->modinfo->get_instances_of('hvp');
            if (empty($h5p_instances)) {
                // No H5P activities in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking H5P availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('hvp') as $h5p_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$h5p_instance->uservisible || !$this->should_include_activity($h5p_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_h5p_content_key($h5p_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_H5P, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_H5P, $cache_key)) {
                $h5p_context .= $cached_content;
                continue;
            }

            // Get H5P activity record
            $h5p = $DB->get_record('hvp', ['id' => $h5p_instance->instance], '*', MUST_EXIST);
            if ($h5p) {
                // Start building H5P conten
                $h5p_content = "### H5P-Aktivität: {$h5p->name}\n";

                // Extract content from H5P
                if (!empty($h5p->json_content)) {
                    try {
                        $content = json_decode($h5p->json_content, true);
                        if (isset($content['questions'])) {
                            foreach ($content['questions'] as $question) {
                                if (isset($question['params']['question'])) {
                                    $h5p_content .= "- Frage: " . strip_tags($question['params']['question']) . "\n";

                                    // Add answers if available
                                    if (isset($question['params']['answers'])) {
                                        foreach ($question['params']['answers'] as $answer) {
                                            $h5p_content .= "  - Antwort: " . strip_tags($answer['text']) . "\n";
                                        }
                                    }
                                }
                            }
                        } elseif (isset($content['text'])) {
                            // Text conten
                            $h5p_content .= "- Inhalt: " . strip_tags($content['text']) . "\n";
                        } else {
                            // Try to extract useful text from the conten
                            $content_string = json_encode($content);
                            $h5p_content .= "- Inhalt: " . substr(strip_tags($content_string), 0, 500) . "\n";
                        }
                    } catch (Exception $e) {
                        error_log("Error parsing H5P content: " . $e->getMessage());
                        // Try to use raw conten
                        $h5p_content .= "- Inhalt: " . substr(strip_tags($h5p->json_content), 0, 500) . "\n";
                    }
                }

                // Add description if available
                if (!empty($h5p->intro)) {
                    $h5p_content .= "- Beschreibung: " . strip_tags($h5p->intro) . "\n";
                }

                $h5p_content .= "\n";

                // Store in cache with timemodified as metadata
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_H5P,
                    block_tutoring_machine_cache_manager::get_h5p_content_key($h5p->id),
                    $h5p_content,
                    [
                        'timemodified' => $h5p->timemodified
                    ]
                );

                // Add to outpu
                $h5p_context .= $h5p_content;
            }
        }

        return $h5p_context;
    }

    /**
     * Extract content from PDF documents
     *
     * @return string Formatted content from PDF documents
     */
    private function extract_pdf_documents() {
        global $DB;

        $pdf_context = '';

        // Get course modules of type 'resource'
        $resources = $this->modinfo->get_instances_of('resource');

        // Loop through each resource
        foreach ($resources as $resource_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$resource_instance->uservisible || !$this->should_include_activity($resource_instance)) {
                continue;
            }

            // Get resource record
            $resource = $DB->get_record('resource', ['id' => $resource_instance->instance], '*', MUST_EXIST);
            if (!$resource) {
                continue;
            }

            // Sicherstellen, dass cmid korrekt gesetzt ist für die Kontextermittlung
            $resource->cmid = $resource_instance->id;

            // Check if it's a PDF
            $file = $this->extract_resource_file($resource);
            if (!$file || $file->get_mimetype() !== 'application/pdf') {
                continue;
            }

            $description = $this->normalise_text($resource->intro ?? '');
            $pdf_content = $this->clamp_text($this->extract_pdf_content($file), 12000);

            if ($description === '' && empty($pdf_content)) {
                continue;
            }

            $section = "### PDF-Dokument: {$resource->name}\n";
            if ($description !== '') {
                $section .= "Beschreibung: {$description}\n\n";
            }

            if (!empty($pdf_content)) {
                $section .= $pdf_content . "\n\n";

                if ($this->file_upload_mode) {
                    $this->register_file_for_upload($file, $resource->name, 'pdf', $pdf_content);
                }
            } else {
                $section .= "\n";
            }

            $pdf_context .= $section;
        }

        return $pdf_context;
    }

    /**
     * Extract file record from a resource
     *
     * @param object $resource The resource record
     * @return stored_file|false The file record or false if not found
     */
    private function extract_resource_file($resource) {
        // Get file storage
        $fs = get_file_storage();

        // Find the main file (resource)
        $context = context_module::instance($resource->cmid);
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (empty($files)) {
            return false;
        }

        // Return the first file (there should be only one for a resource)
        return reset($files);
    }

    /**
     * Extract text content from a PDF file using server-side methods
     *
     * @param stored_file $file The PDF file stored in Moodle
     * @return string The extracted text from PDF
     */
    private function extract_pdf_content($file) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        // Check cache firs
        $content_hash = $file->get_contenthash();
        $timemodified = $file->get_timemodified();

        // Try to get from cache using the cache manager
        $cache_key = block_tutoring_machine_cache_manager::get_pdf_content_key($content_hash);
        $cached_item = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_PDF, $cache_key);

        if ($cached_item) {
            // Check if cache is valid (not expired and file hasn't been modified)
            $cached_data = $cached_item['data'];
            $metadata = $cached_item['metadata'];

            if (isset($metadata['timemodified']) && $metadata['timemodified'] >= $timemodified) {
                error_log("Using cached PDF content for " . $file->get_filename());
                return $cached_data;
            }
        }

        // Not in cache or cache outdated, extract from PDF
        error_log("Extracting content from PDF " . $file->get_filename());

        // Create a temporary file to save the PDF
        $tempdir = $CFG->tempdir . '/tutoring_machine';
        if (!is_dir($tempdir)) {
            mkdir($tempdir, 0777, true);
        }

        $tempfile = $tempdir . '/' . $content_hash . '.pdf';
        $text = '';

        try {
            // Save the PDF to the temporary file
            $file->copy_content_to($tempfile);

            // Try multiple methods to extract tex
            $text = $this->extract_pdf_with_pdftotext($tempfile);

            // If pdftotext fails, try PHP libraries
            if (empty($text)) {
                $text = $this->extract_pdf_with_php_library($tempfile);
            }

            // If all methods fail, return a message
            if (empty($text)) {
                $text = "[PDF-Extraktion nicht möglich. Die Serverkonfiguration unterstützt keine PDF-Textextraktion. Bitte installieren Sie 'poppler-utils' oder eine kompatible PHP-Bibliothek.]";
            }

            // Truncate if too long (max 10000 chars)
            if (strlen($text) > 10000) {
                $text = substr($text, 0, 10000) . "...\n[PDF-Inhalt gekürzt, zu lang für vollständige Einbeziehung]";
            }

            // Save to cache with the cache manager
            if (!empty($text)) {
                $metadata = [
                    'timemodified' => $timemodified,
                    'filename' => $file->get_filename()
                ];

                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_PDF,
                    block_tutoring_machine_cache_manager::get_pdf_content_key($content_hash),
                    $text,
                    $metadata
                );
            }

            return $text;
        } catch (Exception $e) {
            error_log("Error extracting PDF content: " . $e->getMessage());
            return "[Fehler bei der Extraktion des PDF-Inhalts: " . $e->getMessage() . "]";
        } finally {
            // Clean up
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
        }
    }

    /**
     * Extract text from PDF using pdftotext (poppler-utils)
     * This is the preferred method for PDF extraction due to speed and accuracy
     *
     * @param string $tempfile Path to the temporary PDF file
     * @return string Extracted text
     */
    private function extract_pdf_with_pdftotext($tempfile) {
        static $pdftotext_path = null;
        
        // Only check for pdftotext once per request for performance
        if ($pdftotext_path === null) {
            $pdftotext_path = $this->find_pdftotext_binary();
        }
        
        // If pdftotext is not available, return empty text
        if (empty($pdftotext_path)) {
            return '';
        }
        
        // Extract text using pdftotext
        $safe_tempfile = escapeshellarg($tempfile);
        $command = "{$pdftotext_path} -enc UTF-8 {$safe_tempfile} -";
        
        $output_array = [];
        if (function_exists('exec')) {
            @exec($command, $output_array, $return_var);
            if ($return_var === 0) {
                $text = implode("\n", $output_array);
                error_log("PDF text extracted using pdftotext");
                return $text;
            }
            error_log("pdftotext command failed with return code: " . $return_var);
        }
        
        return '';
    }
    
    /**
     * Find the pdftotext binary on the system
     * 
     * @return string|null Path to pdftotext binary or null if not found
     */
    private function find_pdftotext_binary() {
        // Check if pdftotext is available in PATH
        if (function_exists('exec')) {
            @exec('which pdftotext', $output_array, $return_var);
            if ($return_var === 0 && !empty($output_array[0])) {
                return $output_array[0];
            }
        }
        
        // Check common installation locations
        $possible_paths = [
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
            '/opt/homebrew/bin/pdftotext'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        error_log("pdftotext not found on system");
        return null;
    }

    /**
     * Extract text from PDF using PHP libraries
     * Fallback method when pdftotext is not available
     *
     * @param string $tempfile Path to the temporary PDF file
     * @return string Extracted text
     */
    private function extract_pdf_with_php_library($tempfile) {
        // Try using Smalot\PdfParser (preferred PHP library)
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($tempfile);
                $text = $pdf->getText();
                
                if (!empty($text)) {
                    error_log("PDF text extracted using Smalot\\PdfParser");
                    return $text;
                }
            } catch (Exception $e) {
                error_log("Smalot\\PdfParser error: " . $e->getMessage());
            }
        }
        
        // Try using Spatie\PdfToText
        if (class_exists('\Spatie\PdfToText\Pdf')) {
            try {
                $text = \Spatie\PdfToText\Pdf::getText($tempfile);
                
                if (!empty($text)) {
                    error_log("PDF text extracted using Spatie\\PdfToText");
                    return $text;
                }
            } catch (Exception $e) {
                error_log("Spatie\\PdfToText error: " . $e->getMessage());
            }
        }
        
        // Try PDFlib extension if available
        if (extension_loaded('pdflib')) {
            try {
                $pdf = new PDFlib();
                $pdf->open_file("");
                $pdf->open_pdf($tempfile);
                $text = '';
                
                for ($i = 1; $i <= $pdf->get_pdi_parameter("Pages", ""); $i++) {
                    $page = $pdf->open_pdi_page($i, "");
                    $text .= $pdf->get_pdi_parameter("Contents", $page);
                    $pdf->close_pdi_page($page);
                }
                
                $pdf->close_pdf();
                
                if (!empty($text)) {
                    error_log("PDF text extracted using PDFlib");
                    return $text;
                }
            } catch (Exception $e) {
                error_log("PDFlib error: " . $e->getMessage());
            }
        }
        
        error_log("No PHP PDF extraction methods available");
        return '';
    }
    
    /**
     * Extract content from Office documents (Word, Excel, PowerPoint)
     *
     * @return string Formatted content from Office documents
     */
    private function extract_office_documents() {
        global $DB;

        $office_context = '';

        // Get course modules of type 'resource'
        $resources = $this->modinfo->get_instances_of('resource');

        // Loop through each resource
        foreach ($resources as $resource_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$resource_instance->uservisible || !$this->should_include_activity($resource_instance)) {
                continue;
            }

            // Get resource record
            $resource = $DB->get_record('resource', ['id' => $resource_instance->instance], '*', MUST_EXIST);
            if (!$resource) {
                continue;
            }

            // Set cmid for context resolution
            $resource->cmid = $resource_instance->id;

            // Get the file
            $file = $this->extract_resource_file($resource);
            if (!$file) {
                continue;
            }
            
            // Check if it's an Office document
            $mimetype = $file->get_mimetype();
            $doc_type = '';
            
            if ($mimetype === self::MIMETYPE_DOCX || $mimetype === self::MIMETYPE_DOC) {
                $doc_type = 'word';
            } else if ($mimetype === self::MIMETYPE_XLSX || $mimetype === self::MIMETYPE_XLS) {
                $doc_type = 'excel';
            } else if ($mimetype === self::MIMETYPE_PPTX || $mimetype === self::MIMETYPE_PPT) {
                $doc_type = 'powerpoint';
            } else {
                // Not an Office document
                continue;
            }

            $description = $this->normalise_text($resource->intro ?? '');
            $office_content = $this->clamp_text($this->extract_office_content($file, $doc_type), 12000);

            if ($description === '' && empty($office_content)) {
                continue;
            }

            $section = "### {$this->get_doc_type_name($doc_type)}: {$resource->name}\n";
            if ($description !== '') {
                $section .= "Beschreibung: {$description}\n\n";
            }

            if (!empty($office_content)) {
                $section .= $office_content . "\n\n";

                if ($this->file_upload_mode) {
                    $this->register_file_for_upload($file, $resource->name, $doc_type, $office_content);
                }
            } else {
                $section .= "\n";
            }

            $office_context .= $section;
        }

        return $office_context;
    }
    
    /**
     * Get the human-readable document type name
     *
     * @param string $doc_type The document type (word, excel, powerpoint)
     * @return string Human-readable document type
     */
    private function get_doc_type_name($doc_type) {
        switch ($doc_type) {
            case 'word':
                return 'Word-Dokument';
            case 'excel':
                return 'Excel-Tabelle';
            case 'powerpoint':
                return 'PowerPoint-Präsentation';
            default:
                return 'Office-Dokument';
        }
    }
    
    /**
     * Extract text content from an Office document
     *
     * @param stored_file $file The Office document file stored in Moodle
     * @param string $doc_type The document type (word, excel, powerpoint)
     * @return string The extracted text
     */
    private function extract_office_content($file, $doc_type) {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        // Check cache first
        $content_hash = $file->get_contenthash();
        $timemodified = $file->get_timemodified();
        
        // Try to get from cache using the cache manager
        $cache_key = block_tutoring_machine_cache_manager::get_office_content_key($content_hash, $doc_type);
        $cached_item = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_OFFICE, $cache_key);

        if ($cached_item && is_array($cached_item)) {
            // Check if cache is valid (not expired and file hasn't been modified)
            if (isset($cached_item['data']) && isset($cached_item['metadata'])) {
                $cached_data = $cached_item['data'];
                $metadata = $cached_item['metadata'];
                
                if (isset($metadata['timemodified']) && $metadata['timemodified'] >= $timemodified) {
                    error_log("Using cached Office content for " . $file->get_filename());
                    return $cached_data;
                }
            }
        }

        // Not in cache or cache outdated, extract from Office document
        error_log("Extracting content from Office document " . $file->get_filename());

        // Create a temporary file to save the document
        $tempdir = $CFG->tempdir . '/tutoring_machine';
        if (!is_dir($tempdir)) {
            mkdir($tempdir, 0777, true);
        }

        $tempfile = $tempdir . '/' . $content_hash . '.' . $this->get_file_extension($doc_type);
        $text = '';

        try {
            // Save the document to the temporary file
            $file->copy_content_to($tempfile);

            // Use different extraction methods based on document type
            if ($doc_type === 'word') {
                $text = $this->extract_word_document($tempfile);
            } else if ($doc_type === 'excel') {
                $text = $this->extract_excel_document($tempfile);
            } else if ($doc_type === 'powerpoint') {
                $text = $this->extract_powerpoint_document($tempfile);
            }

            // If all methods fail, return a message
            if (empty($text)) {
                $text = "[Extraktion nicht möglich. Die Serverkonfiguration unterstützt keine Textextraktion für {$this->get_doc_type_name($doc_type)}.]";
            }

            // Truncate if too long (max 10000 chars)
            if (strlen($text) > 10000) {
                $text = substr($text, 0, 10000) . "...\n[Inhalt gekürzt, zu lang für vollständige Einbeziehung]";
            }

            // Save to cache with the cache manager
            if (!empty($text)) {
                $metadata = [
                    'timemodified' => $timemodified,
                    'filename' => $file->get_filename()
                ];

                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_OFFICE,
                    $cache_key,
                    $text,
                    $metadata
                );
            }

            return $text;
        } catch (Exception $e) {
            error_log("Error extracting Office content: " . $e->getMessage());
            return "[Fehler bei der Extraktion des Inhalts: " . $e->getMessage() . "]";
        } finally {
            // Clean up
            if (file_exists($tempfile)) {
                unlink($tempfile);
            }
        }
    }
    
    /**
     * Get the file extension for the document type
     *
     * @param string $doc_type The document type (word, excel, powerpoint)
     * @return string The file extension
     */
    private function get_file_extension($doc_type) {
        switch ($doc_type) {
            case 'word':
                return 'docx';
            case 'excel':
                return 'xlsx';
            case 'powerpoint':
                return 'pptx';
            default:
                return 'bin';
        }
    }
    
    /**
     * Extract text from a Word document
     *
     * @param string $tempfile Path to the temporary Word file
     * @return string Extracted text
     */
    private function extract_word_document($tempfile) {
        error_log("Attempting to extract text from Word document: " . $tempfile);
        
        // Check for basic file readability
        if (!is_readable($tempfile)) {
            error_log("Word file is not readable: " . $tempfile);
            return '';
        }
        
        // Try using PhpOffice/PhpWord
        if (class_exists('\PhpOffice\PhpWord\IOFactory')) {
            error_log("PhpOffice/PhpWord class found, attempting to use it");
            try {
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($tempfile);
                $text = '';
                
                // Extract text from each section
                $sections = $phpWord->getSections();
                foreach ($sections as $section) {
                    $elements = $section->getElements();
                    foreach ($elements as $element) {
                        if (method_exists($element, 'getText')) {
                            $text .= $element->getText() . "\n";
                        } else if (method_exists($element, 'getElements')) {
                            $subElements = $element->getElements();
                            foreach ($subElements as $subElement) {
                                if (method_exists($subElement, 'getText')) {
                                    $text .= $subElement->getText() . "\n";
                                }
                            }
                        }
                    }
                }
                
                if (!empty($text)) {
                    error_log("Word document text extracted using PhpOffice/PhpWord");
                    return $text;
                } else {
                    error_log("PhpOffice/PhpWord returned empty text");
                }
            } catch (Exception $e) {
                error_log("PhpOffice/PhpWord error: " . $e->getMessage());
            }
        } else {
            error_log("PhpOffice/PhpWord class not found");
        }
        
        error_log("Trying external tools for Word extraction");
        // Try using command-line tools
        $externalText = $this->extract_office_with_external_tool($tempfile, 'word');
        
        if (!empty($externalText)) {
            error_log("Successfully extracted Word text using external tools");
            return $externalText;
        } else {
            error_log("Failed to extract Word text using external tools");
        }
        
        error_log("All Word extraction methods failed");
        return '';
    }
    
    /**
     * Extract text from an Excel document
     *
     * @param string $tempfile Path to the temporary Excel file
     * @return string Extracted text
     */
    private function extract_excel_document($tempfile) {
        // Try using PhpOffice/PhpSpreadsheet
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempfile);
                $text = '';
                
                // Extract text from each worksheet
                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    $text .= "Tabellenblatt: " . $worksheet->getTitle() . "\n";
                    
                    foreach ($worksheet->getRowIterator() as $row) {
                        $rowText = '';
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false);
                        
                        foreach ($cellIterator as $cell) {
                            $rowText .= $cell->getValue() . "\t";
                        }
                        
                        if (trim($rowText) !== '') {
                            $text .= trim($rowText) . "\n";
                        }
                    }
                    
                    $text .= "\n";
                }
                
                if (!empty($text)) {
                    error_log("Excel document text extracted using PhpOffice/PhpSpreadsheet");
                    return $text;
                }
            } catch (Exception $e) {
                error_log("PhpOffice/PhpSpreadsheet error: " . $e->getMessage());
            }
        }
        
        // Try using command-line tools
        return $this->extract_office_with_external_tool($tempfile, 'excel');
    }
    
    /**
     * Extract text from a PowerPoint document
     *
     * @param string $tempfile Path to the temporary PowerPoint file
     * @return string Extracted text
     */
    private function extract_powerpoint_document($tempfile) {
        error_log("Attempting to extract text from PowerPoint document: " . $tempfile);
        
        // Check for basic file readability
        if (!is_readable($tempfile)) {
            error_log("PowerPoint file is not readable: " . $tempfile);
            return '';
        }
        
        // Try using PhpOffice/PhpPresentation
        if (class_exists('\PhpOffice\PhpPresentation\IOFactory')) {
            error_log("PhpOffice/PhpPresentation class found, attempting to use it");
            try {
                $presentation = \PhpOffice\PhpPresentation\IOFactory::load($tempfile);
                $text = '';
                
                // Extract text from each slide
                $slideCount = $presentation->getSlideCount();
                error_log("PowerPoint has " . $slideCount . " slides");
                
                for ($i = 0; $i < $slideCount; $i++) {
                    $slide = $presentation->getSlide($i);
                    $text .= "Folie " . ($i+1) . ":\n";
                    
                    // Extract text from shapes
                    $shapes = $slide->getShapeCollection();
                    error_log("Slide " . ($i+1) . " has " . count($shapes) . " shapes");
                    
                    foreach ($shapes as $shape) {
                        if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                            $paragraphs = $shape->getParagraphs();
                            foreach ($paragraphs as $paragraph) {
                                $richTexts = $paragraph->getRichTextElements();
                                foreach ($richTexts as $richText) {
                                    $text .= $richText->getText() . " ";
                                }
                                $text .= "\n";
                            }
                        }
                    }
                    
                    $text .= "\n";
                }
                
                if (!empty($text)) {
                    error_log("PowerPoint document text extracted using PhpOffice/PhpPresentation");
                    return $text;
                } else {
                    error_log("PhpOffice/PhpPresentation returned empty text");
                }
            } catch (Exception $e) {
                error_log("PhpOffice/PhpPresentation error: " . $e->getMessage());
            }
        } else {
            error_log("PhpOffice/PhpPresentation class not found");
        }
        
        error_log("Trying external tools for PowerPoint extraction");
        // Try using command-line tools
        $externalText = $this->extract_office_with_external_tool($tempfile, 'powerpoint');
        
        if (!empty($externalText)) {
            error_log("Successfully extracted PowerPoint text using external tools");
            return $externalText;
        } else {
            error_log("Failed to extract PowerPoint text using external tools");
        }
        
        error_log("All PowerPoint extraction methods failed");
        return '';
    }
    
    /**
     * Extract text from Office documents using external tools
     *
     * @param string $tempfile Path to the temporary Office file
     * @param string $doc_type The document type (word, excel, powerpoint)
     * @return string Extracted text
     */
    private function extract_office_with_external_tool($tempfile, $doc_type) {
        error_log("Attempting to extract {$doc_type} document using external tools");
        
        // Try using Apache Tika if available
        $tika_path = $this->find_tika_jar();
        if (!empty($tika_path)) {
            error_log("Found Apache Tika at: " . $tika_path);
            
            if (function_exists('exec')) {
                if (is_readable($tempfile)) {
                    $safe_tempfile = escapeshellarg($tempfile);
                    $safe_tika_path = escapeshellarg($tika_path);
                    
                    // Execute tika with -t option to get plain text
                    $command = "java -jar {$safe_tika_path} -t {$safe_tempfile}";
                    error_log("Executing Tika command: " . $command);
                    
                    $output_array = [];
                    exec($command, $output_array, $return_var);
                    
                    if ($return_var === 0) {
                        if (!empty($output_array)) {
                            $text = implode("\n", $output_array);
                            error_log("Successfully extracted {$doc_type} text using Apache Tika");
                            return $text;
                        } else {
                            error_log("Tika command succeeded but returned empty output");
                        }
                    } else {
                        error_log("Tika command failed with return code: " . $return_var);
                    }
                } else {
                    error_log("Temp file not readable for Tika: " . $tempfile);
                }
            } else {
                error_log("exec() function is not available for Tika");
            }
        } else {
            error_log("Apache Tika not found on system");
        }
        
        // Try using simple file-based extraction for Word documents (.docx)
        if ($doc_type === 'word' && class_exists('ZipArchive') && substr($tempfile, -5) === '.docx') {
            error_log("Attempting direct .docx extraction using ZipArchive");
            try {
                $text = $this->extract_docx_without_library($tempfile);
                if (!empty($text)) {
                    error_log("Successfully extracted Word text using direct ZIP method");
                    return $text;
                } else {
                    error_log("Direct ZIP extraction returned empty text");
                }
            } catch (Exception $e) {
                error_log("Direct ZIP extraction error: " . $e->getMessage());
            }
        }
        
        // Try using simple file-based extraction for PowerPoint documents (.pptx)
        if ($doc_type === 'powerpoint' && class_exists('ZipArchive') && substr($tempfile, -5) === '.pptx') {
            error_log("Attempting direct .pptx extraction using ZipArchive");
            try {
                $text = $this->extract_pptx_without_library($tempfile);
                if (!empty($text)) {
                    error_log("Successfully extracted PowerPoint text using direct ZIP method");
                    return $text;
                } else {
                    error_log("Direct ZIP extraction returned empty text for PowerPoint");
                }
            } catch (Exception $e) {
                error_log("Direct PowerPoint ZIP extraction error: " . $e->getMessage());
            }
        }
        
        // Try using LibreOffice if available
        $libreoffice_path = $this->find_libreoffice_binary();
        if (!empty($libreoffice_path)) {
            error_log("Found LibreOffice at: " . $libreoffice_path);
            
            if (function_exists('exec')) {
                if (is_readable($tempfile)) {
                    // Create a temporary directory for the converted file
                    $tempoutdir = sys_get_temp_dir() . '/tutoring_machine_' . uniqid();
                    if (mkdir($tempoutdir)) {
                        error_log("Created temp directory for LibreOffice output: " . $tempoutdir);
                        
                        $safe_tempfile = escapeshellarg($tempfile);
                        $safe_tempoutdir = escapeshellarg($tempoutdir);
                        $safe_libreoffice_path = escapeshellarg($libreoffice_path);
                        
                        // Convert to text using LibreOffice
                        $command = "{$safe_libreoffice_path} --headless --convert-to txt:Text --outdir {$safe_tempoutdir} {$safe_tempfile}";
                        error_log("Executing LibreOffice command: " . $command);
                        
                        $output_array = [];
                        exec($command, $output_array, $return_var);
                        error_log("LibreOffice command output: " . print_r($output_array, true));
                        
                        // Get the converted text file name
                        $base_name = basename($tempfile);
                        $txt_file = $tempoutdir . '/' . pathinfo($base_name, PATHINFO_FILENAME) . '.txt';
                        error_log("Looking for converted text file at: " . $txt_file);
                        
                        if ($return_var === 0) {
                            if (file_exists($txt_file)) {
                                $text = file_get_contents($txt_file);
                                // Clean up
                                unlink($txt_file);
                                rmdir($tempoutdir);
                                
                                if (!empty($text)) {
                                    error_log("Successfully extracted {$doc_type} text using LibreOffice");
                                    return $text;
                                } else {
                                    error_log("LibreOffice conversion succeeded but text file was empty");
                                }
                            } else {
                                error_log("LibreOffice command succeeded but output file not found");
                            }
                        } else {
                            error_log("LibreOffice command failed with return code: " . $return_var);
                        }
                        
                        // Clean up
                        if (file_exists($tempoutdir)) {
                            rmdir($tempoutdir);
                        }
                    } else {
                        error_log("Failed to create temp directory for LibreOffice output: " . $tempoutdir);
                    }
                } else {
                    error_log("Temp file not readable for LibreOffice: " . $tempfile);
                }
            } else {
                error_log("exec() function is not available for LibreOffice");
            }
        } else {
            error_log("LibreOffice not found on system");
        }
        
        // If we get here, no extraction method was successful
        error_log("All external extraction methods failed for {$doc_type} document");
        return '';
    }
    
    /**
     * Extract text from a .docx file using PHP's ZipArchive class
     * This provides a fallback when no libraries or external tools are available
     *
     * @param string $tempfile Path to the temporary .docx file
     * @return string Extracted text
     */
    private function extract_docx_without_library($tempfile) {
        $text = '';
        
        // Check if ZipArchive class is available
        if (!class_exists('ZipArchive')) {
            error_log("ZipArchive class is not available for .docx extraction");
            return '';
        }
        
        // Open the .docx file as a ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($tempfile) !== true) {
            error_log("Failed to open .docx as ZIP: " . $tempfile);
            return '';
        }
        
        // Look for the main document content
        $entry_name = 'word/document.xml';
        if (($index = $zip->locateName($entry_name)) !== false) {
            // Read the XML content
            $content = $zip->getFromIndex($index);
            
            // Simple XML parsing to extract text
            $content = str_replace('</w:p>', "\n", $content); // Add newlines
            $content = preg_replace('/<.*?>/', '', $content); // Remove XML tags
            $text = html_entity_decode($content);
        } else {
            error_log("Could not find document.xml in the .docx file");
        }
        
        $zip->close();
        return trim($text);
    }
    
    /**
     * Extract text from a .pptx file using PHP's ZipArchive class
     * This provides a fallback when no libraries or external tools are available
     *
     * @param string $tempfile Path to the temporary .pptx file
     * @return string Extracted text
     */
    private function extract_pptx_without_library($tempfile) {
        $text = '';
        $slides = [];
        
        // Check if ZipArchive class is available
        if (!class_exists('ZipArchive')) {
            error_log("ZipArchive class is not available for .pptx extraction");
            return '';
        }
        
        // Open the .pptx file as a ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($tempfile) !== true) {
            error_log("Failed to open .pptx as ZIP: " . $tempfile);
            return '';
        }
        
        // Find all slide XML files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry_name = $zip->getNameIndex($i);
            
            // Look for slide content files
            if (preg_match('|ppt/slides/slide([0-9]+)\.xml|', $entry_name, $matches)) {
                $slide_num = (int)$matches[1];
                
                // Get the content of the slide
                $content = $zip->getFromName($entry_name);
                if ($content !== false) {
                    // Extract text content
                    $slide_text = '';
                    
                    // Extract text from text runs (<a:t>text content</a:t>)
                    preg_match_all('|<a:t[^>]*>(.*?)</a:t>|s', $content, $text_matches);
                    foreach ($text_matches[1] as $text_content) {
                        $slide_text .= $text_content . " ";
                    }
                    
                    // Store slide with its number for proper ordering
                    $slides[$slide_num] = trim($slide_text);
                }
            }
        }
        
        $zip->close();
        
        // Sort slides by number and combine text
        ksort($slides);
        foreach ($slides as $slide_num => $slide_text) {
            if (!empty($slide_text)) {
                $text .= "Folie " . $slide_num . ":\n" . $slide_text . "\n\n";
            }
        }
        
        return trim($text);
    }
    
    /**
     * Find the Apache Tika JAR file on the system
     * 
     * @return string|null Path to Tika JAR or null if not found
     */
    private function find_tika_jar() {
        // Check common installation locations
        $possible_paths = [
            '/usr/local/bin/tika-app.jar',
            '/usr/bin/tika-app.jar',
            '/opt/tika/tika-app.jar',
            '/opt/homebrew/bin/tika-app.jar'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_readable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Find the LibreOffice binary on the system
     * 
     * @return string|null Path to LibreOffice binary or null if not found
     */
    private function find_libreoffice_binary() {
        // Check if libreoffice is available in PATH
        if (function_exists('exec')) {
            @exec('which libreoffice', $output_array, $return_var);
            if ($return_var === 0 && !empty($output_array[0])) {
                return $output_array[0];
            }
            
            // Also check for soffice (LibreOffice's alternative binary name)
            @exec('which soffice', $output_array, $return_var);
            if ($return_var === 0 && !empty($output_array[0])) {
                return $output_array[0];
            }
        }
        
        // Check common installation locations
        $possible_paths = [
            '/usr/bin/libreoffice',
            '/usr/bin/soffice',
            '/usr/local/bin/libreoffice',
            '/usr/local/bin/soffice',
            '/opt/libreoffice/program/soffice',
            '/Applications/LibreOffice.app/Contents/MacOS/soffice'
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        return null;
    }
    /**
     * Extract content from forum discussions with caching
     *
     * @return string Formatted content from forums
     */
    private function extract_forum_discussions() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $forum_context = '';

        // Check if forum module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('forum', array_keys($modules))) {
                // Forum module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $forum_instances = $this->modinfo->get_instances_of('forum');
            if (empty($forum_instances)) {
                // No forums in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking forum availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('forum') as $forum_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$forum_instance->uservisible || !$this->should_include_activity($forum_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('forum', $forum_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                $forum_context .= $cached_content;
                continue;
            }

            // Get forum record
            $forum = $DB->get_record('forum', ['id' => $forum_instance->instance], '*', MUST_EXIST);
            if ($forum) {
                // Start building forum conten
                $forum_content = "### Forum: {$forum->name}\n";

                // Add introduction if available
                if (!empty($forum->intro)) {
                    $forum_content .= "Einführung: " . strip_tags($forum->intro) . "\n\n";
                }

                // Get discussions in this forum
                $discussions = $DB->get_records('forum_discussions', ['forum' => $forum->id]);

                foreach ($discussions as $discussion) {
                    $forum_content .= "- Diskussion: {$discussion->name}\n";

                    // Get posts in this discussion
                    $posts = $DB->get_records('forum_posts', ['discussion' => $discussion->id], 'created ASC');

                    foreach ($posts as $post) {
                        // Skip if it's just a reply without substantial conten
                        if (strlen(strip_tags($post->message)) < 20) {
                            continue;
                        }

                        // Add post content (first 500 chars if it's very long)
                        $post_content = strip_tags($post->message);
                        if (strlen($post_content) > 500) {
                            $post_content = substr($post_content, 0, 500) . "...";
                        }

                        $forum_content .= "  - Beitrag: {$post->subject}\n";
                        $forum_content .= "    " . str_replace("\n", "\n    ", $post_content) . "\n";
                    }
                }

                $forum_content .= "\n";

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                    $cache_key,
                    $forum_content,
                    [
                        'timemodified' => $forum->timemodified,
                        'discussion_count' => count($discussions)
                    ]
                );

                // Add to outpu
                $forum_context .= $forum_content;
            }
        }

        return $forum_context;
    }

    /**
     * Extract content from quizzes with caching
     *
     * @return string Formatted content from quizzes
     */
    private function extract_quiz_questions() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $quiz_context = '';

        // Check if quiz module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('quiz', array_keys($modules))) {
                // Quiz module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $quiz_instances = $this->modinfo->get_instances_of('quiz');
            if (empty($quiz_instances)) {
                // No quizzes in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking quiz availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('quiz') as $quiz_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$quiz_instance->uservisible || !$this->should_include_activity($quiz_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('quiz', $quiz_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                $quiz_context .= $cached_content;
                continue;
            }

            // Get quiz record
            $quiz = $DB->get_record('quiz', ['id' => $quiz_instance->instance], '*', MUST_EXIST);
            if ($quiz) {
                // Start building quiz conten
                $quiz_content = "### Quiz: {$quiz->name}\n";

                // Add introduction if available
                if (!empty($quiz->intro)) {
                    $quiz_content .= "Einführung: " . strip_tags($quiz->intro) . "\n\n";
                }

                // Get quiz questions (this might need adjustment depending on Moodle version)
                $sql = "SELECT q.id, q.name, q.questiontext, q.qtype
                        FROM {quiz_slots} qs
                        JOIN {question} q ON q.id = qs.questionid
                        WHERE qs.quizid = :quizid
                        ORDER BY qs.slot";

                $questions = $DB->get_records_sql($sql, ['quizid' => $quiz->id]);

                foreach ($questions as $question) {
                    // Skip if it's a random question
                    if ($question->qtype === 'random') {
                        continue;
                    }

                    $quiz_content .= "- Frage: " . strip_tags($question->questiontext) . "\n";

                    // Add answers for certain question types
                    if (in_array($question->qtype, ['multichoice', 'truefalse', 'match'])) {
                        $answers = $DB->get_records('question_answers', ['question' => $question->id]);
                        foreach ($answers as $answer) {
                            // Only include correct answers or first few options
                            if ($answer->fraction > 0 || count($answers) <= 5) {
                                $quiz_content .= "  - Option: " . strip_tags($answer->answer) . "\n";

                                // Add feedback for correct answers
                                if ($answer->fraction > 0 && !empty($answer->feedback)) {
                                    $quiz_content .= "    Feedback: " . strip_tags($answer->feedback) . "\n";
                                }
                            }
                        }
                    }
                }

                $quiz_content .= "\n";

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                    $cache_key,
                    $quiz_content,
                    [
                        'timemodified' => $quiz->timemodified,
                        'question_count' => count($questions)
                    ]
                );

                // Add to outpu
                $quiz_context .= $quiz_content;
            }
        }

        return $quiz_context;
    }

    /**
     * Extract content from book modules with caching
     *
     * @return string Formatted content from books
     */
    private function extract_book_chapters() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $book_context = '';

        // Check if book module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('book', array_keys($modules))) {
                // Book module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $book_instances = $this->modinfo->get_instances_of('book');
            if (empty($book_instances)) {
                // No books in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking book availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('book') as $book_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$book_instance->uservisible || !$this->should_include_activity($book_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('book', $book_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                $book_context .= $cached_content;
                continue;
            }

            // Get book record
            $book = $DB->get_record('book', ['id' => $book_instance->instance], '*', MUST_EXIST);
            if ($book) {
                // Start building book conten
                $book_content = "### Buch: {$book->name}\n";

                // Add introduction if available
                if (!empty($book->intro)) {
                    $book_content .= "Einführung: " . strip_tags($book->intro) . "\n\n";
                }

                // Get chapters in this book
                $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id], 'pagenum ASC');

                foreach ($chapters as $chapter) {
                    if ($chapter->subchapter) {
                        $book_content .= "  - Unterkapitel: {$chapter->title}\n";
                    } else {
                        $book_content .= "- Kapitel: {$chapter->title}\n";
                    }

                    // Add chapter conten
                    $chapter_text = strip_tags($chapter->content);

                    // Truncate if very long
                    if (strlen($chapter_text) > 1000) {
                        $chapter_text = substr($chapter_text, 0, 1000) . "...";
                    }

                    $book_content .= "  " . str_replace("\n", "\n  ", $chapter_text) . "\n\n";
                }

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                    $cache_key,
                    $book_content,
                    [
                        'timemodified' => $book->timemodified,
                        'chapter_count' => count($chapters)
                    ]
                );

                // Add to outpu
                $book_context .= $book_content;
            }
        }

        return $book_context;
    }

    /**
     * Extract content from assignments with caching
     *
     * @return string Formatted content from assignments
     */
    private function extract_assignments() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $assignments_context = '';

        // Check if assign module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('assign', array_keys($modules))) {
                // Assign module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $assign_instances = $this->modinfo->get_instances_of('assign');
            if (empty($assign_instances)) {
                // No assignments in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking assignment availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('assign') as $assign_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$assign_instance->uservisible || !$this->should_include_activity($assign_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('assign', $assign_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                $assignments_context .= $cached_content;
                continue;
            }

            // Get assignment record
            $assign = $DB->get_record('assign', ['id' => $assign_instance->instance], '*', MUST_EXIST);
            if ($assign) {
                // Start building assignment conten
                $assign_content = "### Aufgabe: {$assign->name}\n";

                // Add introduction
                if (!empty($assign->intro)) {
                    $assign_content .= "Beschreibung: " . strip_tags($assign->intro) . "\n\n";
                }

                // Add due date if se
                if (!empty($assign->duedate) && $assign->duedate > 0) {
                    $assign_content .= "Abgabetermin: " . userdate($assign->duedate) . "\n";
                }

                // Add assignment settings
                $assign_content .= "Maximale Bewertung: " . $assign->grade . "\n";

                $assign_content .= "\n";

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                    $cache_key,
                    $assign_content,
                    [
                        'timemodified' => $assign->timemodified
                    ]
                );

                // Add to outpu
                $assignments_context .= $assign_content;
            }
        }

        return $assignments_context;
    }

    /**
     * Extract content from labels with caching
     *
     * @return string Formatted content from labels
     */
    private function extract_labels() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $labels_context = '';

        // Check if label module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('label', array_keys($modules))) {
                // Label module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $label_instances = $this->modinfo->get_instances_of('label');
            if (empty($label_instances)) {
                // No labels in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking label availability: " . $e->getMessage());
            return '';
        }

        // Group labels by section
        $section_labels = [];

        foreach ($this->modinfo->get_instances_of('label') as $label_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$label_instance->uservisible || !$this->should_include_activity($label_instance)) {
                continue;
            }

            // Get section info
            $section_id = $label_instance->get_section_info()->section;
            $section_name = get_section_name($this->course, $label_instance->get_section_info());

            if (!isset($section_labels[$section_id])) {
                $section_labels[$section_id] = [
                    'name' => $section_name,
                    'labels' => []
                ];
            }

            // Add label to section
            $section_labels[$section_id]['labels'][] = $label_instance;
        }

        // Process labels by section
        foreach ($section_labels as $section_id => $section) {
            $section_content = "### Abschnittshinweise: {$section['name']}\n";

            foreach ($section['labels'] as $label_instance) {
                // Check cache firs
                $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('label', $label_instance->instance);
                $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

                // If valid cache exists, use i
                if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                    $section_content .= $cached_content;
                    continue;
                }

                // Get label record
                $label = $DB->get_record('label', ['id' => $label_instance->instance], '*', MUST_EXIST);
                if ($label) {
                    // Extract content from label
                    $label_content = "- Hinweis: " . strip_tags($label->intro) . "\n";

                    // Store in cache
                    block_tutoring_machine_cache_manager::set(
                        block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                        $cache_key,
                        $label_content,
                        [
                            'timemodified' => $label->timemodified
                        ]
                    );

                    // Add to section content
                    $section_content .= $label_content;
                }
            }

            $section_content .= "\n";
            $labels_context .= $section_content;
        }

        return $labels_context;
    }

    /**
     * Extract content from URL resources with caching
     *
     * @return string Formatted content from URLs
     */
    private function extract_urls() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $urls_context = '';

        // Check if url module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('url', array_keys($modules))) {
                // URL module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $url_instances = $this->modinfo->get_instances_of('url');
            if (empty($url_instances)) {
                // No URLs in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking URL availability: " . $e->getMessage());
            return '';
        }

        // Group URLs by section
        $section_urls = [];

        foreach ($this->modinfo->get_instances_of('url') as $url_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$url_instance->uservisible || !$this->should_include_activity($url_instance)) {
                continue;
            }

            // Get section info
            $section_id = $url_instance->get_section_info()->section;
            $section_name = get_section_name($this->course, $url_instance->get_section_info());

            if (!isset($section_urls[$section_id])) {
                $section_urls[$section_id] = [
                    'name' => $section_name,
                    'urls' => []
                ];
            }

            // Add URL to section
            $section_urls[$section_id]['urls'][] = $url_instance;
        }

        // Process URLs by section
        foreach ($section_urls as $section_id => $section) {
            $section_content = "### Externe Links in Abschnitt: {$section['name']}\n";

            foreach ($section['urls'] as $url_instance) {
                // Check cache firs
                $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('url', $url_instance->instance);
                $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

                // If valid cache exists, use i
                if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                    $section_content .= $cached_content;
                    continue;
                }

                // Get URL record
                $url = $DB->get_record('url', ['id' => $url_instance->instance], '*', MUST_EXIST);
                if ($url) {
                    // Extract content from URL
                    $url_content = "- Link: {$url->name}\n";
                    $url_content .= "  URL: {$url->externalurl}\n";

                    if (!empty($url->intro)) {
                        $url_content .= "  Beschreibung: " . strip_tags($url->intro) . "\n";
                    }

                    // Store in cache
                    block_tutoring_machine_cache_manager::set(
                        block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                        $cache_key,
                        $url_content,
                        [
                            'timemodified' => $url->timemodified
                        ]
                    );

                    // Add to section content
                    $section_content .= $url_content;
                }
            }

            $section_content .= "\n";
            $urls_context .= $section_content;
        }

        return $urls_context;
    }

    /**
     * Extract content from lessons with caching
     *
     * @return string Formatted content from lessons
     */
    private function extract_lessons() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

        $lessons_context = '';

        // Check if lesson module exists before trying to get instances
        try {
            $modules = $this->modinfo->get_used_module_names();
            if (!in_array('lesson', array_keys($modules))) {
                // Lesson module not installed or not used in this course
                return '';
            }

            // Now check if there are any instances
            $lesson_instances = $this->modinfo->get_instances_of('lesson');
            if (empty($lesson_instances)) {
                // No lessons in this course
                return '';
            }
        } catch (Exception $e) {
            error_log("Error checking lesson availability: " . $e->getMessage());
            return '';
        }

        foreach ($this->modinfo->get_instances_of('lesson') as $lesson_instance) {
            // Skip if not visible to user or excluded by configuration
            if (!$lesson_instance->uservisible || !$this->should_include_activity($lesson_instance)) {
                continue;
            }

            // Check cache firs
            $cache_key = block_tutoring_machine_cache_manager::get_generic_content_key('lesson', $lesson_instance->instance);
            $cached_content = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_tutoring_machine_cache_manager::is_valid(block_tutoring_machine_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                $lessons_context .= $cached_content;
                continue;
            }

            // Get lesson record
            $lesson = $DB->get_record('lesson', ['id' => $lesson_instance->instance], '*', MUST_EXIST);
            if ($lesson) {
                // Start building lesson conten
                $lesson_content = "### Lektion: {$lesson->name}\n";

                if (!empty($lesson->intro)) {
                    $lesson_content .= "Einführung: " . strip_tags($lesson->intro) . "\n\n";
                }

                // Get lesson pages
                $pages = $DB->get_records('lesson_pages', ['lessonid' => $lesson->id]);

                foreach ($pages as $page) {
                    $lesson_content .= "- Seite: {$page->title}\n";

                    // Add page conten
                    if (!empty($page->contents)) {
                        $page_content = strip_tags($page->contents);
                        // Truncate if very long
                        if (strlen($page_content) > 500) {
                            $page_content = substr($page_content, 0, 500) . "...";
                        }
                        $lesson_content .= "  Inhalt: " . str_replace("\n", "\n  ", $page_content) . "\n";
                    }

                    // Get page answers/branches
                    $answers = $DB->get_records('lesson_answers', ['pageid' => $page->id]);
                    if (!empty($answers)) {
                        $lesson_content .= "  Optionen:\n";
                        foreach ($answers as $answer) {
                            $lesson_content .= "   - " . strip_tags($answer->answer) . "\n";
                            if (!empty($answer->response)) {
                                $lesson_content .= "     Antwort: " . strip_tags($answer->response) . "\n";
                            }
                        }
                    }
                }

                $lesson_content .= "\n";

                // Store in cache
                block_tutoring_machine_cache_manager::set(
                    block_tutoring_machine_cache_manager::CACHE_TYPE_MISC,
                    $cache_key,
                    $lesson_content,
                    [
                        'timemodified' => $lesson->timemodified,
                        'page_count' => count($pages)
                    ]
                );

                // Add to outpu
                $lessons_context .= $lesson_content;
            }
        }

        return $lessons_context;
    }

    /**
     * Extract content from configured external links.
     *
     * @return string
     */
    private function extract_context_links(): string {
        global $DB;

        if (empty($this->config['blockid'])) {
            return '';
        }

        $links = $DB->get_records('block_tutoring_machine_links', ['blockid' => $this->config['blockid']], 'id ASC');
        if (empty($links)) {
            return '';
        }

        $output = '';
        foreach ($links as $link) {
            \block_tutoring_machine\link_ingest::ensure_fresh($link);
            $fresh = $DB->get_record('block_tutoring_machine_links', ['id' => $link->id]);
            if (!$fresh || $fresh->status !== 'ok' || trim((string)$fresh->content) === '') {
                continue;
            }
            $content = trim($fresh->content);
            if ($content === '') {
                continue;
            }
            if ($output !== '') {
                $output .= "\n\n";
            }
            $output .= $content;
        }

        return $output;
    }
}
