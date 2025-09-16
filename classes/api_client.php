<?php
// This file is part of Moodle - http://moodle.org/
/**
 * API client class for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * API client interface for AI providers
 *
 * Provides a common interface for all AI provider clients.
 */
interface block_tutoring_machine_api_client_interface {
    /**
     * Get completion from the AI provider
     *
     * @param array $messages The messages to send to the AI provider
     * @return string|false The completion text or false on failure
     */
    public function get_completion($messages);

    /**
     * Set the model to use
     *
     * @param string $model Model identifier
     * @return self This instance for method chaining
     */
    public function set_model($model);

    /**
     * Set temperature parameter
     *
     * @param float $temperature Temperature value (0.0-1.0)
     * @return self This instance for method chaining
     */
    public function set_temperature($temperature);

    /**
     * Set top_p parameter for nucleus sampling
     *
     * @param float $top_p Top-p value (0.0-1.0)
     * @return self This instance for method chaining
     */
    public function set_top_p($top_p);

    /**
     * Set max tokens parameter
     *
     * @param int $max_tokens Maximum number of tokens in response
     * @return self This instance for method chaining
     */
    public function set_max_tokens($max_tokens);

    /**
     * Set response forma
     *
     * @param string $format Response format ('text' or 'json')
     * @return self This instance for method chaining
     */
    public function set_response_format($format);

    /**
     * Set request timeou
     *
     * @param int $timeout Timeout in seconds
     * @return self This instance for method chaining
     */
    public function set_timeout($timeout);
}

/**
 * Base API client class with common functionality
 *
 * This abstract class provides shared implementation for all AI provider clients.
 * It handles parameter validation, HTTP requests, error handling, and logging.
 * Each provider should extend this class and implement the provider-specific methods.
 */
abstract class block_tutoring_machine_api_client_base implements block_tutoring_machine_api_client_interface {
    /** @var string $api_key The API key for authentication */
    protected $api_key;

    /** @var string $model The model to use for completion */
    protected $model;

    /** @var int $max_tokens Maximum number of tokens in response */
    protected $max_tokens = 2500;

    /** @var float $temperature Temperature parameter for response generation */
    protected $temperature = 0.7;

    /** @var float $top_p Top-p/nucleus sampling parameter */
    protected $top_p = 0.9;

    /** @var string $response_format The format for the response */
    protected $response_format = 'text';

    /** @var int $timeout Request timeout in seconds */
    protected $timeout = 30;

    /** @var int $connect_timeout Connection timeout in seconds */
    protected $connect_timeout = 10;

    /** @var string $api_endpoint API endpoint URL */
    protected $api_endpoint;

    /** @var int $max_retries Maximum number of retry attempts */
    protected $max_retries = 2;

    /**
     * Constructor
     *
     * @param string $api_key The API key
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Set the model to use
     *
     * @param string $model Model identifier
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_model($model) {
        $this->model = clean_param($model, PARAM_NOTAGS);
        return $this;
    }

    /**
     * Set max tokens parameter
     *
     * @param int $max_tokens Maximum number of tokens in response
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_max_tokens($max_tokens) {
        $max_tokens = clean_param($max_tokens, PARAM_INT);
        $this->max_tokens = max(1, min(4000, (int)$max_tokens));
        return $this;
    }

    /**
     * Set temperature parameter
     *
     * @param float $temperature Temperature value (0.0-1.0)
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_temperature($temperature) {
        $temperature = clean_param($temperature, PARAM_FLOAT);
        $this->temperature = max(0, min(1, (float)$temperature));
        return $this;
    }

    /**
     * Set top_p parameter for nucleus sampling
     *
     * @param float $top_p Top-p value (0.0-1.0)
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_top_p($top_p) {
        $top_p = clean_param($top_p, PARAM_FLOAT);
        $this->top_p = max(0, min(1, (float)$top_p));
        return $this;
    }

    /**
     * Set response forma
     *
     * @param string $format Response format ('text' or 'json')
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_response_format($format) {
        $format = clean_param($format, PARAM_ALPHA);
        if (in_array($format, ['text', 'json'])) {
            $this->response_format = $format;
        }
        return $this;
    }

    /**
     * Set request timeou
     *
     * @param int $timeout Timeout in seconds
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_timeout($timeout) {
        $timeout = clean_param($timeout, PARAM_INT);
        $this->timeout = max(5, min(60, (int)$timeout));
        return $this;
    }

    /**
     * Set connection timeou
     *
     * @param int $timeout Timeout in seconds
     * @return block_tutoring_machine_api_client_base This instance for method chaining
     */
    public function set_connect_timeout($timeout) {
        $timeout = clean_param($timeout, PARAM_INT);
        $this->connect_timeout = max(1, min(30, (int)$timeout));
        return $this;
    }

