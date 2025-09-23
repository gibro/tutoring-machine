<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Scheduled task to refresh external link context content.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_tutoring_machine\task;

use block_tutoring_machine\link_ingest;
use core\task\scheduled_task;

/**
 * Refresh cached content for external context links.
 */
class refresh_links extends scheduled_task {
    /**
     * {@inheritdoc}
     */
    public function get_name(): string {
        return get_string('task_refresh_links', 'block_tutoring_machine');
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
        global $DB;

        $now = time();
        $ttl = (int)get_config('block_tutoring_machine', 'link_refresh_ttl');
        if ($ttl <= 0) {
            $ttl = DAYSECS;
        }

        // Select links that were never fetched or are older than TTL, but keep volume manageable per run.
        $cutoff = $now - $ttl;
        $links = $DB->get_records_select('block_tutoring_machine_links', 'lastfetch IS NULL OR lastfetch < ?', [$cutoff], 'lastfetch ASC', 'id, blockid, courseid, url, status, lastfetch', 0, 100);

        if (empty($links)) {
            return;
        }

        foreach ($links as $link) {
            try {
                link_ingest::ingest_record($link);
            } catch (\Throwable $e) {
                mtrace('Link refresh failed for id '.$link->id.': '.$e->getMessage());
            }
        }
    }
}
