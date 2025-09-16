<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Analytics logger for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/analytics_manager.php');

/**
 * Class to log analytics data for the chatbo
 */
class block_tutoring_machine_analytics_logger {
    /**
     * Log a user query for analytics
     *
     * This method should be called from the AJAX endpoint that handles chat messages.
     *
     * @param string $query The user query
     * @param int $courseid The course ID
     * @param int $blockinstanceid The block instance ID
     * @param string $model The AI model used
     * @param int $tokens The number of tokens used
     * @return bool Success
     */
    public static function log_query($query, $courseid, $blockinstanceid, $model = '', $tokens = 0) {
        global $DB;

        // Get block instance
        $instance = $DB->get_record('block_instances', array('id' => $blockinstanceid), '*');
        if (!$instance) {
            return false;
        }

        // Get block config
        $config = unserialize(base64_decode($instance->configdata));
        if (empty($config->enable_analytics)) {
            return false;
        }

        // Create analytics manager
        $analytics_manager = new block_tutoring_machine_analytics_manager($courseid, $blockinstanceid, $config);

        // Log the query
        return $analytics_manager->log_query($query, $model, $tokens);
    }
}