    /**
     * Validate and sanitize messages array
     *
     * @param array $messages The array of messages to validate
     * @return array Validated and sanitized messages
     */
    protected function validate_messages($messages) {
        if (!is_array($messages)) {
            throw new InvalidArgumentException('Messages must be an array');
        }

        $validated_messages = [];

        foreach ($messages as $message) {
            if (!is_array($message) || !isset($message['role']) || !isset($message['content'])) {
                continue;
            }

            // Validate role is one of the allowed types
            if (!in_array($message['role'], ['system', 'user', 'assistant'])) {
                continue;
            }

            $validated_messages[] = [
                'role' => clean_param($message['role'], PARAM_ALPHA),
                'content' => clean_param($message['content'], PARAM_TEXT)
            ];
        }

        return $validated_messages;
    }

    /**
     * Execute cURL request with retry mechanism
     *
     * This method handles the HTTP request to the AI provider API with robust error handling,
     * detailed logging, and automatic retries for transient failures. It uses individual
     * curl_setopt calls instead of curl_setopt_array for better compatibility across PHP versions.
     *
     * @param string $url The URL to reques
     * @param array $options The cURL options
     * @return array|false The response data as an array on success, false on failure
     */
    protected function execute_curl_request($url, $options) {
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            $this->log_error('cURL is not available on this system');
            return false;
        }

        // Initialize retry counter
        $retries = 0;
        $start_time = microtime(true);

        // Log the request (without sensitive information)
        $log_url = preg_replace('/key=[^&]*/', 'key=HIDDEN', $url);
        $this->log_info('Making API request to: ' . $log_url);

