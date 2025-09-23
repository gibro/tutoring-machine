<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Message processor class for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to process messages and format responses
 */
class block_tutoring_machine_message_processor {
    /** @var string $response_text The raw response text */
    private $response_text;

    /**
     * Constructor
     *
     * @param string $response_text The raw response tex
     */
    public function __construct($response_text = '') {
        $this->response_text = $response_text;
    }

    /**
     * Set the response tex
     *
     * @param string $response_text The raw response tex
     * @return block_tutoring_machine_message_processor This instance for method chaining
     */
    public function set_response_text($response_text) {
        $this->response_text = $response_text;
        return $this;
    }

    /**
     * Get the raw response tex
     *
     * @return string The raw response tex
     */
    public function get_response_text() {
        return $this->response_text;
    }

    /**
     * Truncate response text to ensure complete sentences.
     * Limits text to a configurable character count and ensures it ends with a complete sentence.
     *
     * @param int $primary_limit Preferred maximum length before truncation kicks in.
     * @param int $secondary_limit Soft ceiling to allow a sentence to finish beyond the primary limit.
     * @return block_tutoring_machine_message_processor This instance for method chaining
     */
    public function truncate_response($primary_limit = 8000, $secondary_limit = 10000) {
        $primary_limit = max(0, (int)$primary_limit);
        $secondary_limit = max($primary_limit, (int)$secondary_limit);

        // If text is already under primary limit, return it as is
        if ($primary_limit === 0 || strlen($this->response_text) <= $primary_limit) {
            return $this;
        }

        // Find sentence endings (period, question mark, exclamation mark followed by space or end of string)
        preg_match_all('/[.!?](\s|$)/u', $this->response_text, $matches, PREG_OFFSET_CAPTURE);

        // If no sentence endings found, return the full tex
        if (empty($matches[0])) {
            return $this;
        }

        // Find the last sentence end position that's still within or close to our target length
        $last_sentence_end = null;
        $secondary_sentence_end = null;

        foreach ($matches[0] as $match) {
            $position = $match[1] + 1; // Include the period/question mark/exclamation mark

            // If the position is beyond our primary length but we don't have a primary end ye
            if ($position > $primary_limit && $last_sentence_end === null) {
                // Use the first sentence end we find after primary limi
                $last_sentence_end = $position;
            }

            // If the position is beyond our secondary length but we don't have a secondary end ye
            if ($position > $secondary_limit && $secondary_sentence_end === null) {
                // Use the first sentence end we find after secondary limi
                $secondary_sentence_end = $position;
                break;
            }

            // If we're still within our primary length, update the lastSentenceEnd
            if ($position <= $primary_limit) {
                $last_sentence_end = $position;
            }
            // If we're between primary and secondary limits, update the secondarySentenceEnd
            else if ($position <= $secondary_limit) {
                $secondary_sentence_end = $position;
            } else {
                // We've passed our secondary limit and already have sentence endings
                break;
            }
        }

        // If we found a secondary limit sentence ending, use that for a better conclusion
        if ($secondary_sentence_end !== null) {
            $this->response_text = substr($this->response_text, 0, $secondary_sentence_end);
            return $this;
        }

        // If we found a primary limit sentence ending, use tha
        if ($last_sentence_end !== null) {
            $this->response_text = substr($this->response_text, 0, $last_sentence_end);
            return $this;
        }

        // If no suitable sentence end was found, return the full tex
        return $this;
    }

    /**
     * Get response as an assistant message objec
     *
     * @return array Message object with role and conten
     */
    public function get_assistant_message() {
        return [
            'role' => 'assistant',
            'content' => $this->response_text
        ];
    }

