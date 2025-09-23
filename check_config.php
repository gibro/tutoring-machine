<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Configuration checker for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Security: require admin access
admin_externalpage_setup('blocksettingtutoring_machine');

// Page setup
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/blocks/tutoring_machine/check_config.php');
$PAGE->set_title('Tutoring Machine Configuration Check');
$PAGE->set_heading('Tutoring Machine Configuration Check');

echo $OUTPUT->header();
echo $OUTPUT->heading('Tutoring Machine Configuration Check');

// Display PHP and environment info
echo $OUTPUT->heading('Environment Information', 3);
echo '<table class="table">';
echo '<tr><td>PHP Version</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>Moodle Version</td><td>' . $CFG->version . '</td></tr>';
echo '<tr><td>cURL Available</td><td>' . (function_exists('curl_init') ? 'Yes (' . curl_version()['version'] . ')' : 'No') . '</td></tr>';
echo '<tr><td>JSON Available</td><td>' . (function_exists('json_encode') ? 'Yes' : 'No') . '</td></tr>';
echo '<tr><td>Operating System</td><td>' . php_uname() . '</td></tr>';
echo '</table>';

// Get and display plugin configuration
$config = get_config('block_tutoring_machine');
echo $OUTPUT->heading('Plugin Configuration', 3);
echo '<table class="table">';

// Display config properties
echo '<tr><td>Default Provider</td><td>' . (isset($config->default_provider) ? $config->default_provider : 'Not set (defaults to OpenAI)') . '</td></tr>';
echo '<tr><td>Default Model</td><td>' . (isset($config->default_model) ? $config->default_model : 'Not set (defaults to gpt-5)') . '</td></tr>';
echo '<tr><td>Temperature</td><td>' . (isset($config->temperature) ? $config->temperature : 'Not set (defaults to 0.7)') . '</td></tr>';
echo '<tr><td>Top-P</td><td>' . (isset($config->top_p) ? $config->top_p : 'Not set (defaults to 0.9)') . '</td></tr>';
echo '<tr><td>Max Tokens</td><td>' . (isset($config->max_tokens) ? $config->max_tokens : 'Not set (defaults to 2500)') . '</td></tr>';
echo '<tr><td>Timeout</td><td>' . (isset($config->timeout) ? $config->timeout : 'Not set (defaults to 30)') . '</td></tr>';
echo '<tr><td>Response Format</td><td>' . (isset($config->response_format) ? $config->response_format : 'Not set (defaults to text)') . '</td></tr>';

// Check API keys (mask them for security)
$openai_key = isset($config->openai_apikey) ? $config->openai_apikey : '';
$google_key = isset($config->google_apikey) ? $config->google_apikey : '';

echo '<tr><td>OpenAI API Key</td><td>';
if (!empty($openai_key)) {
    $key_length = strlen($openai_key);
    $mask = substr($openai_key, 0, 4) . '...' . substr($openai_key, -4);
    echo '<span class="badge badge-success">Configured</span> (' . $mask . ') [Length: ' . $key_length . ']';
} else {
    echo '<span class="badge badge-danger">Not configured</span>';
}
echo '</td></tr>';

echo '<tr><td>Google API Key</td><td>';
if (!empty($google_key)) {
    $key_length = strlen($google_key);
    $mask = substr($google_key, 0, 4) . '...' . substr($google_key, -4);
    echo '<span class="badge badge-success">Configured</span> (' . $mask . ') [Length: ' . $key_length . ']';
} else {
    echo '<span class="badge badge-danger">Not configured</span>';
}
echo '</td></tr>';

echo '</table>';

// Check for common issues
echo $OUTPUT->heading('Configuration Checks', 3);
echo '<div class="alert alert-info">Checking for common configuration issues...</div>';
echo '<ul class="list-group">';

// Check if the default provider has an API key
$default_provider = isset($config->default_provider) ? $config->default_provider : 'openai';
if ($default_provider === 'openai' && empty($openai_key)) {
    echo '<li class="list-group-item list-group-item-danger">The default provider is OpenAI but no OpenAI API key is configured</li>';
} else if ($default_provider === 'google' && empty($google_key)) {
    echo '<li class="list-group-item list-group-item-danger">The default provider is Google but no Google API key is configured</li>';
} else {
    echo '<li class="list-group-item list-group-item-success">Default provider (' . $default_provider . ') has an API key configured</li>';
}

// Check if the OpenAI API key format is valid (starts with "sk-" for newer keys)
if (!empty($openai_key) && (strpos($openai_key, 'sk-') !== 0)) {
    echo '<li class="list-group-item list-group-item-warning">The OpenAI API key may not be in the correct format (should start with "sk-")</li>';
} else if (!empty($openai_key)) {
    echo '<li class="list-group-item list-group-item-success">OpenAI API key format appears valid</li>';
}

