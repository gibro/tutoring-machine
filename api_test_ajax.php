<?php
// This file is part of Moodle - http://moodle.org/
/**
 * AJAX handler for API test script for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

// Security check for admin access - development mode is no longer required
// Comment this line out if you want to enforce development mode
// if (!debugging('', DEBUG_DEVELOPER)) {
//     die(json_encode(['success' => false, 'error' => 'This script can only be run in development mode with debugging enabled.']));
// }

// Include API client class
require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/api_client.php');

// Get provider parameter
$provider = required_param('provider', PARAM_ALPHA);

// Set AJAX response header
header('Content-Type: application/json');

// Get API keys from Moodle config
$config = get_config('block_tutoring_machine');

// Test messages to send to the APIs
$test_messages = [
    [
        'role' => 'system',
        'content' => 'You are a helpful assistant that provides concise and accurate information.'
    ],
    [
        'role' => 'user',
        'content' => 'What is Moodle? Answer in one sentence.'
    ]
];

// Test the specified provider
switch ($provider) {
    case 'openai':
        if (empty($config->openai_apikey)) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API key is not configured']);
        } else {
            try {
                $client = new block_tutoring_machine_openai_client($config->openai_apikey);
                $client->set_model('gpt-5');
                $client->set_max_tokens(100);
                $client->set_reasoning_effort('minimal');
                $client->set_text_verbosity('low');

                $response = $client->get_completion($test_messages);

                if ($response !== false) {
                    echo json_encode(['success' => true, 'message' => htmlspecialchars($response)]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'OpenAI API request failed. Check error logs for details.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Exception: ' . htmlspecialchars($e->getMessage())]);
            }
        }
        break;

    case 'google':
        if (empty($config->google_apikey)) {
            echo json_encode(['success' => false, 'error' => 'Google API key is not configured']);
        } else {
            try {
                $client = new block_tutoring_machine_google_client($config->google_apikey);
                $client->set_model('gemini-1.5-pro');
                $client->set_max_tokens(100);
                $client->set_temperature(0.7);

                $response = $client->get_completion($test_messages);

                if ($response !== false) {
                    echo json_encode(['success' => true, 'message' => htmlspecialchars($response)]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Google Gemini API request failed. Check error logs for details.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Exception: ' . htmlspecialchars($e->getMessage())]);
            }
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid provider specified']);
}