    /**
     * Extract and sanitize message from request data
     *
     * @return string The sanitized message or empty string if not found
     */
    public static function get_message_from_request() {
        // Read incoming JSON data
        $input = file_get_contents("php://input");

        // Limit message size to prevent abuse
        $max_message_length = 8192; // 8KB should be more than enough for a message

        // If no data received, try POST or GET parameters
        if (empty($input)) {
            if (isset($_POST['message'])) {
                $message = $_POST['message'];
                // Truncate if too long
                if (strlen($message) > $max_message_length) {
                    $message = substr($message, 0, $max_message_length);
                    error_log("Warning: Truncated message from POST due to excessive length");
                }
                return clean_param($message, PARAM_TEXT);
            } else if (isset($_GET['message'])) {
                // Strongly discourage using GET for messages (less secure)
                error_log("Warning: Received message via GET which is less secure than POST");
                $message = $_GET['message'];
                if (strlen($message) > $max_message_length) {
                    $message = substr($message, 0, $max_message_length);
                    error_log("Warning: Truncated message from GET due to excessive length");
                }
                return clean_param($message, PARAM_TEXT);
            } else {
                return '';
            }
        }

        // Limit JSON input size
        if (strlen($input) > $max_message_length * 2) { // Allow for JSON structure overhead
            $input = substr($input, 0, $max_message_length * 2);
            error_log("Warning: Truncated raw input due to excessive length");
        }

        // Try to parse JSON
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $message = isset($data['message']) ? $data['message'] : '';
            if (strlen($message) > $max_message_length) {
                $message = substr($message, 0, $max_message_length);
                error_log("Warning: Truncated message from JSON due to excessive length");
            }
            return clean_param($message, PARAM_TEXT);
        }

        // If not valid JSON, try to parse as form data
        parse_str($input, $form_data);
        $message = isset($form_data['message']) ? $form_data['message'] : '';
        if (strlen($message) > $max_message_length) {
            $message = substr($message, 0, $max_message_length);
            error_log("Warning: Truncated message from form data due to excessive length");
        }