        // Try with retry mechanism
        while ($retries <= $this->max_retries) {
            // Initialize cURL
            $ch = curl_init();
            if (!$ch) {
                $this->log_error('curl_init() failed');
                return false;
            }

            try {
                // Setup verbose logging for debugging
                $verbose = fopen('php://temp', 'w+');

                // Set basic options that should work on all PHP versions
                $this->setup_curl_options($ch, $url, $options, $verbose);

                // Execute the request and capture performance metrics
                $request_start = microtime(true);
                $response = curl_exec($ch);
                $request_time = microtime(true) - $request_start;

                // Get HTTP status code and other info
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
                $connect_time = curl_getinfo($ch, CURLINFO_CONNECT_TIME);

                // Get verbose debug information
                rewind($verbose);
                $verbose_log = stream_get_contents($verbose);
                fclose($verbose);

                // Check for cURL errors
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    $errno = curl_errno($ch);
                    $this->log_error('cURL error #' . $errno . ': ' . $error);
                    $this->log_debug('cURL verbose info: ' . $verbose_log);

                    // Retry on connection errors or timeouts
                    if (in_array($errno, [CURLE_COULDNT_CONNECT, CURLE_OPERATION_TIMEOUTED]) &&
                        $retries < $this->max_retries) {
                        $retries++;
                        curl_close($ch);
                        $wait_time = $this->calculate_backoff_time($retries);
                        $this->log_warning("Retry {$retries}/{$this->max_retries} for API request, waiting {$wait_time}s");
                        sleep($wait_time);
                        continue;
                    }

                    curl_close($ch);
                    return false;
                }

                curl_close($ch);

                // Process the response based on HTTP status code
                if ($http_code >= 200 && $http_code < 300) {
                    // Success - parse the JSON response
                    $result = $this->parse_json_response($response, $http_code, $request_time);
                    if ($result !== false) {
                        return $result;
                    }
                    return false;
                } else if (($http_code >= 500 || $http_code === 429) && $retries < $this->max_retries) {
                    // Server error or rate limiting - retry with backoff
                    $retries++;
                    $wait_time = $this->calculate_backoff_time($retries);
                    $this->log_warning("Retry {$retries}/{$this->max_retries} for API request due to HTTP code {$http_code}, waiting {$wait_time}s");
                    $this->log_debug('Response content: ' . substr($response, 0, 500) . '...');
                    sleep($wait_time);
                    continue;
                } else {
                    // Other error - log details and return false
                    $this->log_error("API request failed with HTTP code: {$http_code}");
                    $this->log_debug('Request timing: total=' . round($total_time, 2) . 's, connect=' . round($connect_time, 2) . 's');
                    $this->log_error_response($response);
                    return false;
                }
            } catch (Exception $e) {
                $this->log_error('Exception during cURL execution: ' . $e->getMessage());
                $this->log_debug('Stack trace: ' . $e->getTraceAsString());
                curl_close($ch);
                return false;
            }
        }

        $total_time = microtime(true) - $start_time;
        $this->log_error("API request failed after {$retries} retries and " . round($total_time, 2) . " seconds");
        return false;
    }

    /**
     * Set up cURL options for the reques
     *
     * @param resource $ch The cURL handle
     * @param string $url The URL to reques
     * @param array $options Additional cURL options
     * @param resource $verbose The file handle for verbose outpu
     */
    private function setup_curl_options($ch, $url, $options, $verbose) {
        // Basic options that should work everywhere
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        // Add options from the user that we need for the API call
        if (isset($options[CURLOPT_POST])) {
            curl_setopt($ch, CURLOPT_POST, $options[CURLOPT_POST]);
        }

        if (isset($options[CURLOPT_POSTFIELDS])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options[CURLOPT_POSTFIELDS]);
        }

        if (isset($options[CURLOPT_HTTPHEADER])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options[CURLOPT_HTTPHEADER]);
        }

        // Set up verbose logging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
    }

    /**
     * Calculate exponential backoff time for retries
     *
     * @param int $retry_count The current retry coun
     * @return int The number of seconds to wai
     */
    private function calculate_backoff_time($retry_count) {
        // Exponential backoff with jitter: 2^retry * (0.5 + random(0, 0.5))
        $base = pow(2, $retry_count);
        $jitter = 0.5 + (mt_rand(0, 1000) / 2000); // Random value between 0.5 and 1.0
        return ceil($base * $jitter);
    }

    /**
     * Parse JSON response with error handling
     *
     * @param string $response The raw response string
     * @param int $http_code The HTTP status code
     * @param float $request_time The time taken for the reques
     * @return array|false Parsed JSON array or false on failure
     */
    private function parse_json_response($response, $http_code, $request_time) {
        try {
            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $this->log_info('API request successful with HTTP code ' . $http_code . ' in ' . round($request_time, 2) . 's');
            return $result;
        } catch (Exception $e) {
            $this->log_error('JSON decode error: ' . $e->getMessage());
            $this->log_debug('Response content: ' . substr($response, 0, 500) . '...');
            return false;
        }
    }

    /**
     * Log error response with detailed information
     *
     * @param string $response The raw response string
     */
    private function log_error_response($response) {
        // Try to parse the response as JSON even in case of error
        try {
            $error_json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($error_json)) {
                $this->log_error('Error response: ' . json_encode($error_json));

                // Extract specific error details if available
                if (isset($error_json['error'])) {
                    $error = $error_json['error'];
                    $message = isset($error['message']) ? $error['message'] : 'Unknown error';
                    $type = isset($error['type']) ? $error['type'] : 'unknown';
                    $code = isset($error['code']) ? $error['code'] : 'none';

                    $this->log_error("API error details: Type=$type, Code=$code, Message=$message");
                }
            } else {
                $this->log_error('Non-JSON response content: ' . substr($response, 0, 500) . '...');
            }
        } catch (Exception $e) {
            $this->log_error('Response content (could not parse JSON): ' . substr($response, 0, 500) . '...');
        }
    }

    /**
     * Log an error message
     *
     * @param string $message The message to log
     */
    protected function log_error($message) {
        // Always log errors
        error_log('[ERROR] ' . $message);
    }

    /**
     * Log a warning message
     *
     * @param string $message The message to log
     */
    protected function log_warning($message) {
        // Only log warnings if debug is enabled
        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            error_log('[WARNING] ' . $message);
        }
    }

    /**
     * Log an info message
     *
     * @param string $message The message to log
     */
    protected function log_info($message) {
        // Only log info if debug is enabled
        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            error_log('[INFO] ' . $message);
        }
    }

    /**
     * Log a debug message
     *
     * @param string $message The message to log
     */
    protected function log_debug($message) {
        // Only log debug if debug is enabled
        if (isset($_REQUEST['debug']) && $_REQUEST['debug'] == 1) {
            error_log('[DEBUG] ' . $message);
        }
    }

    /**
     * Abstract methods to be implemented by concrete classes
     */
    abstract protected function prepare_request_data($messages);
    abstract protected function extract_response_text($result);
    abstract public function send_request($messages);
}

