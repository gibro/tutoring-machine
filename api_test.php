<?php
// This file is part of Moodle - http://moodle.org/
/**
 * API Test Script for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This script can be run from the command line
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/api_client.php');

// Create log directory if needed
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}
$log_file = $log_dir . '/api_test_' . date('Y-m-d_H-i-s') . '.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    echo $formatted;
}

// Initialize log file
file_put_contents($log_file, "=== Tutoring Machine API Test Log ===\n" . date('Y-m-d H:i:s') . "\n\n");

// Get configuration
$config = get_config('block_tutoring_machine');

// Determine which provider to tes
$provider = isset($argv[1]) ? $argv[1] : 'all';
log_message("Testing provider: $provider");

// Test message
$system_message = "You are a helpful assistant. Keep your response short and to the point.";
$user_message = "Say hello and introduce yourself in one sentence.";

// Test OpenAI
if ($provider === 'all' || $provider === 'openai') {
    log_message("=== Testing OpenAI API ===");

    if (empty($config->openai_apikey)) {
        log_message("ERROR: OpenAI API key not configured");
    } else {
        try {
            log_message("Creating OpenAI client with API key (length: " . strlen($config->openai_apikey) . ")");
            $client = new block_tutoring_machine_openai_client($config->openai_apikey);

            $models = ['gpt-5', 'gpt-5-mini', 'gpt-5-nano'];

            foreach ($models as $model) {
                log_message("Testing model: $model");
                $client->set_model($model);
                $client->set_max_tokens(100);
                $client->set_reasoning_effort('medium');
                $client->set_text_verbosity('medium');

                if (strpos($model, 'gpt-5') === 0) {
                    $client->set_reasoning_effort('minimal');
                    $client->set_text_verbosity('low');
                }

                $messages = [
                    ['role' => 'system', 'content' => $system_message],
                    ['role' => 'user', 'content' => $user_message]
                ];

                log_message("Sending request to OpenAI API with model $model...");
                $start_time = microtime(true);
                $response = $client->get_completion($messages);
                $time_taken = microtime(true) - $start_time;

                if ($response !== false) {
                    log_message("SUCCESS: Response received in " . round($time_taken, 2) . " seconds");
                    log_message("Response: " . $response);
                } else {
                    log_message("ERROR: Request failed for model $model");
                }

                log_message("Finished testing model: $model\n");
            }
        } catch (Exception $e) {
            log_message("EXCEPTION: " . $e->getMessage());
            log_message("Trace: " . $e->getTraceAsString());
        }
    }
}

// Test Google
if ($provider === 'all' || $provider === 'google') {
    log_message("=== Testing Google Gemini API ===");

    if (empty($config->google_apikey)) {
        log_message("ERROR: Google API key not configured");
    } else {
        try {
            log_message("Creating Google client with API key (length: " . strlen($config->google_apikey) . ")");
            $client = new block_tutoring_machine_google_client($config->google_apikey);

            $models = ['gemini-1.5-pro', 'gemini-1.5-flash'];

            foreach ($models as $model) {
                log_message("Testing model: $model");
                $client->set_model($model);
                $client->set_max_tokens(100);
                $client->set_temperature(0.7);

                $messages = [
                    ['role' => 'system', 'content' => $system_message],
                    ['role' => 'user', 'content' => $user_message]
                ];

                log_message("Sending request to Google API with model $model...");
                $start_time = microtime(true);
                $response = $client->get_completion($messages);
                $time_taken = microtime(true) - $start_time;

                if ($response !== false) {
                    log_message("SUCCESS: Response received in " . round($time_taken, 2) . " seconds");
                    log_message("Response: " . $response);
                } else {
                    log_message("ERROR: Request failed for model $model");
                }

                log_message("Finished testing model: $model\n");
            }
        } catch (Exception $e) {
            log_message("EXCEPTION: " . $e->getMessage());
            log_message("Trace: " . $e->getTraceAsString());
        }
    }
}

// Anthropic test has been removed

log_message("API testing complete. Log file: $log_file");
