<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Cache manager class for Chatbot block.
 *
 * @package    block_chatbot
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Cache manager class for Chatbot block
 *
 * Manages caching of various content types used by the chatbot
 */
class block_chatbot_cache_manager {
    /**
     * Cache types
     */
    const CACHE_TYPE_PDF = 'pdf_content';
    const CACHE_TYPE_COURSE = 'course_content';
    const CACHE_TYPE_PAGE = 'page_content';
    const CACHE_TYPE_GLOSSARY = 'glossary_content';
    const CACHE_TYPE_H5P = 'h5p_content';
    const CACHE_TYPE_MISC = 'misc_content'; // For other module types like forums, quizzes, etc.
    
    /**
     * Default TTL values in seconds
     */
    const DEFAULT_TTL = 86400; // 24 hours
    const PDF_TTL = 604800;    // 1 week
    const COURSE_TTL = 43200;  // 12 hours
    
    /**
     * Get cached item from the appropriate cache store
     *
     * @param string $type Cache type
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public static function get($type, $key) {
        global $DB;
        
        $cache = self::get_cache_for_type($type);
        return $cache->get($key);
    }
    
    /**
     * Store item in the appropriate cache store
     *
     * @param string $type Cache type
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param array $metadata Optional metadata to store with cached item
     * @return bool Success or failure
     */
    public static function set($type, $key, $data, $metadata = []) {
        $cache = self::get_cache_for_type($type);
        
        // Add timestamp to metadata
        $metadata['timestamp'] = time();
        
        // For database-backed caches, store metadata with the data
        if ($type == self::CACHE_TYPE_PDF) {
            $cache_data = [
                'data' => $data,
                'metadata' => $metadata
            ];
            return $cache->set($key, $cache_data);
        }
        
        // For MUC caches, store metadata separately if needed
        return $cache->set($key, $data);
    }
    
    /**
     * Check if a cached item is valid (not expired)
     *
     * @param string $type Cache type
     * @param string $key Cache key
     * @param int $ttl Time-to-live in seconds
     * @return bool True if valid, false otherwise
     */
    public static function is_valid($type, $key, $ttl = null) {
        // Get the data from cache
        $cached = self::get($type, $key);
        if (!$cached) {
            return false;
        }
        
        // Determine TTL to use
        if ($ttl === null) {
            switch ($type) {
                case self::CACHE_TYPE_PDF:
                    $ttl = self::PDF_TTL;
                    break;
                case self::CACHE_TYPE_COURSE:
                    $ttl = self::COURSE_TTL;
                    break;
                case self::CACHE_TYPE_PAGE:
                case self::CACHE_TYPE_GLOSSARY:
                case self::CACHE_TYPE_H5P:
                    $ttl = self::DEFAULT_TTL;
                    break;
                default:
                    $ttl = self::DEFAULT_TTL;
            }
        }
        
        // For DB caches, check timestamp in metadata
        if ($type == self::CACHE_TYPE_PDF) {
            if (!isset($cached['metadata']) || !isset($cached['metadata']['timestamp'])) {
                return false;
            }
            return (time() - $cached['metadata']['timestamp']) < $ttl;
        }
        
        // For MUC caches, they handle expiration automatically
        return true;
    }
    
    /**
     * Invalidate (delete) a cached item
     *
     * @param string $type Cache type
     * @param string $key Cache key
     * @return bool Success or failure
     */
    public static function invalidate($type, $key) {
        $cache = self::get_cache_for_type($type);
        return $cache->delete($key);
    }
    
    /**
     * Invalidate page content cache
     *
     * @param int $pageid The page ID
     * @return bool Success or failure
     */
    public static function invalidate_page_cache($pageid) {
        $key = self::get_page_content_key($pageid);
        return self::invalidate(self::CACHE_TYPE_PAGE, $key);
    }
    
    /**
     * Invalidate glossary content cache
     *
     * @param int $glossaryid The glossary ID
     * @return bool Success or failure
     */
    public static function invalidate_glossary_cache($glossaryid) {
        $key = self::get_glossary_content_key($glossaryid);
        return self::invalidate(self::CACHE_TYPE_GLOSSARY, $key);
    }
    
    /**
     * Invalidate H5P content cache
     *
     * @param int $h5pid The H5P activity ID
     * @return bool Success or failure
     */
    public static function invalidate_h5p_cache($h5pid) {
        $key = self::get_h5p_content_key($h5pid);
        return self::invalidate(self::CACHE_TYPE_H5P, $key);
    }
    
    /**
     * Invalidate PDF content cache
     *
     * @param string $contenthash The content hash of the PDF file
     * @return bool Success or failure
     */
    public static function invalidate_pdf_cache($contenthash) {
        $key = self::get_pdf_content_key($contenthash);
        return self::invalidate(self::CACHE_TYPE_PDF, $key);
    }
    
