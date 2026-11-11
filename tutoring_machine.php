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
require_once(__DIR__ . '/configloader.php');
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
    if (!block_tutoring_machine_check_rate_limit()) {
        throw new moodle_exception('toomanyrequests', 'error');
    }

    // Build the configured client and message array (shared with the streaming endpoint).
    $prepared = block_tutoring_machine_prepare_completion($message, $conversation_history, $course_id, $block_id);
    $client = $prepared->client;
    $messages = $prepared->messages;
    $model_to_use = $prepared->model;

    // Send request to API
    try {
        error_log("[CHATBOT DEBUG] Sending request to API using model: " . $model_to_use);
        $start_time = microtime(true);

        $response_text = $client->get_completion($messages, $prepared->attachments);

        $end_time = microtime(true);
        $time_taken = round($end_time - $start_time, 2);
        error_log("[CHATBOT DEBUG] API response received in " . $time_taken . " seconds");

        // Check for errors
        if ($response_text === false) {
            error_log("[CHATBOT ERROR] API returned false response");
            throw new moodle_exception('apiconnectionerror', 'block_tutoring_machine');
        }
    } catch (moodle_exception $e) {
        throw $e;
    } catch (Exception $e) {
        error_log("[CHATBOT ERROR] Exception during API request: " . $e->getMessage());
        throw new moodle_exception('apiconnectionerror', 'block_tutoring_machine');
    }

    // Replace activity reference tokens ([[akt:cmid]]) with real Moodle links.
    $response_text = block_tutoring_machine_message_processor::link_activity_references($response_text, $course_id);

    // Process and truncate response
    try {
        error_log("[CHATBOT DEBUG] Processing response text (length: " . strlen($response_text) . ")");
        $processor = new block_tutoring_machine_message_processor($response_text);

        $config = get_config('block_tutoring_machine');
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
            'content' => $response_text
        ];
    }

    // Debug mode: If debug=1 is provided, return the raw response for diagnostics
    if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
        // Create a diagnostic response
        $debug_response = "=== DEBUG INFORMATION ===\n\n";
        $debug_response .= "Model: " . $model_to_use . "\n";
        $debug_response .= "Course ID: " . ($course_id ?: 'Not set') . "\n";
        $debug_response .= "Block ID: " . ($block_id ?: 'Not set') . "\n";
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
        block_tutoring_machine_message_processor::send_response("Es tut mir leid, aber die Verbindung zum KI-Dienst konnte nicht hergestellt werden. Bitte überprüfe die API-Schlüssel in den Einstellungen und ob der ausgewählte Anbieter (OpenAI oder Google) verfügbar ist. Details für Administratoren wurden in die Moodle-Logs geschrieben.");
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
