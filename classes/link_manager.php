<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Helper for managing external context links.
 *
 * @package    block_tutoring_machine
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_tutoring_machine;

use context_block;

defined('MOODLE_INTERNAL') || die();

/**
 * Storage helper for context links.
 */
class link_manager {
    /**
     * Sync configured URLs for a block into the persistent table.
     *
     * @param int   $blockid   Block instance id.
     * @param int   $courseid  Course id if available.
     * @param array $rawurls   Raw URL strings.
     */
    public static function sync_links_for_block(int $blockid, int $courseid, array $rawurls): void {
        global $DB;

        $cleanurls = [];
        foreach ($rawurls as $url) {
            $url = trim($url);
            if ($url === '') {
                continue;
            }
            if (!preg_match('/^https?:\/\//i', $url)) {
                // Only allow HTTP/HTTPS schemes.
                continue;
            }
            $url = self::normalise_url($url);
            $cleanurls[$url] = self::hash_url($url);
        }

        $existing = $DB->get_records('block_tutoring_machine_links', ['blockid' => $blockid], '', 'id,urlhash');

        // Delete removed links.
        foreach ($existing as $record) {
            if (!in_array($record->urlhash, $cleanurls, true)) {
                $DB->delete_records('block_tutoring_machine_links', ['id' => $record->id]);
            }
        }

        // Insert new links and align metadata.
        foreach ($cleanurls as $url => $hash) {
            $exists = $DB->record_exists('block_tutoring_machine_links', ['blockid' => $blockid, 'urlhash' => $hash]);
            if (!$exists) {
                $DB->insert_record('block_tutoring_machine_links', (object) [
                    'blockid' => $blockid,
                    'courseid' => $courseid,
                    'url' => $url,
                    'urlhash' => $hash,
                    'status' => 'pending',
                    'lastfetch' => null,
                    'timemodified' => time(),
                ]);
            } else {
                $existingid = self::get_id_for_hash($existing, $hash);
                if ($existingid) {
                    $DB->update_record('block_tutoring_machine_links', (object) [
                        'id' => $existingid,
                        'courseid' => $courseid,
                        'timemodified' => time(),
                    ]);
                }
            }
        }
    }

    /**
     * Helper to find record id for a given hash.
     *
     * @param array $records
     * @param string $hash
     * @return int|null
     */
    protected static function get_id_for_hash(array $records, string $hash): ?int {
        foreach ($records as $record) {
            if ($record->urlhash === $hash) {
                return (int)$record->id;
            }
        }
        return null;
    }

    /**
     * Return list of link records for a block ordered by id.
     *
     * @param int $blockid
     * @return array
     */
    public static function get_links_for_block(int $blockid): array {
        global $DB;
        return $DB->get_records('block_tutoring_machine_links', ['blockid' => $blockid], 'id ASC');
    }

    /**
     * Determine course id for a block context.
     *
     * @param int $blockid
     * @return int
     */
    public static function resolve_courseid(int $blockid): int {
        $context = context_block::instance($blockid);
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            return (int)$coursecontext->instanceid;
        }
        return 0;
    }

    /**
     * Normalise URL string (lowercase scheme/host, trim trailing spaces).
     *
     * @param string $url
     * @return string
     */
    public static function normalise_url(string $url): string {
        $parts = parse_url($url);
        if (!$parts) {
            return trim($url);
        }
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . $query . $fragment;
    }

    /**
     * Hash URL for stable storage.
     *
     * @param string $url
     * @return string
     */
    public static function hash_url(string $url): string {
        return hash('sha256', $url);
    }
}