    /**
     * Invalidate course content cache
     *
     * @param int $courseid The course ID
     * @param array $config Optional configuration parameters
     * @return bool Success or failure
     */
    public static function invalidate_course_cache($courseid, $config = null) {
        $key = self::get_course_content_key($courseid, $config);
        return self::invalidate(self::CACHE_TYPE_COURSE, $key);
    }
    
    /**
     * Purge all cached items of a specific type
     *
     * @param string $type Cache type
     * @return bool Success or failure
     */
    public static function purge($type) {
        $cache = self::get_cache_for_type($type);
        return $cache->purge();
    }
    
    /**
     * Get the appropriate cache for a given type
     *
     * @param string $type Cache type
     * @return cache_application|object Cache instance
     */
    private static function get_cache_for_type($type) {
        // For PDF content, use the database cache
        if ($type == self::CACHE_TYPE_PDF) {
            return new block_chatbot_db_cache();
        }
        
        // For other types, use Moodle's caching API
        return cache::make('block_chatbot', $type);
    }
    
    /**
     * Generate a cache key for course content
     *
     * @param int $courseid Course ID
     * @param array $config Block configuration
     * @return string Cache key
     */
    public static function get_course_content_key($courseid, $config = null) {
        $key = "course_{$courseid}";
        
        // If config is provided, add a hash of the config to make the key unique
        if ($config) {
            $config_string = json_encode($config);
            $config_hash = md5($config_string);
            $key .= "_{$config_hash}";
        }
        
        return $key;
    }
    
    /**
     * Generate a cache key for PDF content
     *
     * @param string $contenthash Content hash of the file
     * @return string Cache key
     */
    public static function get_pdf_content_key($contenthash) {
        return "pdf_{$contenthash}";
    }
    
    /**
     * Generate a cache key for page content
     *
     * @param int $pageid Page ID
     * @return string Cache key
     */
    public static function get_page_content_key($pageid) {
        return "page_{$pageid}";
    }
    
    /**
     * Generate a cache key for glossary content
     *
     * @param int $glossaryid Glossary ID
     * @return string Cache key
     */
    public static function get_glossary_content_key($glossaryid) {
        return "glossary_{$glossaryid}";
    }
    
    /**
     * Generate a cache key for H5P content
     *
     * @param int $h5pid H5P activity ID
     * @return string Cache key
     */
    public static function get_h5p_content_key($h5pid) {
        return "h5p_{$h5pid}";
    }
    
    /**
     * Generate a generic cache key for any module type
     *
     * @param string $module_type The module type (forum, quiz, book, etc.)
     * @param int $instance_id The instance ID
     * @return string Cache key
     */
    public static function get_generic_content_key($module_type, $instance_id) {
        return "{$module_type}_{$instance_id}";
    }
}

/**
 * Database-backed cache implementation for PDF content
 *
 * This is used when we need to store large amounts of data
 * that might exceed the size limits of Moodle's caching API
 */
class block_chatbot_db_cache {
    /**
     * Get a cached item from the database
     *
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public function get($key) {
        global $DB;
        
        try {
            $record = $DB->get_record('block_chatbot_pdf_cache', ['contenthash' => $key]);
            if (!$record) {
                return false;
            }
            
            return [
                'data' => $record->content,
                'metadata' => [
                    'timestamp' => $record->timecached,
                    'timemodified' => $record->timemodified
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting PDF cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set a cached item in the database
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @return bool Success or failure
     */
    public function set($key, $data) {
        global $DB;
        
        try {
            // Extract data and metadata
            $content = $data['data'];
            $metadata = $data['metadata'];
            
            // Check if entry already exists
            $existing = $DB->get_record('block_chatbot_pdf_cache', ['contenthash' => $key]);
            
            $record = new stdClass();
            $record->contenthash = $key;
            $record->timemodified = $metadata['timemodified'] ?? time();
            $record->timecached = time();
            $record->content = $content;
            
            if ($existing) {
                // Update existing record
                $record->id = $existing->id;
                return $DB->update_record('block_chatbot_pdf_cache', $record);
            } else {
                // Insert new record
                return $DB->insert_record('block_chatbot_pdf_cache', $record) ? true : false;
            }
        } catch (Exception $e) {
            error_log("Error saving PDF cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a cached item from the database
     *
     * @param string $key Cache key
     * @return bool Success or failure
     */
    public function delete($key) {
        global $DB;
        
        try {
            return $DB->delete_records('block_chatbot_pdf_cache', ['contenthash' => $key]);
        } catch (Exception $e) {
            error_log("Error deleting PDF cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Purge all cached items from the database
     *
     * @return bool Success or failure
     */
    public function purge() {
        global $DB;
        
        try {
            return $DB->delete_records('block_chatbot_pdf_cache');
        } catch (Exception $e) {
            error_log("Error purging PDF cache: " . $e->getMessage());
            return false;
        }
    }
}