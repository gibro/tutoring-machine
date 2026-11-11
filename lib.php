<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Library functions for the Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Basic PDF to text conversion class
 * This class provides a fallback method to extract text from PDFs when
 * other methods like pdftotext aren't available.
 */
class pdf2text {
    protected $filename;
    private $data;

    /**
     * Set filename to extract text from
     *
     * @param string $filename Path to PDF file
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * Decode the PDF file conten
     *
     * @return boolean Success or failure
     */
    public function decodePDF() {
        if (!file_exists($this->filename)) {
            return false;
        }

        $this->data = file_get_contents($this->filename);
        if (!$this->data) {
            return false;
        }

        return true;
    }

    /**
     * Extract text content from PDF
     * This is a simple implementation that extracts text objects
     * Note: This won't work on all PDFs, but serves as a basic fallback
     *
     * @return string Extracted tex
     */
    public function output() {
        if (empty($this->data)) {
            return '';
        }

        $text = '';

        // Extract text between BT and ET markers (Basic Text objects)
        preg_match_all('/(BT.*?ET)/s', $this->data, $textObjects);

        foreach ($textObjects[1] as $textObject) {
            // Extract text enclosed in parentheses or between < and >
            preg_match_all('/\(([^\)]*)\)/', $textObject, $matches);
            foreach ($matches[1] as $match) {
                $text .= $match . " ";
            }

            // Also try to extract hex encoded tex
            preg_match_all('/<([^>]*)>/', $textObject, $hexMatches);
            foreach ($hexMatches[1] as $match) {
                // Convert hex to ASCII
                $hex = str_replace(' ', '', $match);
                for ($i = 0; $i < strlen($hex); $i += 2) {
                    if (isset($hex[$i]) && isset($hex[$i+1])) {
                        $text .= chr(hexdec($hex[$i].$hex[$i+1]));
                    }
                }
                $text .= " ";
            }
        }

        // Clean up the tex
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}

/**
 * Function to determine if current user can access specified block instance
 *
 * @param int $blockid The block instance ID
 * @return bool True if user can access, false otherwise
 */
function block_tutoring_machine_can_access_block($blockid) {
    global $DB, $USER, $COURSE;

    // Get block instance
    $blockinstance = $DB->get_record('block_instances', array('id' => $blockid), '*', MUST_EXIST);
    if (!$blockinstance) {
        return false;
    }

    // Check if block is in a course contex
    $context = context::instance_by_id($blockinstance->parentcontextid);
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return false;
    }

    // Check if user is enrolled in the course
    $courseid = $context->instanceid;
    $coursecontext = context_course::instance($courseid);

    return is_enrolled($coursecontext, $USER->id);
}

// Keine pluginfile-Funktion mehr erforderlich,
// da wir direkt das feste Logo aus dem pix-Verzeichnis verwenden

/**
 * Add nodes to navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course objec
 * @param context $context The course contex
 */
function block_tutoring_machine_extend_navigation_course($navigation, $course, $context) {
    // Die Funktion ist jetzt leer, da wir keinen Link in der Kursnavigation hinzufügen möchten
    // Der Kontext-Viewer ist nur über die Blockeinstellungen erreichbar
}

/**
 * Add nodes to the settings navigation.
 *
 * @param settings_navigation $navigation The settings navigation objec
 * @param context $context The context of the course
 */
function block_tutoring_machine_extend_settings_navigation($navigation, $context) {
    global $CFG, $PAGE;

    // Only add settings if in course contex
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return;
    }

    // Check if user has capability to view settings
    // Check for either the Tutoring Machine view capability or basic course view capability
    if (has_capability('block/tutoring_machine:view', $context) || has_capability('moodle/course:view', $context)) {
        // Find the course administration node
        if ($coursenode = $navigation->get('courseadmin')) {
            // Add link to the context viewer
            $url = new moodle_url($CFG->wwwroot . '/blocks/tutoring_machine/view_context.php',
                array('courseid' => $PAGE->course->id));

            $coursenode->add(
                get_string('contextsources', 'block_tutoring_machine'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'tutoring_machine_context',
                new pix_icon('i/info', '')
            );
        }
    }
}

/**
 * Resolve the API key for the configured default provider.
 *
 * @return string The API key
 * @throws moodle_exception If no API key is configured
 */
