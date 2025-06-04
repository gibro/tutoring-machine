<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Direct API test for Chatbot block.
 *
 * @package    block_chatbot
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a test file that should only be accessible to admins
require_once('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

// Include API client class
require_once($CFG->dirroot . '/blocks/chatbot/classes/api_client.php');

// Get API key from configuration
$config = get_config('block_chatbot');
$api_key = isset($config->openai_apikey) ? $config->openai_apikey : '';

if (empty($api_key)) {
    die("Error: OpenAI API key not configured");
}

// Set up a simple test using direct cURL to minimize interference
$url = 'https://api.openai.com/v1/chat/completions';
$model = 'gpt-3.5-turbo'; // Use the smallest, fastest model for test

$data = [
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => 'Say hello in a simple way.']
    ],
    'max_tokens' => 20
];

$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
];

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Execute request
echo "<h2>Direct OpenAI API Test</h2>";
echo "<pre>";
echo "Sending request to OpenAI API...\n";

$start_time = microtime(true);
$response = curl_exec($ch);
$time_taken = microtime(true) - $start_time;

// Check for errors
if (curl_errno($ch)) {
    echo "ERROR: " . curl_error($ch) . "\n";
    echo "Error code: " . curl_errno($ch) . "\n";
} else {
    // Get HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP Status: " . $http_code . "\n";
    echo "Time taken: " . round($time_taken, 2) . " seconds\n\n";
    
    // Process and display response
    $result = json_decode($response, true);
    
    if ($http_code >= 200 && $http_code < 300) {
        echo "SUCCESS!\n\n";
        if (isset($result['choices'][0]['message']['content'])) {
            echo "Response: " . $result['choices'][0]['message']['content'] . "\n";
        } else {
            echo "Response structure: " . print_r($result, true) . "\n";
        }
    } else {
        echo "API ERROR: " . $http_code . "\n";
        echo "Response: " . print_r($result, true) . "\n";
    }
}

// Close cURL session
curl_close($ch);

echo "</pre>";

// Now test the actual API client class
echo "<h2>API Client Test</h2>";
echo "<pre>";

try {
    // Create API client
    $client = new block_chatbot_openai_client($api_key);
    $client->set_model('gpt-3.5-turbo');
    $client->set_max_tokens(20);
    
    $messages = [
        ['role' => 'user', 'content' => 'Say hello in a simple way.']
    ];
    
    // Send request
    echo "Sending request through API client...\n";
    $start_time = microtime(true);
    $response = $client->get_completion($messages);
    $time_taken = microtime(true) - $start_time;
    
    echo "Time taken: " . round($time_taken, 2) . " seconds\n\n";
    
    if ($response !== false) {
        echo "SUCCESS!\n\n";
        echo "Response: " . $response . "\n";
    } else {
        echo "API CLIENT ERROR\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Show JavaScript for direct test
echo "<h2>Browser API Test</h2>";
echo "<p>This tests if your browser can directly reach the OpenAI API (CORS restrictions may prevent this)</p>";
echo "<button id='testButton'>Run Browser Test</button>";
echo "<div id='result'></div>";

?>
<script>
document.getElementById('testButton').addEventListener('click', async function() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = "<p>Testing API from browser...</p>";
    
    try {
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer <?php echo $api_key ?>'
            },
            body: JSON.stringify({
                model: 'gpt-3.5-turbo',
                messages: [
                    {role: 'user', content: 'Say hello in a simple way.'}
                ],
                max_tokens: 20
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            resultDiv.innerHTML = `
                <p style="color: green;">SUCCESS!</p>
                <p>Response: ${data.choices[0].message.content}</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        } else {
            resultDiv.innerHTML = `
                <p style="color: red;">ERROR: ${response.status}</p>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <p style="color: red;">FETCH ERROR: ${error.message}</p>
        `;
    }
});
</script>