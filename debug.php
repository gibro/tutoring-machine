<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Debugging tool for Tutoring Machine block
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Diagnostic functions for Chatbo
 */
class block_tutoring_machine_diagnostics {
    /** @var string $log_file Path to the log file */
    private static $log_file = null;

    /**
     * Initialize the log file
     */
    public static function init_log() {
        // Create logs directory if it doesn't exis
        $log_dir = __DIR__ . '/logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        // Create or truncate log file
        self::$log_file = $log_dir . '/debug_' . date('Y-m-d_H-i-s') . '.log';
        file_put_contents(self::$log_file, "=== Tutoring Machine Debug Log ===\n" . date('Y-m-d H:i:s') . "\n\n");
    }

    /**
     * Write a message to the log file
     *
     * @param string $message Message to log
     * @param string $level Log level (INFO, WARNING, ERROR)
     */
    public static function log($message, $level = 'INFO') {
        if (self::$log_file === null) {
            self::init_log();
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] [$level] $message\n";

        // Write to log file
        file_put_contents(self::$log_file, $log_message, FILE_APPEND);

        // Also log to error_log for redundancy
        error_log("CHATBOT_DEBUG [$level]: $message");
    }

    /**
     * Test API connectivity for all providers
     *
     * @return array Results of the tests
     */
    public static function test_api_connectivity() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/api_client.php');

        $results = [
            'openai' => ['status' => 'Not tested', 'message' => ''],
            'google' => ['status' => 'Not tested', 'message' => '']
        ];

        // Get configuration
        $config = get_config('block_tutoring_machine');

        // Test OpenAI connectivity
        if (!empty($config->openai_apikey)) {
            self::log("Testing OpenAI API connectivity...");
            try {
                $client = new block_tutoring_machine_openai_client($config->openai_apikey);
                $client->set_model('gpt-5');
                $client->set_max_tokens(50);
                $client->set_reasoning_effort('minimal');
                $client->set_text_verbosity('low');

                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => 'Say "Hello, this is a test."']
                ];

                self::log("Sending request to OpenAI API...");
                $response = $client->get_completion($messages);