// Check for curl extension
if (!function_exists('curl_init')) {
    echo '<li class="list-group-item list-group-item-danger">cURL extension is not available - required for API communication</li>';
} else {
    echo '<li class="list-group-item list-group-item-success">cURL extension is available</li>';
}

// Check if SSL verification is possible
if (function_exists('curl_init')) {
    $ch = curl_init('https://api.openai.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $ssl_verify_error = curl_errno($ch) === CURLE_SSL_CACERT || curl_errno($ch) === CURLE_SSL_CACERT_BADFILE;
    curl_close($ch);

    if ($ssl_verify_error) {
        echo '<li class="list-group-item list-group-item-danger">SSL certificate verification failed - this will prevent secure API connections</li>';
    } else {
        echo '<li class="list-group-item list-group-item-success">SSL certificate verification is working</li>';
    }
}

// Check if the server can reach the API endpoints
if (function_exists('curl_init')) {
    // Check OpenAI endpoin
    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $openai_reachable = curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 0;
    curl_close($ch);

    // Check Google endpoin
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $google_reachable = curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 0;
    curl_close($ch);

    echo '<li class="list-group-item ' . ($openai_reachable ? 'list-group-item-success' : 'list-group-item-danger') . '">
          OpenAI API endpoint is ' . ($openai_reachable ? 'reachable' : 'not reachable') . '</li>';

    echo '<li class="list-group-item ' . ($google_reachable ? 'list-group-item-success' : 'list-group-item-danger') . '">
          Google API endpoint is ' . ($google_reachable ? 'reachable' : 'not reachable') . '</li>';

}

echo '</ul>';

// Run API key validation
echo $OUTPUT->heading('API Key Validation', 3);
echo '<div class="alert alert-info">Note: This test only checks if the keys are valid, not if the models are accessible.</div>';

echo '<div id="api-validation-results">Loading results...</div>';

echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const resultsDiv = document.getElementById("api-validation-results");
    resultsDiv.innerHTML = "<p>Validating API keys (this may take a few seconds)...</p>";

    // Function to check OpenAI key forma
    function validateOpenAIKey(key) {
        if (!key) return false;
        return key.startsWith("sk-") && key.length > 20;
    }

    // Function to check Google key format (basic check)
    function validateGoogleKey(key) {
        if (!key) return false;
        return key.length > 10;
    }

    // Generate validation results
    let results = "<ul class=\'list-group\'>";

    // OpenAI key validation
    const openaiKey = "' . (!empty($openai_key) ? $openai_key : '') . '";
    const openaiValid = validateOpenAIKey(openaiKey);
    results += "<li class=\'list-group-item " + (openaiValid ? "list-group-item-success" : "list-group-item-danger") + "\'>" +
              "OpenAI API Key: " + (openaiValid ? "Valid format" : "Invalid format or not provided") +
              (openaiKey && !openaiValid ? " (should start with sk- and be longer than 20 characters)" : "") +
              "</li>";

    // Google key validation
    const googleKey = "' . (!empty($google_key) ? $google_key : '') . '";
    const googleValid = validateGoogleKey(googleKey);
    results += "<li class=\'list-group-item " + (googleValid ? "list-group-item-success" : "list-group-item-danger") + "\'>" +
              "Google API Key: " + (googleValid ? "Valid format" : "Invalid format or not provided") +
              "</li>";

    results += "</ul>";

    resultsDiv.innerHTML = results;
});
</script>';

// Next steps and troubleshooting advice
echo $OUTPUT->heading('Troubleshooting Recommendations', 3);
echo '<div class="alert alert-info">';
echo '<p><strong>If you\'re experiencing API connection issues:</strong></p>';
echo '<ol>';
echo '<li>Check that the API key for your default provider is correctly configured</li>';
echo '<li>Verify your API key is valid and has not expired or been revoked</li>';
echo '<li>Ensure your server can connect to the API endpoints (no firewall or network restrictions)</li>';
echo '<li>Check that your API key has access to the model you\'re trying to use</li>';
echo '<li>Verify your API account has sufficient credits or quota remaining</li>';
echo '<li>Look in the Moodle error logs for specific error messages</li>';
echo '</ol>';

echo '<p><strong>Running the API test script:</strong></p>';
echo '<p>For more detailed diagnostics, you can run the API test script from the command line:</p>';
echo '<pre>php ' . __DIR__ . '/api_test.php</pre>';
echo '<p>Or test a specific provider:</p>';
echo '<pre>php ' . __DIR__ . '/api_test.php openai</pre>';
echo '<pre>php ' . __DIR__ . '/api_test.php google</pre>';
echo '</div>';

echo $OUTPUT->footer();
