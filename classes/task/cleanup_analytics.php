<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Scheduled task for cleaning up old analytics data.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_chatbot\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/chatbot/classes/analytics_manager.php');

/**
 * Task to clean up old analytics data.
 */
class cleanup_analytics extends \core\task\scheduled_task {
    /**
     * Return the task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_analytics', 'block_chatbot');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        mtrace('Starting cleanup of old chatbot analytics data...');

        // Get all chatbot block instances
        $blocks = $DB->get_records('block_instances', ['blockname' => 'chatbot']);
        mtrace('Found ' . count($blocks) . ' chatbot blocks');

        $total_deleted = 0;

        // Process each block
        foreach ($blocks as $block) {
            $courseid = $block->parentcontextid;
            $blockid = $block->id;

            // Create analytics manager
            $analytics_manager = new \block_chatbot_analytics_manager($courseid, $blockid);

            // Skip if analytics not enabled
            if (!$analytics_manager->is_analytics_enabled()) {
                mtrace("Analytics not enabled for block $blockid in course $courseid - skipping");
                continue;
            }

            // Clean up old data
            $deleted = $analytics_manager->cleanup_old_data();
            $total_deleted += $deleted;

            mtrace("Cleaned up $deleted old records for block $blockid in course $courseid");
        }

        mtrace("Cleanup completed. Total records deleted: $total_deleted");
    }
}