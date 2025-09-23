<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Web-based API test script for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a test file that should only be accessible to admins in development environments
// Check for admin rights before allowing access
require_once('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

// Security check for admin access - development mode is no longer required
// Comment this line out if you want to enforce development mode
// if (!debugging('', DEBUG_DEVELOPER)) {
//     die('This script can only be run in development mode with debugging enabled.');
// }

// Include API client class
require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/api_client.php');

// Get API keys from Moodle config
$config = get_config('block_tutoring_machine');
$openai_key = !empty($config->openai_apikey) ? $config->openai_apikey : '';
$google_key = !empty($config->google_apikey) ? $config->google_apikey : '';

// Set page layou
$PAGE->set_url(new moodle_url('/blocks/tutoring_machine/api_test_web.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Tutoring Machine API Test');
$PAGE->set_heading('Tutoring Machine API Test');

echo $OUTPUT->header();

// Display test interface
echo '<div class="container">
    <div class="row mb-4">
        <div class="col">
            <div class="alert alert-info">
                <h4>API Test Information</h4>
                <p>This page tests the API clients for different AI providers. It sends a simple query to each configured API provider and displays the response.</p>
                <p><strong>Note:</strong> This test uses real API calls and may incur costs with your API provider.</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3 class="m-0">API Key Status</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>OpenAI API Key:</strong>
                        <span class="badge ' . (empty($openai_key) ? 'badge-danger' : 'badge-success') . '">
                            ' . (empty($openai_key) ? 'Not Configured' : 'Configured') . '
                        </span>
                    </div>
                    <div>
                        <strong>Google API Key:</strong>
                        <span class="badge ' . (empty($google_key) ? 'badge-danger' : 'badge-success') . '">
                            ' . (empty($google_key) ? 'Not Configured' : 'Configured') . '
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Test OpenAI API if key is configured
if (!empty($openai_key)) {
    echo '<div class="row mb-4">
        <div class="col">
            <div class="card" id="openai-test">
                <div class="card-header bg-primary text-white">
                    <h3 class="m-0">Testing OpenAI API</h3>
                </div>
                <div class="card-body">
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                    <p>Sending test request to OpenAI API...</p>
                    <div id="openai-result">
                        <div class="alert alert-info">Waiting for response...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

// Test Google API if key is configured
if (!empty($google_key)) {
    echo '<div class="row mb-4">
        <div class="col">
            <div class="card" id="google-test">
                <div class="card-header bg-primary text-white">
                    <h3 class="m-0">Testing Google Gemini API</h3>
                </div>
                <div class="card-body">
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                    <p>Sending test request to Google Gemini API...</p>
                    <div id="google-result">
                        <div class="alert alert-info">Waiting for response...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

// Display troubleshooting section
echo '<div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h3 class="m-0">Troubleshooting</h3>
                </div>
                <div class="card-body">
                    <p>If you see errors, check:</p>
                    <ul>
                        <li>API keys are correctly configured in the plugin settings</li>
                        <li>Network connectivity to the API endpoints</li>
                        <li>PHP cURL extension is enabled and working properly</li>
                        <li>Error logs for more detailed information</li>
                    </ul>
                    <p>For more details, you can also run the command-line test script:</p>
                    <pre>php blocks/tutoring_machine/api_test.php</pre>
                </div>
            </div>
        </div>
    </div>
</div>';

// Add JavaScript for AJAX calls
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Test OpenAI API
    ' . (!empty($openai_key) ? 'testProvider("openai");' : '') . '

    // Test Google API
    ' . (!empty($google_key) ? 'testProvider("google");' : '') . '

    function testProvider(provider) {
        fetch("api_test_ajax.php?provider=" + provider, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById(provider + "-result");
            const cardDiv = document.getElementById(provider + "-test");
            const cardHeader = cardDiv.querySelector(".card-header");

            // Remove progress bar
            cardDiv.querySelector(".progress").remove();

            if (data.success) {
                cardHeader.className = "card-header bg-success text-white";
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <strong>Test successful!</strong>
                    </div>
                    <div class="card">
                        <div class="card-header">Response:</div>
                        <div class="card-body">
                            <p>${data.message}</p>
                        </div>
                    </div>`;
            } else {
                cardHeader.className = "card-header bg-danger text-white";
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Test failed:</strong>
                    </div>
                    <div class="card">
                        <div class="card-header">Error:</div>
                        <div class="card-body">
                            <p>${data.error}</p>
                        </div>
                    </div>`;
            }
        })
        .catch(error => {
            const resultDiv = document.getElementById(provider + "-result");
            const cardDiv = document.getElementById(provider + "-test");
            const cardHeader = cardDiv.querySelector(".card-header");

            // Remove progress bar
            cardDiv.querySelector(".progress").remove();

            cardHeader.className = "card-header bg-danger text-white";
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Request failed:</strong>
                </div>
                <div class="card">
                    <div class="card-header">Error:</div>
                    <div class="card-body">
                        <p>${error}</p>
                    </div>
                </div>`;
        });
    }
});
</script>';

echo $OUTPUT->footer();