/**
 * OpenAI API client implementation
 *
 * This class provides integration with OpenAI's GPT models via their Chat Completions API.
 * It supports all current OpenAI models including GPT-4, GPT-4o, and others.
 */
class block_tutoring_machine_openai_client extends block_tutoring_machine_api_client_base {
    /** @var array $supported_json_models Models that support JSON response format */
    protected $supported_json_models = [
        'gpt-4-turbo', 'gpt-4o', 'gpt-4o-mini', 'gpt-4.1-nano', 'gpt-4.1-mini',
        'gpt-4-vision-preview', 'gpt-4-1106-preview', 'gpt-4-0613'
    ];

    /**
     * Constructor
     *
     * @param string $api_key The API key for authentication
     */
    public function __construct($api_key) {
        parent::__construct($api_key);
        $this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->model = 'gpt-4o'; // Default to the most capable general-purpose model
    }

    /**
     * Prepare request data for OpenAI API
     *
     * Formats the request data according to OpenAI's API specifications.
     *
     * @param array $messages The array of messages to send
     * @return array The prepared request data
     */
    protected function prepare_request_data($messages) {
        // Validate and sanitize messages
        $validated_messages = $this->validate_messages($messages);

        // Prepare the base request data
        $post_data = [
            'model' => $this->model,
            'messages' => $validated_messages,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'top_p' => $this->top_p
        ];

        // Add response format if using supported models and JSON format is requested
        if (in_array($this->model, $this->supported_json_models) &&
            $this->response_format === 'json') {
            $post_data['response_format'] = ['type' => 'json_object'];
            $this->log_info("Setting JSON response format for model {$this->model}");
        }

        return $post_data;
    }

