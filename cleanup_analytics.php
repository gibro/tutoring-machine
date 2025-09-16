<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Scheduled task for cleaning up old analytics data.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/analytics_manager.php');

/**
 * Cleanup old analytics data based on retention periods
 * This script should be run as a cron job
 */

$start_time = microtime(true);
$log_file = __DIR__ . '/logs/analytics_cleanup_' . date('Y-m-d') . '.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    if (!defined('PHPUNIT_TEST')) {
        mtrace($formatted);
    }
}

// Initialize log
file_put_contents($log_file, "=== Analytics Data Cleanup Log ===\n" . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);
log_message("Starting analytics data cleanup");

// Get all block instances
try {
    global $DB;

    // Get all Tutoring Machine block instances
    $blocks = $DB->get_records('block_instances', ['blockname' => 'tutoring_machine']);
    log_message("Found " . count($blocks) . " Tutoring Machine blocks");

    $total_deleted = 0;

    // Process each block
    foreach ($blocks as $block) {
        $courseid = $block->parentcontextid;
        $blockid = $block->id;

        // Create analytics manager
        $analytics_manager = new block_tutoring_machine_analytics_manager($courseid, $blockid);

        // Skip if analytics not enabled
        if (!$analytics_manager->is_analytics_enabled()) {
            log_message("Analytics not enabled for block $blockid in course $courseid - skipping");
            continue;
        }

        // Clean up old data
        $deleted = $analytics_manager->cleanup_old_data();
        $total_deleted += $deleted;

        log_message("Cleaned up $deleted old records for block $blockid in course $courseid");
    }

    log_message("Cleanup completed. Total records deleted: $total_deleted");

} catch (Exception $e) {
    log_message("Error during cleanup: " . $e->getMessage());
    exit(1);
}

$time_taken = round(microtime(true) - $start_time, 2);
log_message("Cleanup finished in $time_taken seconds");
exit(0);