                if ($response !== false) {
                    $results['openai']['status'] = 'Success';
                    $results['openai']['message'] = "API connection successful. Response: " . substr($response, 0, 100);
                    self::log("OpenAI API test successful: " . substr($response, 0, 100), 'INFO');
                } else {
                    $results['openai']['status'] = 'Failed';
                    $results['openai']['message'] = "API request failed. Check Moodle error logs for details.";
                    self::log("OpenAI API test failed", 'ERROR');
                }
            } catch (Exception $e) {
                $results['openai']['status'] = 'Error';
                $results['openai']['message'] = "Exception: " . $e->getMessage();
                self::log("OpenAI API test exception: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $results['openai']['status'] = 'Skipped';
            $results['openai']['message'] = "No API key configured";
            self::log("OpenAI API test skipped - no API key configured", 'WARNING');
        }

        // Test Google connectivity
        if (!empty($config->google_apikey)) {
            self::log("Testing Google Gemini API connectivity...");
            try {
                $client = new block_tutoring_machine_google_client($config->google_apikey);
                $client->set_model('gemini-2.5-pro');
                $client->set_max_tokens(50);

                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => 'Say "Hello, this is a test."']
                ];

                self::log("Sending request to Google Gemini API...");
                $response = $client->get_completion($messages);

                if ($response !== false) {
                    $results['google']['status'] = 'Success';
                    $results['google']['message'] = "API connection successful. Response: " . substr($response, 0, 100);
                    self::log("Google API test successful: " . substr($response, 0, 100), 'INFO');
                } else {
                    $results['google']['status'] = 'Failed';
                    $results['google']['message'] = "API request failed. Check Moodle error logs for details.";
                    self::log("Google API test failed", 'ERROR');
                }
            } catch (Exception $e) {
                $results['google']['status'] = 'Error';
                $results['google']['message'] = "Exception: " . $e->getMessage();
                self::log("Google API test exception: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $results['google']['status'] = 'Skipped';
            $results['google']['message'] = "No API key configured";
            self::log("Google API test skipped - no API key configured", 'WARNING');
        }

        // Anthropic test has been removed
        self::log("Anthropic API test skipped - integration removed", 'INFO');

        return $results;
    }

    /**
     * Get current configuration
     *
     * @return array Configuration details
     */
    public static function get_configuration() {
        $config = get_config('block_tutoring_machine');

        // Mask API keys for security
        $masked_config = clone $config;

        if (isset($masked_config->openai_apikey)) {
            $key_length = strlen($masked_config->openai_apikey);
            $masked_config->openai_apikey = ($key_length > 8) ?
                substr($masked_config->openai_apikey, 0, 4) . '...' . substr($masked_config->openai_apikey, -4) :
                '********';
        }

        if (isset($masked_config->google_apikey)) {
            $key_length = strlen($masked_config->google_apikey);
            $masked_config->google_apikey = ($key_length > 8) ?
                substr($masked_config->google_apikey, 0, 4) . '...' . substr($masked_config->google_apikey, -4) :
                '********';
        }

        return [
            'default_provider' => isset($config->default_provider) ? $config->default_provider : 'openai',
            'default_model' => isset($config->default_model) ? $config->default_model : 'openai:gpt-5',
            'temperature' => isset($config->temperature) ? $config->temperature : '0.7',
            'top_p' => isset($config->top_p) ? $config->top_p : '0.9',
            'max_tokens' => isset($config->max_tokens) ? $config->max_tokens : '2500',
            'timeout' => isset($config->timeout) ? $config->timeout : '30',
            'response_format' => isset($config->response_format) ? $config->response_format : 'text',
            'openai_apikey_set' => !empty($config->openai_apikey),
            'google_apikey_set' => !empty($config->google_apikey),
            'openai_apikey_masked' => isset($masked_config->openai_apikey) ? $masked_config->openai_apikey : 'not set',
            'google_apikey_masked' => isset($masked_config->google_apikey) ? $masked_config->google_apikey : 'not set',
            'moodle_version' => $CFG->version,
            'php_version' => phpversion(),
            'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'unknown'
        ];
    }

    /**
     * Check system requirements
     *
     * @return array Results of system checks
     */
    public static function check_system() {
        $results = [];

        // Check PHP version
        $php_version = phpversion();
        $results['php_version'] = [
            'status' => version_compare($php_version, '8.2') >= 0 ? 'OK' : 'Warning',
            'message' => "PHP $php_version" . (version_compare($php_version, '8.2') < 0 ? " (PHP 8.2 or later recommended)" : "")
        ];

        // Check cURL extension
        $results['curl'] = [
            'status' => function_exists('curl_init') ? 'OK' : 'Error',
            'message' => function_exists('curl_init') ?
                "cURL extension installed (" . curl_version()['version'] . ")" :
                "cURL extension not available"
        ];

        // Check JSON extension
        $results['json'] = [
            'status' => function_exists('json_encode') ? 'OK' : 'Error',
            'message' => function_exists('json_encode') ?
                "JSON extension installed" :
                "JSON extension not available"
        ];

        // Check logs directory
        $log_dir = __DIR__ . '/logs';
        $results['logs_dir'] = [
            'status' => (file_exists($log_dir) && is_writable($log_dir)) ? 'OK' :
                       (file_exists($log_dir) ? 'Warning' : 'Not Created'),
            'message' => file_exists($log_dir) ?
                (is_writable($log_dir) ? "Logs directory is writable" : "Logs directory exists but is not writable") :
                "Logs directory does not exist (will be created)"
        ];

        return $results;
    }
}