    /**
     * Send a request to the OpenAI API
     *
     * Handles the complete request lifecycle including error handling and logging.
     *
     * @param array $messages The array of messages to send
     * @return array|false The response data or false on failure
     */
    public function send_request($messages) {
        // Validate API key
        if (empty($this->api_key)) {
            $this->log_error('OpenAI API key is not configured');
            return false;
        }

        // Prepare request data
        try {
            $post_data = $this->prepare_request_data($messages);
            $json_data = json_encode($post_data, JSON_THROW_ON_ERROR);

            // Log the request data for debugging (with sensitive info removed)
            $this->log_request_data($post_data);
        } catch (Exception $e) {
            $this->log_error('Failed to prepare OpenAI request: ' . $e->getMessage());
            return false;
        }

        // Set cURL options
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key,
                'User-Agent: MoodleTutoring Machine/3.0',
                'OpenAI-Beta: assistants=v1' // Include beta features if available
            ]
        ];

        // Execute reques
        $result = $this->execute_curl_request($this->api_endpoint, $options);

        // Check for API errors
        if ($result === false) {
            $this->log_error('OpenAI API request failed completely');
            return false;
        }

        if (isset($result['error'])) {
            $this->handle_api_error($result['error']);
            return false;
        }

        return $result;
    }

    /**
     * Extract the response text from API resul
     *
     * Parses the OpenAI response format to extract the generated text.
     *
     * @param array $result API response data
     * @return string The extracted response tex
     */
    protected function extract_response_text($result) {
        // Check if the response has the expected structure
        if (!isset($result['choices'][0]['message']['content'])) {
            $this->log_warning('Unexpected OpenAI response format: content field missing');
            return '';
        }

        // Get and sanitize the response conten
        $content = $result['choices'][0]['message']['content'];

        // Log completion tokens for debugging/monitoring
        if (isset($result['usage']['completion_tokens'])) {
            $this->log_info('OpenAI completion used ' . $result['usage']['completion_tokens'] . ' tokens');
        }

        return trim(clean_param($content, PARAM_TEXT));
    }

    /**
     * Send a chat completion request and return the response tex
     *
     * Main method to be called by external code to get AI completions.
     *
     * @param array $messages The array of messages to send
     * @return string|false The response text or false on failure
     */
    public function get_completion($messages) {
        $result = $this->send_request($messages);

        if (!$result) {
            return false;
        }

        return $this->extract_response_text($result);
    }

    /**
     * Log request data with sensitive information removed
     *
     * @param array $post_data The request data to log
     */
    private function log_request_data($post_data) {
        $log_data = $post_data;

        // Mask message content for privacy
        if (isset($log_data['messages']) && is_array($log_data['messages'])) {
            foreach ($log_data['messages'] as $key => $msg) {
                if (isset($msg['content'])) {
                    $log_data['messages'][$key]['content'] = substr($msg['content'], 0, 20) . '... (truncated)';
                }
            }
        }

        $this->log_info('OpenAI request data: ' . json_encode($log_data));
        $this->log_info('OpenAI endpoint: ' . $this->api_endpoint);
        $this->log_info('OpenAI model: ' . $this->model);
    }

    /**
     * Handle API errors with detailed logging
     *
     * @param array $error The error object from the API response
     */
    private function handle_api_error($error) {
        $error_message = isset($error['message']) ? $error['message'] : 'Unknown OpenAI API error';
        $error_type = isset($error['type']) ? $error['type'] : 'unknown';
        $error_code = isset($error['code']) ? $error['code'] : 'none';

        $this->log_error("OpenAI API error: Type=$error_type, Code=$error_code, Message=$error_message");

        // Provide more specific guidance based on error type
        switch ($error_type) {
            case 'invalid_request_error':
                $this->log_error('Check request format, parameters, or model availability');
                break;
            case 'authentication_error':
                $this->log_error('Invalid API key or authentication issue');
                break;
            case 'permission_error':
                $this->log_error('API key does not have permission to use this model');
                break;
            case 'rate_limit_error':
                $this->log_error('Rate limit exceeded. Consider implementing request throttling');
                break;
            case 'quota_error':
                $this->log_error('Account quota or limits exceeded');
                break;
            default:
                $this->log_error('Unexpected error type from OpenAI API');
                break;
        }
    }
}

/**
 * Google Gemini API client implementation
 *
 * This class provides integration with Google's Gemini models via their Generative Language API.
 * It supports Gemini models including gemini-1.5-pro and gemini-1.5-flash.
 */
class block_tutoring_machine_google_client extends block_tutoring_machine_api_client_base {
    /** @var array $supported_json_models Models that support JSON response format */
    protected $supported_json_models = [
        'gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-pro'
    ];

    /**
     * Constructor
     *
     * @param string $api_key The API key for authentication
     */
    public function __construct($api_key) {
        parent::__construct($api_key);
        $this->api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/';
        $this->model = 'gemini-1.5-pro'; // Default to the most capable general-purpose model
    }

    /**
     * Prepare request data for Google Gemini API
     *
     * Formats the request data according to Google Gemini's API specifications,
     * including converting between different message formats and handling system messages.
     *
     * @param array $messages The array of messages to send
     * @return array The prepared request data
     */
    protected function prepare_request_data($messages) {
        // Validate and sanitize messages
        $validated_messages = $this->validate_messages($messages);

        // Convert OpenAI-compatible message format to Gemini forma
        $gemini_messages = $this->convert_to_gemini_format($validated_messages);

        // Prepare the base request data
        $post_data = [
            'contents' => $gemini_messages,
            'generationConfig' => [
                'temperature' => $this->temperature,
                'topP' => $this->top_p,
                'maxOutputTokens' => $this->max_tokens
            ]
        ];

        // Add response format if using supported models and JSON format is requested
        if (in_array($this->model, $this->supported_json_models) &&
            $this->response_format === 'json') {
            $post_data['generationConfig']['responseSchema'] = [
                'type' => 'object',
                'format' => 'json'
            ];
            $this->log_info("Setting JSON response format for model {$this->model}");
        }

        return $post_data;
    }