function block_tutoring_machine_get_api_key() {
    $config = get_config('block_tutoring_machine');

    $default_provider = isset($config->default_provider) ? $config->default_provider : 'openai';

    $api_key = '';
    switch ($default_provider) {
        case 'google':
            $api_key = isset($config->google_apikey) ? trim($config->google_apikey) : '';
            break;
        case 'openai':
        default:
            $api_key = isset($config->openai_apikey) ? trim($config->openai_apikey) : '';
            break;
    }

    if (!empty($api_key)) {
        return $api_key;
    }

    // Legacy fallback for old setups: check for deprecated apikey field.
    if (isset($config->apikey) && !empty($config->apikey)) {
        return trim($config->apikey);
    }

    // Fallback: read key from file (for backward compatibility).
    $key_file_path = __DIR__ . '/your-secret-key.txt';
    if (file_exists($key_file_path)) {
        $api_key_content = file_get_contents($key_file_path);
        if (!empty($api_key_content)) {
            return trim($api_key_content);
        }
    }

    throw new moodle_exception('noapikey', 'block_tutoring_machine');
}

/**
 * Check whether the current user is within the per-hour request rate limit.
 *
 * @return bool True if the request is allowed, false if the limit is exceeded
 */
function block_tutoring_machine_check_rate_limit() {
    global $SESSION;

    $max_requests = 30;      // Maximum number of requests.
    $time_window = 3600;     // Time window in seconds (1 hour).
    $now = time();

    if (!isset($SESSION->tutoring_machine_requests)) {
        $SESSION->tutoring_machine_requests = array();
    }

    // Remove requests outside the time window.
    $SESSION->tutoring_machine_requests = array_filter(
        $SESSION->tutoring_machine_requests,
        function($timestamp) use ($now, $time_window) {
            return ($now - $timestamp) < $time_window;
        }
    );

    if (count($SESSION->tutoring_machine_requests) >= $max_requests) {
        return false;
    }

    $SESSION->tutoring_machine_requests[] = $now;
    return true;
}

/**
 * Build a configured API client and the message array for a chat request.
 *
 * Used by the AJAX chat endpoint (tutoring_machine.php) to keep context
 * gathering, model selection and parameter handling in one place.
 *
 * @param string $message The current user message
 * @param array $conversation_history Prior conversation messages
 * @param int|null $course_id Course identifier (validated by caller)
 * @param int|null $block_id Block instance identifier (validated by caller)
 * @return stdClass Object with ->client, ->messages and ->model
 * @throws moodle_exception On configuration errors
 */
