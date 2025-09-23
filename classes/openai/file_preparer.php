<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Handles preparation and upload of context files for OpenAI Responses API.
 *
 * @package    block_tutoring_machine
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Prepare Moodle stored files for OpenAI Responses requests.
 */
class block_tutoring_machine_openai_file_preparer {
    /** @var callable */
    private $create_temp_file;

    /** @var callable */
    private $upload_file;

    /** @var callable */
    private $log_info;

    /** @var callable */
    private $log_warning;

    /** @var callable */
    private $log_error;

    /** @var array */
    private $skipped_files = [];

    /** Extensions that may be attached directly to Responses requests. */
    private const RESPONSES_ALLOWED_EXTENSIONS = [
        'pdf'
    ];

    /** Extensions that can be indexed in the vector store. */
    private const VECTOR_STORE_ALLOWED_EXTENSIONS = [
        'pdf',
        'doc', 'docx',
        'ppt', 'pptx',
        'rtf', 'odt', 'odp'
    ];

    /**
     * Constructor.
     *
     * @param array $callbacks Required callbacks: create_temp_file, upload_file, log_info, log_warning, log_error
     */
    public function __construct(array $callbacks) {
        $required = ['create_temp_file', 'upload_file', 'log_info', 'log_warning', 'log_error'];
        foreach ($required as $key) {
            if (!isset($callbacks[$key]) || !is_callable($callbacks[$key])) {
                throw new invalid_argument_exception('Missing callback: ' . $key);
            }
        }

        $this->create_temp_file = $callbacks['create_temp_file'];
        $this->upload_file = $callbacks['upload_file'];
        $this->log_info = $callbacks['log_info'];
        $this->log_warning = $callbacks['log_warning'];
        $this->log_error = $callbacks['log_error'];
    }

    /**
     * Prepare attachments for the Responses API.
     *
     * @param array $attachments Attachment definitions
     * @return array ['files'=>[], 'courseid'=>int|null, 'skipped'=>string[]]
     */
    public function prepare(array $attachments) {
        $uploaded = [];
        $processed_hashes = [];
        $course_id = null;
        $this->skipped_files = [];

        foreach ($attachments as $attachment) {
            if (empty($attachment['file']) || !($attachment['file'] instanceof stored_file)) {
                continue;
            }

            /** @var stored_file $file */
            $file = $attachment['file'];
            $hash = $file->get_contenthash();

            if ($course_id === null && isset($attachment['courseid'])) {
                $course_id = $attachment['courseid'];
            }

            if (isset($processed_hashes[$hash])) {
                $uploaded[] = $processed_hashes[$hash];
                continue;
            }

            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            if (!$this->is_vector_store_allowed($extension)) {
                $this->mark_skipped($file->get_filename());
                continue;
            }

            $cache_key = block_tutoring_machine_cache_manager::get_uploaded_file_key('openai', $hash);
            $cached_entry = block_tutoring_machine_cache_manager::get(block_tutoring_machine_cache_manager::CACHE_TYPE_UPLOADS, $cache_key);
            if ($this->is_cache_entry_reusable($file, $cached_entry)) {
                $file_info = [
                    'id' => $cached_entry['fileid'],
                    'label' => isset($attachment['label']) ? $attachment['label'] : $file->get_filename(),
                    'filename' => isset($cached_entry['filename']) ? $cached_entry['filename'] : $file->get_filename(),
                    'courseid' => $cached_entry['courseid'] ?? ($attachment['courseid'] ?? null),
                    'allow_responses' => $this->is_responses_allowed($extension)
                ];
                $uploaded[] = $file_info;
                $processed_hashes[$hash] = $file_info;
                call_user_func($this->log_info, 'Reusing cached OpenAI file id for ' . $file->get_filename());
                continue;
            }

            $temp_path = call_user_func($this->create_temp_file, $file);
            if (!$temp_path) {
                call_user_func($this->log_error, 'Failed to prepare temporary file for ' . $file->get_filename());
                $this->mark_skipped($file->get_filename());
                continue;
            }

            $upload_response = call_user_func($this->upload_file, $temp_path, $file->get_filename(), $file->get_mimetype());
            if (file_exists($temp_path)) {
                @unlink($temp_path);
            }

            if (!$upload_response || !isset($upload_response['id'])) {
                call_user_func($this->log_error, 'OpenAI file upload failed for ' . $file->get_filename());
                $this->mark_skipped($file->get_filename());
                continue;
            }

            $attachment_course_id = $attachment['courseid'] ?? $course_id;
            if ($course_id === null && $attachment_course_id !== null) {
                $course_id = $attachment_course_id;
            }

            $allow_responses = $this->is_responses_allowed($extension);
            $file_info = [
                'id' => $upload_response['id'],
                'label' => isset($attachment['label']) ? $attachment['label'] : $file->get_filename(),
                'filename' => $file->get_filename(),
                'courseid' => $attachment_course_id,
                'allow_responses' => $allow_responses
            ];

            if (!$allow_responses) {
                call_user_func($this->log_info, 'Uploaded vector-store-only file ' . $file->get_filename());
            }

            $uploaded[] = $file_info;
            $processed_hashes[$hash] = $file_info;

            block_tutoring_machine_cache_manager::set(
                block_tutoring_machine_cache_manager::CACHE_TYPE_UPLOADS,
                $cache_key,
                [
                    'fileid' => $upload_response['id'],
                    'filename' => $file->get_filename(),
                    'timemodified' => $file->get_timemodified(),
                    'mimetype' => $file->get_mimetype(),
                    'courseid' => $attachment_course_id
                ]
            );
        }

        if (!empty($uploaded)) {
            call_user_func($this->log_info, 'Uploaded ' . count($uploaded) . ' context files to OpenAI');
        }

        return [
            'files' => $uploaded,
            'courseid' => $course_id,
            'skipped' => $this->skipped_files
        ];
    }

    /**
     * Check if cached upload info can be reused.
     *
     * @param stored_file $file Moodle file
     * @param array|false $cached_entry Cached metadata
     * @return bool
     */
    private function is_cache_entry_reusable(stored_file $file, $cached_entry) {
        if (!$cached_entry || !isset($cached_entry['fileid'], $cached_entry['timemodified'])) {
            return false;
        }

        $filename = isset($cached_entry['filename']) ? $cached_entry['filename'] : $file->get_filename();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $this->is_vector_store_allowed($extension) &&
            $cached_entry['timemodified'] >= $file->get_timemodified();
    }

    /**
     * Determine if a file may be uploaded for vector store usage.
     *
     * @param string $extension File extension
     * @return bool
     */
    private function is_vector_store_allowed($extension) {
        return in_array($extension, self::VECTOR_STORE_ALLOWED_EXTENSIONS, true);
    }

    /**
     * Determine if a file may be referenced directly in Responses requests.
     *
     * @param string $extension File extension
     * @return bool
     */
    private function is_responses_allowed($extension) {
        return in_array($extension, self::RESPONSES_ALLOWED_EXTENSIONS, true);
    }

    /**
     * Track skipped files and add a warning entry.
     *
     * @param string $filename File name
     */
    private function mark_skipped($filename) {
        $this->skipped_files[] = $filename;
        call_user_func($this->log_warning, 'Skipping unsupported file for OpenAI upload: ' . $filename);
    }
}
