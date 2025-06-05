<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Content extractor class for Chatbot block.
 *
 * @package    block_chatbot
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/lib/modinfolib.php');
require_once($CFG->dirroot.'/lib/filelib.php');
require_once($CFG->dirroot.'/blocks/chatbot/lib.php');

/**
 * Class to extract content from course resources for use as context
 * 
 * This class extracts content from various Moodle resource types such as text pages,
 * glossaries, PDFs, forums, etc. and provides it as a structured context for the AI.
 * It includes a robust caching system to improve performance.
 */
class block_chatbot_content_extractor {
    /** @var object $course The course object */
    private $course;

    /** @var array $config Configuration settings */
    private $config;

    /** @var string $context Extracted context content */
    private $context = '';

    /** @var object $modinfo Course module information */
    private $modinfo;

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

        // Set default configuration
        $this->config = [
            'use_textpages' => true,
            'use_glossaries' => true,
            'use_internet' => false,
            'use_h5p' => true,
            'use_pdfs' => true,
            'use_forums' => true,
            'use_quizzes' => true,
            'use_books' => true,
            'use_assignments' => true,
            'use_labels' => true,
            'use_urls' => true,
            'use_lessons' => true
        ];

        // Override with provided configuration
        if ($config) {
            $config_array = (array)$config;
            foreach ($config_array as $key => $value) {
                $setting_key = preg_replace('/^config_/', '', $key);
                if (array_key_exists($setting_key, $this->config)) {
                    $this->config[$setting_key] = (bool)$value;
                }
            }
        }

