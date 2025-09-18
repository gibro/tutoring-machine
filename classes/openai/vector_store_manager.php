<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Helper for managing OpenAI vector stores.
 *
 * @package    block_tutoring_machine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Ensures vector stores exist and keeps cache entries in sync with OpenAI.
 */
class block_tutoring_machine_openai_vector_store_manager {
    /** @var callable */
    private $post_json;

    /** @var callable */
    private $get_json;

    /** @var callable */
    private $log_info;

    /** @var callable */
    private $log_warning;

    /** @var callable */
    private $log_error;

    /**
     * Constructor.
     *
     * @param array $callbacks Required callbacks: post_json, get_json, log_info, log_warning, log_error
     */
    public function __construct(array $callbacks) {
        $required = ['post_json', 'get_json', 'log_info', 'log_warning', 'log_error'];
        foreach ($required as $key) {
            if (!isset($callbacks[$key]) || !is_callable($callbacks[$key])) {
                throw new invalid_argument_exception('Missing callback: ' . $key);
            }
        }

        $this->post_json = $callbacks['post_json'];
        $this->get_json = $callbacks['get_json'];
        $this->log_info = $callbacks['log_info'];
        $this->log_warning = $callbacks['log_warning'];
        $this->log_error = $callbacks['log_error'];
    }

    /**
     * Ensure a vector store exists and contains the provided file IDs.
     *
     * @param int|null $course_id Course identifier
     * @param array $file_ids File identifiers to attach
     * @return string|null Vector store id
     */
    public function ensure($course_id, array $file_ids) {
        if (empty($file_ids)) {
            return null;
        }

        $course_id = $course_id ?? 0;
        $cache_key = block_tutoring_machine_cache_manager::get_vector_store_key('openai', $course_id);
        $store_entry = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_VECTOR_STORE, $cache_key);

        $vector_store_id = $store_entry['id'] ?? null;
        $known_ids = $store_entry['fileids'] ?? [];

        if ($vector_store_id && !$this->exists($vector_store_id)) {
            call_user_func($this->log_warning, 'Cached vector store ' . $vector_store_id . ' no longer exists – recreating.');
            $vector_store_id = null;
            $known_ids = [];
        }

        if (!$vector_store_id) {
            $create_payload = ['name' => 'tutoring-machine-course-' . $course_id];
            $response = call_user_func($this->post_json, 'https://api.openai.com/v1/vector_stores', $create_payload);

            if (!$response || !isset($response['id'])) {
                call_user_func($this->log_error, 'Failed to create OpenAI vector store for course ' . $course_id);
                return null;
            }

            $vector_store_id = $response['id'];
            $known_ids = [];
        }

        $new_ids = array_values(array_diff($file_ids, $known_ids));
        if (!empty($new_ids)) {
            $batch_payload = ['file_ids' => $new_ids];
            $endpoint = 'https://api.openai.com/v1/vector_stores/' . $vector_store_id . '/file_batches';
            $batch_response = call_user_func($this->post_json, $endpoint, $batch_payload);

            if (!$batch_response || !isset($batch_response['id'])) {
                call_user_func($this->log_error, 'Failed to attach files to vector store ' . $vector_store_id);
            } else {
                $known_ids = array_values(array_unique(array_merge($known_ids, $file_ids)));
            }
        }

        block_tutoring_machine_cache_manager::set(
            block_tutoring_machine_cache_manager::CACHE_TYPE_VECTOR_STORE,
            $cache_key,
            ['id' => $vector_store_id, 'fileids' => $known_ids]
        );

        return $vector_store_id;
    }

    /**
     * Check if a vector store exists.
     *
     * @param string $vector_store_id Identifier
     * @return bool
     */
    private function exists($vector_store_id) {
        $url = 'https://api.openai.com/v1/vector_stores/' . urlencode($vector_store_id);
        $response = call_user_func($this->get_json, $url);

        if ($response === false) {
            return false;
        }

        return isset($response['id']) && $response['id'] === $vector_store_id;
    }
}