// This script can be run from the command line for testing
if (PHP_SAPI === 'cli') {
    // Set up Moodle environmen
    define('CLI_SCRIPT', true);
    require_once(__DIR__ . '/../../config.php');

    // Run diagnostics
    echo "Running Tutoring Machine diagnostics...\n";
    block_tutoring_machine_diagnostics::init_log();
    block_tutoring_machine_diagnostics::log("Starting CLI diagnostics");

    echo "\nSystem Checks:\n";
    $system_checks = block_tutoring_machine_diagnostics::check_system();
    foreach ($system_checks as $check => $result) {
        echo "- $check: " . $result['status'] . " - " . $result['message'] . "\n";
    }

    echo "\nConfiguration:\n";
    $config = block_tutoring_machine_diagnostics::get_configuration();
    foreach ($config as $key => $value) {
        echo "- $key: $value\n";
    }

    echo "\nAPI Connectivity Tests:\n";
    $api_tests = block_tutoring_machine_diagnostics::test_api_connectivity();
    foreach ($api_tests as $provider => $result) {
        echo "- $provider: " . $result['status'] . " - " . $result['message'] . "\n";
    }

    echo "\nDiagnostics complete. Log file: " . block_tutoring_machine_diagnostics::$log_file . "\n";
}

// Debug endpoints
if (isset($_GET['action']) && $_GET['action'] === 'debug') {
    // Web-based diagnostics
    require_once(__DIR__ . '/../../config.php');
    require_once($CFG->libdir . '/adminlib.php');

    // Check admin privileges
    admin_externalpage_setup('blocksettingtutoring_machine');

    // Set up page
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/blocks/tutoring_machine/debug.php', ['action' => 'debug']);
    $PAGE->set_title('Tutoring Machine Diagnostics');
    $PAGE->set_heading('Tutoring Machine Diagnostics');

    // Initialize diagnostic logger
    block_tutoring_machine_diagnostics::init_log();
    block_tutoring_machine_diagnostics::log("Starting web diagnostics");

    // Run tests
    $system_checks = block_tutoring_machine_diagnostics::check_system();
    $config = block_tutoring_machine_diagnostics::get_configuration();
    $api_tests = block_tutoring_machine_diagnostics::test_api_connectivity();

    // Output page
    echo $OUTPUT->header();
    echo $OUTPUT->heading('Tutoring Machine Diagnostics');

    echo '<div class="alert alert-info">This page runs diagnostic tests on the Tutoring Machine plugin to help identify configuration issues.</div>';

    // System checks
    echo $OUTPUT->heading('System Checks', 3);
    echo '<table class="table"><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';
    foreach ($system_checks as $check => $result) {
        $status_class = $result['status'] === 'OK' ? 'success' : ($result['status'] === 'Warning' ? 'warning' : 'danger');
        echo "<tr><td>$check</td><td><span class='badge badge-$status_class'>{$result['status']}</span></td><td>{$result['message']}</td></tr>";
    }
    echo '</tbody></table>';

    // Configuration
    echo $OUTPUT->heading('Configuration', 3);
    echo '<table class="table"><thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody>';
    foreach ($config as $key => $value) {
        echo "<tr><td>$key</td><td>$value</td></tr>";
    }
    echo '</tbody></table>';

    // API connectivity tests
    echo $OUTPUT->heading('API Connectivity Tests', 3);
    echo '<table class="table"><thead><tr><th>Provider</th><th>Status</th><th>Details</th></tr></thead><tbody>';
    foreach ($api_tests as $provider => $result) {
        $status_class = $result['status'] === 'Success' ? 'success' :
                       ($result['status'] === 'Skipped' ? 'secondary' : 'danger');
        echo "<tr><td>$provider</td><td><span class='badge badge-$status_class'>{$result['status']}</span></td><td>{$result['message']}</td></tr>";
    }
    echo '</tbody></table>';

    // Log file information
    $log_file = block_tutoring_machine_diagnostics::$log_file;
    $log_contents = file_exists($log_file) ? file_get_contents($log_file) : 'No log file available';

    echo $OUTPUT->heading('Debug Log', 3);
    echo '<pre style="max-height: 400px; overflow-y: auto;">' . htmlspecialchars($log_contents) . '</pre>';

    echo $OUTPUT->footer();
    exit;
}