    /**
     * Convert OpenAI-compatible message format to Google Gemini forma
     *
     * @param array $messages The array of messages in OpenAI forma
     * @return array The messages in Gemini forma
     */
    private function convert_to_gemini_format($messages) {
        $gemini_messages = [];
        $system_content = '';

        // First pass: collect system messages and convert others
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // Gemini doesn't support system messages directly,
                // so we collect them to add as contex
                $system_content .= $message['content'] . "\n\n";
            } else {
                $gemini_messages[] = [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }
        }

        // If we have system content, prepend it to the first user message
        // or create a new user message if there are none
        if (!empty($system_content)) {
            $gemini_messages = $this->handle_system_content($gemini_messages, $system_content);
        }

        $this->log_info('Converted ' . count($messages) . ' messages to Gemini format');
        return $gemini_messages;
    }

    /**
     * Handle system content for Gemini API
     *
     * Gemini doesn't support system messages directly, so we need to
     * inject system content into a user message.
     *
     * @param array $gemini_messages The messages in Gemini forma
     * @param string $system_content The collected system conten
     * @return array The updated Gemini messages
     */
    private function handle_system_content($gemini_messages, $system_content) {
        $system_prefix = "You are an AI assistant with the following instructions to follow:\n\n" .
                          $system_content .
                          "\nPlease follow these instructions carefully in your responses.";

        if (!empty($gemini_messages)) {
            // Find the first user message
            foreach ($gemini_messages as $key => $message) {
                if ($message['role'] === 'user') {
                    // Prepend system content to this user message
                    $gemini_messages[$key]['parts'][0]['text'] =
                        $system_prefix . "\n\n" . $gemini_messages[$key]['parts'][0]['text'];
                    $this->log_info('Added system content to existing user message');
                    return $gemini_messages;
                }
            }
        }

        // No user messages found, create a new one
        $gemini_messages[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $system_prefix]
            ]
        ];
        $this->log_info('Created new user message with system content');

        return $gemini_messages;
    }

    /**
     * Send a request to the Google Gemini API
     *
     * Handles the complete request lifecycle including error handling and logging.
     *
     * @param array $messages The array of messages to send
     * @return array|false The response data or false on failure
     */
    public function send_request($messages) {
        // Validate API key
        if (empty($this->api_key)) {
            $this->log_error('Google Gemini API key is not configured');
            return false;
        }

        // Prepare request data
        try {
            $post_data = $this->prepare_request_data($messages);
            $json_data = json_encode($post_data, JSON_THROW_ON_ERROR);

            // Log the request data for debugging (with sensitive info removed)
            $this->log_request_data($post_data);
        } catch (Exception $e) {
            $this->log_error('Failed to prepare Google Gemini request: ' . $e->getMessage());
            return false;
        }

        // Build the complete URL with API key
        $url = $this->api_endpoint . $this->model . ':generateContent?key=' . $this->api_key;
        $this->log_info('Google API endpoint: ' . $this->api_endpoint . $this->model . ':generateContent (key parameter hidden)');

        // Set cURL options
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: MoodleTutoring Machine/3.0'
            ]
        ];

        // Execute reques
        $result = $this->execute_curl_request($url, $options);

        // Check for API errors
        if ($result === false) {
            $this->log_error('Google Gemini API request failed completely');
            return false;
        }

        if (isset($result['error'])) {
            $this->handle_api_error($result['error']);
            return false;
        }

        return $result;
    }

    /**
     * Extract the response text from API resul
     *
     * Parses the Google Gemini response format to extract the generated text.
     *
     * @param array $result API response data
     * @return string The extracted response tex
     */
    protected function extract_response_text($result) {
        // Check if the response has the expected structure
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $this->log_warning('Unexpected Google Gemini response format: text field missing');
            return '';
        }

        // Get and sanitize the response conten
        $content = $result['candidates'][0]['content']['parts'][0]['text'];

        // Log usage information if available
        if (isset($result['usageMetadata'])) {
            $this->log_usage_info($result['usageMetadata']);
        }

        return trim(clean_param($content, PARAM_TEXT));
    }

    /**
     * Send a chat completion request and return the response tex
     *
     * Main method to be called by external code to get AI completions.
     *
     * @param array $messages The array of messages to send
     * @return string|false The response text or false on failure
     */
    public function get_completion($messages) {
        $result = $this->send_request($messages);

        if (!$result) {
            return false;
        }

        return $this->extract_response_text($result);
    }

    /**
     * Log request data with sensitive information removed
     *
     * @param array $post_data The request data to log
     */
    private function log_request_data($post_data) {
        $log_data = $post_data;

        // Mask message content for privacy
        if (isset($log_data['contents']) && is_array($log_data['contents'])) {
            foreach ($log_data['contents'] as $key => $msg) {
                if (isset($msg['parts'][0]['text'])) {
                    $log_data['contents'][$key]['parts'][0]['text'] = substr($msg['parts'][0]['text'], 0, 20) . '... (truncated)';
                }
            }
        }

        $this->log_info('Google Gemini request data: ' . json_encode($log_data));
        $this->log_info('Google Gemini endpoint: ' . $this->api_endpoint . $this->model . ':generateContent');
        $this->log_info('Google Gemini model: ' . $this->model);
    }

    /**
     * Handle API errors with detailed logging
     *
     * @param array $error The error object from the API response
     */
    private function handle_api_error($error) {
        $error_message = isset($error['message']) ? $error['message'] : 'Unknown Google Gemini API error';
        $error_code = isset($error['code']) ? $error['code'] : 'unknown';
        $error_status = isset($error['status']) ? $error['status'] : 'unknown';

        $this->log_error("Google Gemini API error: Status=$error_status, Code=$error_code, Message=$error_message");

        // Provide more specific guidance based on error code
        switch ($error_code) {
            case 400:
                $this->log_error('Bad request - Check request format, parameters, or model name');
                break;
            case 401:
                $this->log_error('Unauthorized - Check your API key');
                break;
            case 403:
                $this->log_error('Forbidden - API key does not have permission to use this model');
                break;
            case 429:
                $this->log_error('Rate limit exceeded. Consider implementing request throttling');
                break;
            case 500:
            case 501:
            case 502:
            case 503:
                $this->log_error('Server error - Try again later');
                break;
            default:
                $this->log_error('Unexpected error code from Google Gemini API');
                break;
        }
    }

    /**
     * Log usage information from the API response
     *
     * @param array $usage_metadata The usage metadata from the response
     */
    private function log_usage_info($usage_metadata) {
        if (isset($usage_metadata['totalTokenCount'])) {
            $this->log_info('Google Gemini used ' . $usage_metadata['totalTokenCount'] . ' total tokens');
        }

        if (isset($usage_metadata['promptTokenCount'])) {
            $this->log_info('Google Gemini prompt used ' . $usage_metadata['promptTokenCount'] . ' tokens');
        }

        if (isset($usage_metadata['candidatesTokenCount'])) {
            $this->log_info('Google Gemini response used ' . $usage_metadata['candidatesTokenCount'] . ' tokens');
        }
    }
}

