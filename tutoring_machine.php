<?php
// This file is part of Moodle - http://moodle.org/
/**
 * AJAX handler for the Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Set security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
header('X-XSS-Protection: 1; mode=block');

// AJAX-Skript – stellt sicher, dass nur AJAX-Aufrufe erlaubt sind.
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/api_client.php');
require_once(__DIR__ . '/classes/content_extractor.php');
require_once(__DIR__ . '/classes/message_processor.php');

// Require login
require_login();

// Security check - verify this is an AJAX reques
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    header('HTTP/1.0 403 Forbidden');
    die('Direkter Zugriff nicht erlaubt.');
}


// Additional CSRF protection using sesskey
// Stricter implementation to require sesskey for all requests
if (!isset($_POST['sesskey']) && !isset($_GET['sesskey'])) {
    header('HTTP/1.0 403 Forbidden');
    $error = ['error' => true, 'message' => get_string('csrfcheck', 'block_tutoring_machine')];
    echo json_encode($error);
    exit;
}

if (!confirm_sesskey()) {
    header('HTTP/1.0 403 Forbidden');
    $error = ['error' => true, 'message' => get_string('csrfcheck', 'block_tutoring_machine')];
    echo json_encode($error);
    exit;
}

defined('MOODLE_INTERNAL') || die();

try {
    // Get and validate request parameters
    $message = block_tutoring_machine_message_processor::get_message_from_request();
    $conversation_history = block_tutoring_machine_message_processor::get_conversation_history_from_request();
    $course_id = block_tutoring_machine_message_processor::get_course_id_from_request();
    $block_id = block_tutoring_machine_message_processor::get_block_id_from_request();

    // Validate the message
    if (empty($message)) {
        throw new moodle_exception('missingparam', 'error', '', 'message');
    }

    // Rate limiting - check if user has made too many requests
    if (!check_rate_limit()) {
        throw new moodle_exception('toomanyrequests', 'error');
    }

    // Get metaprompt if available for this block instance
    $metaprompt = block_tutoring_machine_message_processor::get_metaprompt($block_id);

    // Get API keys from plugin settings
    $api_key = get_api_key();

    // Load global and block-specific configuration to determine provider and model
    $config = get_config('block_tutoring_machine');
    $block_config_settings = null;
    if (!empty($block_id)) {
        global $DB;
        $block_record = $DB->get_record('block_instances', ['id' => $block_id], '*');
        if ($block_record && $block_record->blockname === 'tutoring_machine') {
            $block_config_settings = unserialize(base64_decode($block_record->configdata));
        }
    }

    $model_to_use = !empty($config->default_model) ? $config->default_model : 'openai:gpt-5';
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

    $file_context_items = [];
    $include_context = !empty($config->default_include_context);
    if (!empty($block_config_settings) && property_exists($block_config_settings, 'include_context')) {
        $include_context = (bool)$block_config_settings->include_context;
    }

    $should_upload_files = ($provider === 'openai'
        && $include_context
        && !empty($block_config_settings)
        && !empty($block_config_settings->use_specific_activities));

    // Get context from course conten
    $system_message = '';
    $content_instructions = '';
    $metaprompt_used = false;

    // Add course content as context if course ID is provided
    if (!empty($course_id)) {
        if ($include_context) {
            $block_config = $block_config_settings ?: new stdClass();
            $block_config->blockid = $block_id;
            try {
                // Extract course conten
                $extractor = new block_tutoring_machine_content_extractor($course_id, $block_config);
                if ($should_upload_files) {
                    $extractor->enable_file_upload_mode();
                }
                $course_content = $extractor->get_context();
                if ($should_upload_files) {
                    $file_context_items = $extractor->get_files_for_upload();
                }

                if (!empty($course_content)) {
                    // Parse out the instructions part from the contex
                    $content_parts = explode('# WICHTIG: Strikte Informationsquellen', $course_content);
                    $content_parts_alt = explode('# Internetsuche', $course_content);

                    if (count($content_parts) > 1) {
                        // Found strict instructions
                        $content_instructions = "# WICHTIG: Strikte Informationsquellen" . $content_parts[1];
                        $pure_content = $content_parts[0];
                    } else if (count($content_parts_alt) > 1) {
                        // Found internet search instructions
                        $content_instructions = "# Internetsuche" . $content_parts_alt[1];
                        $pure_content = $content_parts_alt[0];
                    } else {
                        // No instructions found
                        $pure_content = $course_content;
                    }

                    // Build the system message
                    if (!empty($metaprompt)) {
                        $system_message = $metaprompt;
                        $metaprompt_used = true;
                        $system_message .= "\n\n# Kursinhalte als Kontext\n\n";
                        $system_message .= $pure_content;
                    } else {
                        $system_message = $course_content; // Use the full content including instructions
                    }
                } else {
                    // Even if no content was extracted, add a strict instruction when internet search is disabled
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
                        // Internet allowed, but no content, use metapromp
                        $system_message = $metaprompt;
                        $metaprompt_used = true;
                    }
                }
            } catch (Exception $e) {
                // Add fallback instruction if content extraction fails
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
        // No course ID, but metaprompt exists
        $system_message = $metaprompt;
        $metaprompt_used = true;
    }

    // Make sure we always add source instructions at the end to ensure they take precedence
    if (!empty($content_instructions) && $metaprompt_used) {
        // Re-add the content instructions at the end to ensure they are respected
        $system_message .= "\n\n" . $content_instructions;
    }

    // Prepare messages array for the API
    $messages = [];

    // Add system message if available
    if (!empty($system_message)) {
        $messages[] = [
            'role' => 'system',
            'content' => $system_message
        ];
    }

    // Add conversation history
    if (!empty($conversation_history) && is_array($conversation_history)) {
        foreach ($conversation_history as $msg) {
            if (isset($msg['role']) && isset($msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
    }

    // Add current user message
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];

    // Create API client with the factory using the selected model
    try {
        // Verbose logging for debugging
        error_log("[CHATBOT DEBUG] Using model: " . $model_to_use);
        error_log("[CHATBOT DEBUG] API key length: " . (strlen($api_key) > 0 ? strlen($api_key) : "Empty"));

        $client = block_tutoring_machine_api_client::create($api_key, $model_to_use);
    } catch (Exception $e) {
        error_log("[CHATBOT ERROR] Failed to create API client: " . $e->getMessage());
        throw new moodle_exception('apiconnectionerror', 'block_tutoring_machine');
    }

    // Set parameters if configured
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

    if (!empty($file_context_items)) {
        $maxfiles = 2;
        $maxbytes = 5 * 1024 * 1024; // ~5MB total
        $limited = [];
        $totalbytes = 0;

        foreach ($file_context_items as $item) {
            $size = 0;
            if (!empty($item['file']) && $item['file'] instanceof stored_file) {
                $size = (int)$item['file']->get_filesize();
            }

            if (count($limited) >= $maxfiles) {
                error_log('[CHATBOT WARNING] Skipping additional context file ' . ($item['filename'] ?? 'unknown') . ' (max file count reached).');
                continue;
            }

            if ($maxbytes > 0 && ($totalbytes + $size) > $maxbytes) {
                error_log('[CHATBOT WARNING] Skipping context file ' . ($item['filename'] ?? 'unknown') . ' to stay inside upload size limits.');
                continue;
            }

            $limited[] = $item;
            $totalbytes += $size;
        }

        $file_context_items = $limited;
    }

    if (!empty($file_context_items)) {
        // Current OpenAI responses limits do not tolerate large binary uploads well.
        error_log('[CHATBOT WARNING] Skipping all file uploads to avoid model context limits.');
        $file_context_items = [];
    }

    // Send request to API
    try {
        error_log("[CHATBOT DEBUG] Sending request to API...");
        $start_time = microtime(true);

        $response_text = $client->get_completion($messages, $file_context_items);

        $end_time = microtime(true);
        $time_taken = round($end_time - $start_time, 2);
        error_log("[CHATBOT DEBUG] API response received in " . $time_taken . " seconds");

        // Check for errors
        if ($response_text === false) {
            error_log("[CHATBOT ERROR] API returned false response");
            throw new moodle_exception('apiconnectionerror', 'block_tutoring_machine');
        }
    } catch (Exception $e) {
        error_log("[CHATBOT ERROR] Exception during API request: " . $e->getMessage());
        throw new moodle_exception('apiconnectionerror', 'block_tutoring_machine');
    }

    // Process and truncate response
    try {
        error_log("[CHATBOT DEBUG] Processing response text (length: " . strlen($response_text) . ")");
        $processor = new block_tutoring_machine_message_processor($response_text);

        $primary_limit = 8000;
        $secondary_limit = 10000;
        if (isset($config->max_tokens) && (int)$config->max_tokens > 0) {
            $approx_chars = (int)$config->max_tokens * 4; // Roughly 4 characters per token.
            $approx_chars = max($primary_limit, $approx_chars);
            $approx_chars = min($approx_chars, 24000);
            $primary_limit = $approx_chars;
            $secondary_limit = min($primary_limit + 2000, 26000);
        }

        $processor->truncate_response($primary_limit, $secondary_limit);
        $response_text = $processor->get_response_text();
        $last_message = $processor->get_assistant_message();
        error_log("[CHATBOT DEBUG] Response processed successfully");
    } catch (Exception $e) {
        error_log("[CHATBOT ERROR] Error processing response: " . $e->getMessage());
        // Fallback to raw response if processing fails
        $response_text = "Es tut mir leid, aber es gab ein Problem beim Verarbeiten der Antwort. Bitte versuche es erneut.";
        $last_message = [
            'role' => 'assistant',
            'content' => $response_tex
        ];
    }

    // Debug mode: If debug=1 is provided, return the raw response for diagnostics
    if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
        // Create a diagnostic response
        $debug_response = "=== DEBUG INFORMATION ===\n\n";
        $debug_response .= "API Provider: " . $default_provider . "\n";
        $debug_response .= "Model: " . $model_to_use . "\n";
        $debug_response .= "Course ID: " . ($course_id ?: 'Not set') . "\n";
        $debug_response .= "Block ID: " . ($block_id ?: 'Not set') . "\n";
        $debug_response .= "System message length: " . (isset($system_message) ? strlen($system_message) : 0) . " chars\n";
        $debug_response .= "User message: " . substr($message, 0, 50) . "...\n\n";
        $debug_response .= "Raw API response:\n" . substr($response_text, 0, 1000) . "...";

        $response_text = $debug_response;
        $last_message = [
            'role' => 'assistant',
            'content' => $debug_response
        ];
    }

    // Log query for analytics if analytics are enabled
    if (!empty($course_id) && !empty($block_id)) {
        try {
            require_once(__DIR__ . '/classes/analytics_logger.php');
            block_tutoring_machine_analytics_logger::log_query($message, $course_id, $block_id, $model_to_use);
        } catch (Exception $analytics_error) {
            // Just log the error but don't interrupt the main flow
        }
    }

    // Send response
    block_tutoring_machine_message_processor::send_response($response_text, $last_message);

} catch (moodle_exception $e) {
    // For critical errors, keep minimal logging
    error_log("Tutoring Machine error: " . $e->getMessage());

    // Provide more helpful error message based on error type
    $error_type = $e->getMessage();

    if ($error_type === 'apiconnectionerror') {
        block_tutoring_machine_message_processor::send_response("Es tut mir leid, aber die Verbindung zum KI-Dienst konnte nicht hergestellt werden. Bitte überprüfe die API-Schlüssel in den Einstellungen und ob die ausgewählte KI (OpenAI, Google, Anthropic) verfügbar ist. Details für Administratoren wurden in die Moodle-Logs geschrieben.");
    } else if ($error_type === 'noapikey') {
        block_tutoring_machine_message_processor::send_response("Es wurde kein API-Schlüssel für den ausgewählten KI-Dienst konfiguriert. Bitte kontaktiere den Administrator, um den API-Schlüssel für den gewählten Anbieter einzurichten.");
    } else {
        block_tutoring_machine_message_processor::send_response("Es tut mir leid, aber ich konnte deine Anfrage nicht verarbeiten: " . $e->getMessage() . "\n\nBitte kontaktiere den Administrator, wenn dieser Fehler weiterhin auftritt.");
    }
    exit;
} catch (Exception $e) {
    // For unexpected errors, keep minimal logging
    error_log("Unexpected error in Tutoring Machine: " . $e->getMessage());
    block_tutoring_machine_message_processor::send_response("Es tut mir leid, aber ein unerwarteter Fehler ist aufgetreten. Bitte versuche es später noch einmal oder kontaktiere den Administrator. Details für Administratoren wurden in die Moodle-Logs geschrieben.");
    exit;
}

/**
 * Get API key from plugin settings based on default provider or fallback file
 *
 * @return string The API key
 * @throws moodle_exception If API key is not configured
 */
