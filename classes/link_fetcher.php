<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Fetch and parse external links for the Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_tutoring_machine;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/filelib.php');

/**
 * Helper methods for fetching remote URLs safely.
 */
class link_fetcher {
    /**
     * Determine whether the URL belongs to an allowed domain.
     *
     * @param string $url
     * @return bool
     */
    public static function allowed_domain(string $url): bool {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        if ($host === '') {
            return false;
        }

        $whitelist = get_config('block_tutoring_machine', 'linkdomain_whitelist');
        if (empty($whitelist)) {
            // No whitelist configured, disallow by default to prevent surprises.
            return false;
        }

        $allowedhosts = preg_split('/\R+/', $whitelist, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($allowedhosts as $allowed) {
            $allowed = trim(strtolower($allowed));
            if ($allowed === '') {
                continue;
            }
            if (self::host_matches($host, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether host equals or is a subdomain of allowed host.
     */
    protected static function host_matches(string $host, string $allowed): bool {
        if ($host === $allowed) {
            return true;
        }
        $suffix = '.' . $allowed;
        return substr_compare($host, $suffix, -strlen($suffix)) === 0;
    }

    /**
     * Determine whether a URL points to a PDF resource.
     */
    public static function is_pdf(string $url): bool {
        return (bool)preg_match('/\.pdf($|\?)/i', $url);
    }

    /**
     * Fetch raw HTML from a URL.
     *
     * @param string $url
     * @return string|null
     */
    public static function fetch_html(string $url): ?string {
        if (!self::passes_robots($url)) {
            return null;
        }

        $curl = new \curl();
        $curl->setHeader([
            'User-Agent: ' . (get_config('block_tutoring_machine', 'link_useragent') ?? 'MoodleTutoringMachineBot/1.0'),
            'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
        ]);
        $curl->setopt([
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_TIMEOUT' => 20,
            'CURLOPT_MAXREDIRS' => 5,
        ]);

        $html = $curl->get($url);
        $info = $curl->get_info();
        $code = $info['http_code'] ?? 0;

        if ($code >= 200 && $code < 300) {
            return $html ?: '';
        }

        return null;
    }

    /**
     * Extract readable text and metadata from HTML.
     *
     * @param string $url
     * @param string $html
     * @return array{title:string,body:string}
     */
    public static function parse_html_to_markdown(string $url, string $html): array {
        $title = self::extract_title($html) ?? $url;
        $body = self::extract_main_text($html);
        return [
            'title' => $title,
            'body' => $body,
        ];
    }

    /**
     * Check robots.txt for the URL if enabled.
     */
    protected static function passes_robots(string $url): bool {
        if (!get_config('block_tutoring_machine', 'respect_robots')) {
            return true;
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return true;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = strtolower($parts['host']);
        $robotsurl = $scheme . '://' . $host . '/robots.txt';

        $curl = new \curl();
        $robots = $curl->get($robotsurl);
        $code = $curl->get_info()['http_code'] ?? 0;
        if ($code >= 400 || $robots === false) {
            return true; // No robots, allow.
        }

        return self::is_path_allowed($robots, $parts['path'] ?? '/');
    }

    /**
     * Minimal robots.txt parser for User-agent: * rules.
     */
    protected static function is_path_allowed(string $robots, string $path): bool {
        $useragent = get_config('block_tutoring_machine', 'link_useragent') ?? 'MoodleTutoringMachineBot/1.0';
        $ua = strtolower($useragent);
        $lines = preg_split('/\R+/', $robots);
        $applies = false;
        $allowed = true;
        $path = $path ?: '/';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (stripos($line, 'User-agent:') === 0) {
                $value = strtolower(trim(substr($line, strlen('User-agent:'))));
                $applies = ($value === '*' || strpos($ua, $value) !== false);
                continue;
            }
            if (!$applies) {
                continue;
            }
            if (stripos($line, 'Disallow:') === 0) {
                $rule = trim(substr($line, strlen('Disallow:')));
                if ($rule === '') {
                    $allowed = true;
                    continue;
                }
                if (strpos($path, $rule) === 0) {
                    $allowed = false;
                }
                continue;
            }
            if (stripos($line, 'Allow:') === 0) {
                $rule = trim(substr($line, strlen('Allow:')));
                if ($rule !== '' && strpos($path, $rule) === 0) {
                    $allowed = true;
                }
            }
        }

        return $allowed;
    }

    /**
     * Extract title from HTML.
     */
    protected static function extract_title(string $html): ?string {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        return null;
    }

    /**
     * Extract readable text from HTML. Uses Readability if available, otherwise falls back to strip_tags.
     */
    protected static function extract_main_text(string $html): string {
        if (class_exists('Readability\\Readability') && class_exists('Readability\\Configuration')) {
            try {
                $config = new \Readability\Configuration();
                $readability = new \Readability\Readability($config);
                if ($readability->parse($html)) {
                    $content = $readability->getContent();
                    if (is_string($content) && $content !== '') {
                        $text = html_to_text($content, 0, false);
                        return self::trim_text($text);
                    }
                }
            } catch (\Throwable $ignored) {
                // fall back to default handling
            }
        }

        // Basic fallback: remove scripts/styles and strip tags.
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $text = html_to_text($html, 0, false);
        return self::trim_text($text);
    }

    /**
     * Normalise whitespace and truncate overly long text.
     */
    protected static function trim_text(string $text): string {
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Limit to roughly 4000 characters (~1000 tokens) to keep prompts small.
        if (mb_strlen($text, 'UTF-8') > 4000) {
            $text = mb_substr($text, 0, 4000, 'UTF-8') . "\n\n…";
        }

        return $text;
    }
}