        // Get module information
        $this->modinfo = get_fast_modinfo($this->course);
    }

    /**
     * Get context from all enabled sources with caching
     *
     * @return string The formatted contex
     */
    public function get_context() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

        // Try to get from cache firs
        $cache_key = block_chatbot_cache_manager::get_course_content_key($this->course->id, $this->config);
        $cached_context = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_COURSE, $cache_key);

        // Return cached context if it's valid
        if ($cached_context && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_COURSE, $cache_key)) {
            // Check if we need to update the information sources instructions
            if ((strpos($cached_context, '# Internetsuche') !== false && !$this->config['use_internet']) ||
                (strpos($cached_context, '# WICHTIG: Strikte Informationsquellen') !== false && $this->config['use_internet'])) {
                error_log("Cache contains outdated internet search settings - regenerating");
                // Internet setting has changed, regenerate context
            } else {
                return $cached_context;
            }
        }

        // If not cached or expired, generate the context
        $sections = [];

        // Process text pages
        if ($this->config['use_textpages']) {
            $pages_context = $this->extract_text_pages();
            if (!empty($pages_context)) {
                $sections['textpages'] = [
                    'title' => get_string('use_textpages', 'block_chatbot'),
                    'content' => $pages_context
                ];
            }
        }

        // Process glossaries
        if ($this->config['use_glossaries']) {
            $glossaries_context = $this->extract_glossaries();
            if (!empty($glossaries_context)) {
                $sections['glossaries'] = [
                    'title' => get_string('use_glossaries', 'block_chatbot'),
                    'content' => $glossaries_context
                ];
            }
        }

        // Process H5P activities
        if ($this->config['use_h5p']) {
            $h5p_context = $this->extract_h5p_activities();
            if (!empty($h5p_context)) {
                $sections['h5p'] = [
                    'title' => get_string('use_h5p', 'block_chatbot'),
                    'content' => $h5p_context
                ];
            }
        }

        // Process PDF documents
        if ($this->config['use_pdfs']) {
            $pdf_context = $this->extract_pdf_documents();
            if (!empty($pdf_context)) {
                $sections['pdfs'] = [
                    'title' => get_string('use_pdfs', 'block_chatbot'),
                    'content' => $pdf_context
                ];
            }
        }

        // Process forum discussions
        if ($this->config['use_forums']) {
            $forums_context = $this->extract_forum_discussions();
            if (!empty($forums_context)) {
                $sections['forums'] = [
                    'title' => get_string('use_forums', 'block_chatbot'),
                    'content' => $forums_context
                ];
            }
        }

        // Process quizzes
        if ($this->config['use_quizzes']) {
            $quizzes_context = $this->extract_quiz_questions();
            if (!empty($quizzes_context)) {
                $sections['quizzes'] = [
                    'title' => get_string('use_quizzes', 'block_chatbot'),
                    'content' => $quizzes_context
                ];
            }
        }

        // Process books
        if ($this->config['use_books']) {
            $books_context = $this->extract_book_chapters();
            if (!empty($books_context)) {
                $sections['books'] = [
                    'title' => get_string('use_books', 'block_chatbot'),
                    'content' => $books_context
                ];
            }
        }

        // Process assignments
        if ($this->config['use_assignments']) {
            $assignments_context = $this->extract_assignments();
            if (!empty($assignments_context)) {
                $sections['assignments'] = [
                    'title' => get_string('use_assignments', 'block_chatbot'),
                    'content' => $assignments_context
                ];
            }
        }

        // Process labels
        if ($this->config['use_labels']) {
            $labels_context = $this->extract_labels();
            if (!empty($labels_context)) {
                $sections['labels'] = [
                    'title' => get_string('use_labels', 'block_chatbot'),
                    'content' => $labels_context
                ];
            }
        }

        // Process URLs
        if ($this->config['use_urls']) {
            $urls_context = $this->extract_urls();
            if (!empty($urls_context)) {
                $sections['urls'] = [
                    'title' => get_string('use_urls', 'block_chatbot'),
                    'content' => $urls_context
                ];
            }
        }

        // Process lessons
        if ($this->config['use_lessons']) {
            $lessons_context = $this->extract_lessons();
            if (!empty($lessons_context)) {
                $sections['lessons'] = [
                    'title' => get_string('use_lessons', 'block_chatbot'),
                    'content' => $lessons_context
                ];
            }
        }

        // Combine all sections into a structured context
        $context = '';

        if (!empty($sections)) {
            $context = "# Kursinhalte als Kontext\n\n";

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

        // Cache the generated contex
        block_chatbot_cache_manager::set(block_chatbot_cache_manager::CACHE_TYPE_COURSE, $cache_key, $context);

        return $context;
    }

    /**
     * Extract content from text pages with caching
     *
     * @return string Formatted content from text pages
     */
    private function extract_text_pages() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

        $pages_context = '';

        foreach ($this->modinfo->get_instances_of('page') as $page_instance) {
            // Skip if not visible to user
            if (!$page_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_page_content_key($page_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_PAGE, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_PAGE, $cache_key)) {
                $pages_context .= $cached_content;
                continue;
            }

            // Get page record
            $page = $DB->get_record('page', ['id' => $page_instance->instance], '*', MUST_EXIST);
            if ($page) {
                // Format the page conten
                $page_content = "### Textseite: {$page->name}\n";
                $page_content .= strip_tags($page->content) . "\n\n";

                // Store in cache
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_PAGE,
                    block_chatbot_cache_manager::get_page_content_key($page->id),
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

        $glossaries_context = '';

        foreach ($this->modinfo->get_instances_of('glossary') as $glossary_instance) {
            // Skip if not visible to user
            if (!$glossary_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_glossary_content_key($glossary_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_GLOSSARY, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_GLOSSARY, $cache_key)) {
                $glossaries_context .= $cached_content;
                continue;
            }

            // Get glossary record
            $glossary = $DB->get_record('glossary', ['id' => $glossary_instance->instance], '*', MUST_EXIST);
            if ($glossary) {
                // Start building glossary conten
                $glossary_content = "### Glossar: {$glossary->name}\n";

                // Get approved entries
                $entries = $DB->get_records('glossary_entries', ['glossaryid' => $glossary->id, 'approved' => 1]);

                foreach ($entries as $entry) {
                    $glossary_content .= "- Begriff: {$entry->concept}\n";
                    $glossary_content .= "  Definition: " . strip_tags($entry->definition) . "\n";
                }

                $glossary_content .= "\n";

                // Store in cache with timemodified and entry count as metadata
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_GLOSSARY,
                    block_chatbot_cache_manager::get_glossary_content_key($glossary->id),
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$h5p_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_h5p_content_key($h5p_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_H5P, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_H5P, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_H5P,
                    block_chatbot_cache_manager::get_h5p_content_key($h5p->id),
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
            // Skip if not visible to user
            if (!$resource_instance->uservisible) {
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

            // Extract PDF conten
            $pdf_content = $this->extract_pdf_content($file);
            if (!empty($pdf_content)) {
                $pdf_context .= "### PDF-Dokument: {$resource->name}\n";
                $pdf_context .= $pdf_content . "\n\n";
            }
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

        // Check cache firs
        $content_hash = $file->get_contenthash();
        $timemodified = $file->get_timemodified();

        // Try to get from cache using the cache manager
        $cache_key = block_chatbot_cache_manager::get_pdf_content_key($content_hash);
        $cached_item = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_PDF, $cache_key);

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
        $tempdir = $CFG->tempdir . '/chatbot';
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

                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_PDF,
                    block_chatbot_cache_manager::get_pdf_content_key($content_hash),
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
     * Extract content from forum discussions with caching
     *
     * @return string Formatted content from forums
     */
    private function extract_forum_discussions() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$forum_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_generic_content_key('forum', $forum_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$quiz_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_generic_content_key('quiz', $quiz_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$book_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_generic_content_key('book', $book_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$assign_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_generic_content_key('assign', $assign_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$label_instance->uservisible) {
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
                $cache_key = block_chatbot_cache_manager::get_generic_content_key('label', $label_instance->instance);
                $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

                // If valid cache exists, use i
                if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
                    $section_content .= $cached_content;
                    continue;
                }

                // Get label record
                $label = $DB->get_record('label', ['id' => $label_instance->instance], '*', MUST_EXIST);
                if ($label) {
                    // Extract content from label
                    $label_content = "- Hinweis: " . strip_tags($label->intro) . "\n";

                    // Store in cache
                    block_chatbot_cache_manager::set(
                        block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$url_instance->uservisible) {
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
                $cache_key = block_chatbot_cache_manager::get_generic_content_key('url', $url_instance->instance);
                $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

                // If valid cache exists, use i
                if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                    block_chatbot_cache_manager::set(
                        block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
        require_once($CFG->dirroot . '/blocks/chatbot/classes/cache_manager.php');

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
            // Skip if not visible to user
            if (!$lesson_instance->uservisible) {
                continue;
            }

            // Check cache firs
            $cache_key = block_chatbot_cache_manager::get_generic_content_key('lesson', $lesson_instance->instance);
            $cached_content = block_chatbot_cache_manager::get(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key);

            // If valid cache exists, use i
            if ($cached_content && block_chatbot_cache_manager::is_valid(block_chatbot_cache_manager::CACHE_TYPE_MISC, $cache_key)) {
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
                block_chatbot_cache_manager::set(
                    block_chatbot_cache_manager::CACHE_TYPE_MISC,
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
}