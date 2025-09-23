<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Analytics manager for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage teaching analytics for the Tutoring Machine.
 *
 * This class handles the storage, retrieval, and analysis of anonymized
 * user queries for teaching analytics purposes.
 */
class block_tutoring_machine_analytics_manager {
    /** @var int $courseid The course ID */
    protected $courseid;

    /** @var int $blockinstanceid The block instance ID */
    protected $blockinstanceid;

    /** @var object $config Block configuration */
    protected $config;

    /**
     * Constructor
     *
     * @param int $courseid The course ID
     * @param int $blockinstanceid The block instance ID
     * @param object $config Block configuration
     */
    public function __construct($courseid, $blockinstanceid, $config = null) {
        $this->courseid = $courseid;
        $this->blockinstanceid = $blockinstanceid;
        $this->config = $config;

        if ($this->config === null) {
            // Load block configuration if not provided
            $this->load_config();
        }
    }

    /**
     * Load block configuration
     */
    protected function load_config() {
        global $DB;

        $blockinstance = $DB->get_record('block_instances', array('id' => $this->blockinstanceid), '*', MUST_EXIST);
        $this->config = unserialize(base64_decode($blockinstance->configdata));
    }

    /**
     * Check if analytics are enabled for this block instance
     *
     * @return bool True if analytics are enabled
     */
    public function is_analytics_enabled() {
        if (!isset($this->config->enable_analytics)) {
            return false;
        }

        return (bool)$this->config->enable_analytics;
    }

    /**
     * Get data retention period in days
     *
     * @return int Retention period in days
     */
    public function get_retention_period() {
        if (!isset($this->config->analytics_retention)) {
            return 30; // Default to 30 days
        }

        return (int)$this->config->analytics_retention;
    }

    /**
     * Log a user query for analytics
     *
     * @param string $query The user query (will be anonymized)
     * @param string $model The AI model used
     * @param int $tokens The number of tokens used
     * @return bool Success
     */
    public function log_query($query, $model = '', $tokens = 0) {
        global $DB;

        if (!$this->is_analytics_enabled()) {
            return false;
        }

        // Anonymize the query by removing any potential PII
        $anonymized_query = $this->anonymize_query($query);

        // Try to categorize the query
        $querytype = $this->categorize_query($anonymized_query);

        // Create the record
        $record = new stdClass();
        $record->courseid = $this->courseid;
        $record->blockinstanceid = $this->blockinstanceid;
        $record->timecreated = time();
        $record->query = $anonymized_query;
        $record->querytype = $querytype;
        $record->tokens = $tokens;
        $record->model = $model;

        // Insert the record
        try {
            $DB->insert_record('block_tutoring_machine_analytics', $record);
            return true;
        } catch (Exception $e) {
            debugging('Error logging query for analytics: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get analytics data for dashboard display
     *
     * @param int $days Number of days to look back
     * @param int $limit Maximum number of queries to return
     * @return array Analytics data
     */
    public function get_analytics_data($days = 30, $limit = 10) {
        global $DB;

        // Default result structure
        $result = array(
            'total_queries' => 0,
            'most_common' => array(),
            'by_type' => array(),
            'time_period' => $days
        );

        if (!$this->is_analytics_enabled()) {
            return $result;
        }

        // Calculate the time threshold
        $time_threshold = time() - ($days * 24 * 60 * 60);

        // Get total number of queries
        $sql = "SELECT COUNT(*) FROM {block_tutoring_machine_analytics}
                WHERE courseid = :courseid AND blockinstanceid = :blockinstanceid
                AND timecreated > :timethreshold";
        $params = array(
            'courseid' => $this->courseid,
            'blockinstanceid' => $this->blockinstanceid,
            'timethreshold' => $time_threshold
        );

        $result['total_queries'] = $DB->count_records_sql($sql, $params);

        // Get most common queries
        $sql = "SELECT query, COUNT(*) as count
                FROM {block_tutoring_machine_analytics}
                WHERE courseid = :courseid AND blockinstanceid = :blockinstanceid
                AND timecreated > :timethreshold
                GROUP BY query
                ORDER BY count DESC, query ASC";

        $common_queries = $DB->get_records_sql($sql, $params, 0, $limit);

        foreach ($common_queries as $query) {
            $result['most_common'][] = array(
                'query' => $query->query,
                'count' => $query->count
            );
        }

        // Get query types distribution
        $sql = "SELECT querytype, COUNT(*) as count
                FROM {block_tutoring_machine_analytics}
                WHERE courseid = :courseid AND blockinstanceid = :blockinstanceid
                AND timecreated > :timethreshold AND querytype IS NOT NULL
                GROUP BY querytype
                ORDER BY count DESC";

        $query_types = $DB->get_records_sql($sql, $params);

        foreach ($query_types as $type) {
            $result['by_type'][$type->querytype] = $type->count;
        }

        return $result;
    }

    /**
     * Clean up old analytics data based on retention period
     *
     * @return int Number of records deleted
     */
    public function cleanup_old_data() {
        global $DB;

        $retention_days = $this->get_retention_period();
        $time_threshold = time() - ($retention_days * 24 * 60 * 60);

        $params = array(
            'courseid' => $this->courseid,
            'blockinstanceid' => $this->blockinstanceid,
            'timethreshold' => $time_threshold
        );

        $sql = "DELETE FROM {block_tutoring_machine_analytics}
                WHERE courseid = :courseid AND blockinstanceid = :blockinstanceid
                AND timecreated < :timethreshold";

        return $DB->execute($sql, $params);
    }

    /**
     * Anonymize a query to remove potential PII
     *
     * @param string $query The query to anonymize
     * @return string Anonymized query
     */
    protected function anonymize_query($query) {
        // Basic anonymization - remove common PII patterns

        // Remove email addresses
        $anonymized = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', '[EMAIL]', $query);

        // Remove phone numbers (various formats)
        $anonymized = preg_replace('/\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/', '[PHONE]', $anonymized);
        $anonymized = preg_replace('/\b\+\d{1,3}[-.\s]?\d{1,3}[-.\s]?\d{3,5}[-.\s]?\d{4}\b/', '[PHONE]', $anonymized);

        // Remove names (this is a basic approach - not foolproof)
        // A more sophisticated approach would use named entity recognition
        $anonymized = preg_replace('/\b(Mr\.|Mrs\.|Ms\.|Dr\.)\s+[A-Z][a-z]+\b/', '[NAME]', $anonymized);

        // Remove URLs
        $anonymized = preg_replace('/https?:\/\/\S+/', '[URL]', $anonymized);

        return $anonymized;
    }

    /**
     * Attempt to categorize a query
     *
     * @param string $query The query to categorize
     * @return string|null Query category or null if not categorized
     */
    protected function categorize_query($query) {
        // Simple keyword-based categorization
        $categories = array(
            'content' => array('was ist', 'erkläre', 'erklären', 'bedeutet', 'definition', 'what is', 'explain', 'define', 'meaning'),
            'assignment' => array('aufgabe', 'hausaufgabe', 'übung', 'assignment', 'homework', 'exercise', 'task'),
            'exam' => array('prüfung', 'klausur', 'test', 'exam', 'assessment'),
            'grade' => array('note', 'bewertung', 'punkte', 'grade', 'score', 'points'),
            'technical' => array('fehler', 'problem', 'funktioniert nicht', 'error', 'issue', 'not working', 'bug'),
            'schedule' => array('termin', 'datum', 'wann', 'deadline', 'date', 'when', 'due')
        );

        $query_lower = mb_strtolower($query);

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_strpos($query_lower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }
}