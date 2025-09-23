<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Ingestion of external link content.
 *
 * @package    block_tutoring_machine
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_tutoring_machine;

defined('MOODLE_INTERNAL') || die();

/**
 * Link ingestion pipeline.
 */
class link_ingest {
    /** @var int Time-to-live for cached link content (seconds). */
    public const DEFAULT_TTL = DAYSECS;

    /**
     * Ensure a link record has fresh content, fetching when required.
     *
     * @param \stdClass $record
     */
    public static function ensure_fresh(\stdClass $record): void {
        $ttl = (int)get_config('block_tutoring_machine', 'link_refresh_ttl');
        if ($ttl <= 0) {
            $ttl = self::DEFAULT_TTL;
        }

        $needsrefresh = empty($record->lastfetch) || ($record->lastfetch + $ttl) < time();
        if ($needsrefresh || empty($record->content)) {
            self::ingest_record($record);
        }
    }

    /**
     * Fetch and store content for a link record.
     *
     * @param \stdClass $record Partial record (must at least contain id, url, blockid, courseid).
     */
    public static function ingest_record(\stdClass $record): void {
        global $DB;

        $url = trim($record->url ?? '');
        if ($url === '' || !link_fetcher::allowed_domain($url)) {
            self::mark_status($record->id, 'blocked', 'Domain not whitelisted');
            return;
        }

        if (link_fetcher::is_pdf($url)) {
            self::mark_status($record->id, 'unsupported', 'PDF links are not yet supported');
            return;
        }

        $html = link_fetcher::fetch_html($url);
        if ($html === null) {
            self::mark_status($record->id, 'error', 'Failed to retrieve content');
            return;
        }

        $parsed = link_fetcher::parse_html_to_markdown($url, $html);
        $body = trim($parsed['body']);
        if ($body === '') {
            self::mark_status($record->id, 'error', 'Empty response body');
            return;
        }

        if (mb_strlen($body, 'UTF-8') > 4000) {
            $body = mb_substr($body, 0, 4000, 'UTF-8') . "\n\n…";
        }

        $markdown = self::build_markdown($parsed['title'], $url, $body);
        $hash = hash('sha256', $markdown);

        $update = (object) [
            'id' => $record->id,
            'title' => $parsed['title'],
            'contenthash' => $hash,
            'content' => $markdown,
            'status' => 'ok',
            'lasterror' => null,
            'lastfetch' => time(),
            'timemodified' => time(),
        ];
        $DB->update_record('block_tutoring_machine_links', $update);
    }

    /**
     * Build markdown snippet stored for a link.
     */
    protected static function build_markdown(string $title, string $url, string $body): string {
        $safeurl = trim($url);
        $header = '### ' . trim($title);
        $source = '*Quelle:* ' . $safeurl;
        return $header . "\n\n" . $source . "\n\n---\n\n" . $body;
    }

    /**
     * Helper to flag status/lasterror quickly.
     */
    protected static function mark_status(int $id, string $status, ?string $message = null): void {
        global $DB;
        $update = new \stdClass();
        $update->id = $id;
        $update->status = $status;
        $update->lasterror = $message;
        $update->lastfetch = time();
        $update->timemodified = time();
        $DB->update_record('block_tutoring_machine_links', $update);
    }
}