/**
 * API provider implementations for other services can be added here
 * by extending the block_tutoring_machine_api_client_base abstract class
 */

/**
 * Factory class for creating API clients
 */
class block_tutoring_machine_api_client {
    /**
     * Create an API client for the specified model/provider
     *
     * @param string $api_key The API key
     * @param string $model The model identifier (with provider prefix)
     * @return block_tutoring_machine_api_client_interface The API clien
     */
    public static function create($api_key, $model = null) {
        // Get provider from model string (format: "provider:model")
        $provider = 'openai'; // Default provider
        $actual_model = $model;

        if (!empty($model) && strpos($model, ':') !== false) {
            list($provider, $actual_model) = explode(':', $model, 2);
        }

        // Check if a provider-specific API key is available
        $config = get_config('block_tutoring_machine');
        $provider_api_key = $api_key; // Default to provided key

        switch ($provider) {
            case 'google':
                if (!empty($config->google_apikey)) {
                    $provider_api_key = $config->google_apikey;
                }
                $client = new block_tutoring_machine_google_client($provider_api_key);
                break;

            case 'openai':
            default:
                if (!empty($config->openai_apikey)) {
                    $provider_api_key = $config->openai_apikey;
                }
                $client = new block_tutoring_machine_openai_client($provider_api_key);
                break;
        }

        // Set the model if provided (without provider prefix)
        if (!empty($actual_model)) {
            $client->set_model($actual_model);
        }

        return $client;
    }

    /**
     * Private constructor to enforce usage of factory method
     * The factory pattern is used to create appropriate API clients
     */
    private function __construct() {
        // Constructor is private to enforce usage of create() factory method
    }

    // Magic methods removed - factory pattern used exclusively
}