function get_api_key() {
    // Get configuration
    $config = get_config('block_tutoring_machine');

    // Get the default provider
    $default_provider = isset($config->default_provider) ? $config->default_provider : 'openai';

    // Get the appropriate API key based on provider
    $api_key = '';

    switch ($default_provider) {
        case 'google':
            $api_key = isset($config->google_apikey) ? trim($config->google_apikey) : '';
            break;

        case 'anthropic':
            $api_key = isset($config->anthropic_apikey) ? trim($config->anthropic_apikey) : '';
            break;

        case 'openai':
        default:
            $api_key = isset($config->openai_apikey) ? trim($config->openai_apikey) : '';
            break;
    }

    // Check if API key is configured
    if (!empty($api_key)) {
        return $api_key;
    }

    // Legacy fallback for old setups: check for deprecated apikey field
    if (isset($config->apikey) && !empty($config->apikey)) {
        return trim($config->apikey);
    }

    // Fallback: Try to read key from file (for backward compatibility)
    $key_file_path = __DIR__ . '/your-secret-key.txt';
    if (file_exists($key_file_path)) {
        $api_key_content = file_get_contents($key_file_path);
        if (!empty($api_key_content)) {
            return trim($api_key_content);
        }
    }

    // If still no key available, throw exception
    throw new moodle_exception('noapikey', 'block_tutoring_machine');
}

/**
 * Check if user has exceeded rate limi
 * Uses a simple approach that stores timestamps in user sessions
 *
 * @return bool True if user is within rate limit, false otherwise
 */
function check_rate_limit() {
    global $USER, $SESSION;

    // Define rate limits
    $max_requests = 30;      // Maximum number of requests
    $time_window = 3600;     // Time window in seconds (1 hour)

    // Get current time
    $now = time();

    // Initialize request history in session if not se
    if (!isset($SESSION->tutoring_machine_requests)) {
        $SESSION->tutoring_machine_requests = [];
    }

    // Remove old requests outside the time window
    $SESSION->tutoring_machine_requests = array_filter($SESSION->tutoring_machine_requests, function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });

    // Check if user has exceeded rate limi
    if (count($SESSION->tutoring_machine_requests) >= $max_requests) {
        return false;
    }

    // Add current request to the lis
    $SESSION->tutoring_machine_requests[] = $now;

    return true;
}