function block_tutoring_machine_prepare_completion($message, $conversation_history, $course_id, $block_id) {
    global $DB;

    require_once(__DIR__ . '/classes/api_client.php');
    require_once(__DIR__ . '/classes/content_extractor.php');
    require_once(__DIR__ . '/classes/message_processor.php');

    // Get metaprompt if available for this block instance.
    $metaprompt = block_tutoring_machine_message_processor::get_metaprompt($block_id);

    // Get API key from plugin settings.
    $api_key = block_tutoring_machine_get_api_key();

    // Load global and block-specific configuration to determine provider and model.
    $config = get_config('block_tutoring_machine');
    $block_config_settings = null;
    if (!empty($block_id)) {
        $block_record = $DB->get_record('block_instances', array('id' => $block_id), '*');
        if ($block_record && $block_record->blockname === 'tutoring_machine') {
            $block_config_settings = unserialize(base64_decode($block_record->configdata));
        }
    }

    $model_to_use = !empty($config->default_model) ? $config->default_model : 'openai:gpt-5.5';
    if (!empty($block_config_settings) && !empty($block_config_settings->ai_model)) {
        $model_to_use = $block_config_settings->ai_model;
    }

    $provider = 'openai';
    if (strpos($model_to_use, ':') !== false) {
        list($provider) = explode(':', $model_to_use, 2);
    } else {
        $provider = !empty($config->default_provider) ? $config->default_provider : 'openai';
        $model_to_use = $provider . ':' . $model_to_use;
    }

    $include_context = !empty($config->default_include_context);
    if (!empty($block_config_settings) && property_exists($block_config_settings, 'include_context')) {
        $include_context = (bool)$block_config_settings->include_context;
    }

    // Determine how the uploaded OKF knowledge base should be provided to the AI.
    // The OpenAI vector store is only available for OpenAI models; any other
    // provider falls back to inline prompt injection.
    $knowledgebase_storage = 'inline';
    if (!empty($block_config_settings) && !empty($block_config_settings->knowledgebase_storage)) {
        $knowledgebase_storage = $block_config_settings->knowledgebase_storage;
    }
    if ($knowledgebase_storage === 'openai_vectorstore' && $provider !== 'openai') {
        $knowledgebase_storage = 'inline';
    }

    // Build the system message from course context.
    $system_message = '';
    $content_instructions = '';
    $metaprompt_used = false;
    $knowledgebase_attachments = [];

    if (!empty($course_id)) {
        if ($include_context) {
            $block_config = $block_config_settings ?: new stdClass();
            $block_config->blockid = $block_id;
            $block_config->knowledgebase_storage = $knowledgebase_storage;
            try {
                $extractor = new block_tutoring_machine_content_extractor($course_id, $block_config);
                if ($knowledgebase_storage === 'openai_vectorstore') {
                    $extractor->enable_file_upload_mode();
                }
                $course_content = $extractor->get_context();
                if ($knowledgebase_storage === 'openai_vectorstore') {
                    $knowledgebase_attachments = $extractor->get_files_for_upload();
                }

                if (!empty($course_content)) {
                    $content_parts = explode('# WICHTIG: Strikte Informationsquellen', $course_content);
                    $content_parts_alt = explode('# Internetsuche', $course_content);

                    if (count($content_parts) > 1) {
                        $content_instructions = "# WICHTIG: Strikte Informationsquellen" . $content_parts[1];
                        $pure_content = $content_parts[0];
                    } else if (count($content_parts_alt) > 1) {
                        $content_instructions = "# Internetsuche" . $content_parts_alt[1];
                        $pure_content = $content_parts_alt[0];
                    } else {
                        $pure_content = $course_content;
                    }

                    if (!empty($metaprompt)) {
                        $system_message = $metaprompt;
                        $metaprompt_used = true;
                        $system_message .= "\n\n# Kursinhalte als Kontext\n\n";
                        $system_message .= $pure_content;
                    } else {
                        $system_message = $course_content;
                    }
                } else {
                    $use_internet = isset($block_config->use_internet) ? (bool)$block_config->use_internet : false;

                    if (!$use_internet) {
                        $content_instructions = "# WICHTIG: Strikte Informationsquellen\n";
                        $content_instructions .= "Beantworte Fragen AUSSCHLIESSLICH basierend auf den gegebenen Kursinhalten. Da aktuell keine Kursinhalte verfügbar sind, antworte: \"Entschuldigung, aber ich kann aktuell keine Kursinhalte finden. Bitte wende dich an deinen Kursleiter für Unterstützung.\"\n\n";
                        $content_instructions .= "Verwende NIEMALS dein eigenes Wissen oder externe Quellen für die Beantwortung.\n\n";

                        if (!empty($metaprompt)) {
                            $system_message = $metaprompt;
                            $metaprompt_used = true;
                            $system_message .= "\n\n" . $content_instructions;
                        } else {
                            $system_message = $content_instructions;
                        }
                    } else if (!empty($metaprompt)) {
                        $system_message = $metaprompt;
                        $metaprompt_used = true;
                    }
                }
            } catch (Exception $e) {
                $error_message = "# WICHTIG: Fehler beim Laden der Kursinhalte\n";
                $error_message .= "Beim Laden der Kursinhalte ist ein Fehler aufgetreten. Bitte entschuldige diese Störung und verweise den Benutzer an den Kursleiter oder den Support.\n\n";

                if (!empty($metaprompt)) {
                    $system_message = $metaprompt;
                    $metaprompt_used = true;
                    $system_message .= "\n\n" . $error_message;
                } else {
                    $system_message = $error_message;
                }
            }
        } else if (!empty($metaprompt)) {
            $system_message = $metaprompt;
            $metaprompt_used = true;
        }
    } else if (!empty($metaprompt)) {
        $system_message = $metaprompt;
        $metaprompt_used = true;
    }

    // Make sure source instructions always take precedence by re-adding them last.
    if (!empty($content_instructions) && $metaprompt_used) {
        $system_message .= "\n\n" . $content_instructions;
    }

    // Assemble the message array for the API.
    $messages = array();
    if (!empty($system_message)) {
        $messages[] = array('role' => 'system', 'content' => $system_message);
    }
    if (!empty($conversation_history) && is_array($conversation_history)) {
        foreach ($conversation_history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = array('role' => $msg['role'], 'content' => $msg['content']);
            }
        }
    }
    $messages[] = array('role' => 'user', 'content' => $message);

    // Create and configure the API client.
    $client = block_tutoring_machine_api_client::create($api_key, $model_to_use);

    if (isset($config->temperature)) {
        $client->set_temperature((float)$config->temperature);
    }
    if (isset($config->top_p)) {
        $client->set_top_p((float)$config->top_p);
    }
    if (isset($config->max_tokens)) {
        $client->set_max_tokens((int)$config->max_tokens);
    }
    if (isset($config->timeout)) {
        $client->set_timeout((int)$config->timeout);
    }
    if (isset($config->response_format)) {
        $client->set_response_format($config->response_format);
    }

    $result = new stdClass();
    $result->client = $client;
    $result->messages = $messages;
    $result->model = $model_to_use;
    $result->attachments = $knowledgebase_attachments;
    return $result;
}