        return clean_param($message, PARAM_TEXT);
    }

    /**
     * Get and validate conversation history from reques
     *
     * @return array Validated conversation history or empty array if not found
     */
    public static function get_conversation_history_from_request() {
        $conversation_history = [];

        if (isset($_POST['history'])) {
            $history_json = clean_param($_POST['history'], PARAM_RAW);
            $conversation_history = self::validate_conversation_history($history_json);
        } else if (isset($_GET['history'])) {
            $history_json = clean_param($_GET['history'], PARAM_RAW);
            $conversation_history = self::validate_conversation_history($history_json);
        } else {
            // Try to parse from JSON inpu
            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['history'])) {
                    $conversation_history = self::validate_conversation_history_array($data['history']);
                }
            }
        }

        return $conversation_history;
    }

    /**
     * Validate and sanitize a conversation history from JSON string
     *
     * @param string $history_json JSON string containing conversation history
     * @return array Validated conversation history array
     */
    private static function validate_conversation_history($history_json) {
        $history = json_decode($history_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($history)) {
            error_log("Invalid conversation history JSON: " . json_last_error_msg());
            return [];
        }

        return self::validate_conversation_history_array($history);
    }

    /**
     * Validate and sanitize a conversation history array
     *
     * @param array $history Array containing conversation history
     * @return array Validated conversation history array
     */
    private static function validate_conversation_history_array($history) {
        $valid_history = [];

        if (!is_array($history)) {
            return [];
        }

        // Limit conversation history to prevent DoS attacks through excessive memory usage
        $max_messages = 100;
        $max_content_length = 10000; // 10KB per message
        $message_count = 0;

        foreach ($history as $message) {
            // Check if we've reached the max message limi
            if ($message_count >= $max_messages) {
                break;
            }

            // Check if message has required fields and valid role
            if (is_array($message) &&
                isset($message['role']) &&
                isset($message['content']) &&
                in_array($message['role'], ['user', 'assistant', 'system'])) {

                // Truncate content if it's too long
                $content = isset($message['content']) ? $message['content'] : '';
                if (strlen($content) > $max_content_length) {
                    $content = substr($content, 0, $max_content_length);
                    error_log("Warning: Truncated conversation history message due to excessive length");
                }

                $valid_history[] = [
                    'role' => clean_param($message['role'], PARAM_ALPHA),
                    'content' => clean_param($content, PARAM_TEXT)
                ];

                $message_count++;
            }
        }

        return $valid_history;
    }

    /**
     * Get and validate course ID from reques
     *
     * @return int|null Course ID or null if not found or invalid
     */
    public static function get_course_id_from_request() {
        $courseid = null;

        if (isset($_POST['courseid'])) {
            $courseid = clean_param($_POST['courseid'], PARAM_INT);
        } else if (isset($_GET['courseid'])) {
            $courseid = clean_param($_GET['courseid'], PARAM_INT);
        } else {
            // Try to parse from JSON inpu
            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['courseid'])) {
                    $courseid = clean_param($data['courseid'], PARAM_INT);
                }
            }
        }

        // Verify the course exists and user has access
        if (!empty($courseid)) {
            global $DB, $USER;

            // Check course exists
            if (!$DB->record_exists('course', ['id' => $courseid])) {
                return null;
            }

            // Check user is enrolled in the course
            $context = context_course::instance($courseid);
            if (!is_enrolled($context, $USER->id)) {
                return null;
            }
        }

        return $courseid;
    }

    /**
     * Get and validate block instance ID from reques
     *
     * @return int|null Block instance ID or null if not found or invalid
     */
    public static function get_block_id_from_request() {
        $blockid = null;

        if (isset($_POST['blockid'])) {
            $blockid = clean_param($_POST['blockid'], PARAM_INT);
        } else if (isset($_GET['blockid'])) {
            $blockid = clean_param($_GET['blockid'], PARAM_INT);
        } else {
            // Try to parse from JSON inpu
            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['blockid'])) {
                    $blockid = clean_param($data['blockid'], PARAM_INT);
                }
            }
        }

        // Verify the block exists and is of correct type
        if (!empty($blockid)) {
            global $DB;

            $block_record = $DB->get_record('block_instances', ['id' => $blockid], '*');
            if (!$block_record) {
                return null;
            }

            if ($block_record->blockname !== 'tutoring_machine') {
                return null;
            }
        }

        return $blockid;
    }

    /**
     * Get metaprompt from block instance configuration with security checks
     *
     * @param int|null $blockid Block instance ID
     * @return string Metaprompt or empty string if not found
     */
    public static function get_metaprompt($blockid) {
        global $DB, $USER;

        if (empty($blockid)) {
            return '';
        }

        try {
            // Get block configuration
            $block_record = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
            if (!$block_record) {
                return '';
            }

            // Check if it's the correct block type
            if ($block_record->blockname !== 'tutoring_machine') {
                return '';
            }

            // Check user has access to this block
            $context = context::instance_by_id($block_record->parentcontextid);
            $course_context = $context->get_course_context(false);

            if ($course_context && !is_enrolled($course_context, $USER->id)) {
                return '';
            }

            // Decode block configuration
            $config = unserialize(base64_decode($block_record->configdata));
            if (!$config || !isset($config->metaprompt)) {
                return '';
            }

            return clean_param(trim($config->metaprompt), PARAM_TEXT);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Send JSON response to client with security headers
     *
     * @param string $response_text Text to send
     * @param array|null $last_message The last message to add to history
     */
    public static function send_response($response_text, $last_message = null) {
        // Add security headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
        header('X-XSS-Protection: 1; mode=block');

        try {
            // Make sure to not re-sanitize, as this can corrupt the data
            // Only sanitize if it's coming from external sources, not for our own processing
            $response = ["response" => $response_text];

            // Include last message if available
            if ($last_message) {
                $response["lastMessage"] = $last_message;
            }

            $json_output = json_encode($response);
            if ($json_output === false) {
                throw new Exception("JSON encoding failed: " . json_last_error_msg());
            }

            echo $json_output;

        } catch (Exception $e) {
            // Use a simpler encoding to ensure it works
            echo '{"error":true,"message":"Ein Fehler ist aufgetreten."}';
        }
    }
}
