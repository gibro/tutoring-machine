<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Robust loader for Moodle's config.php for the Tutoring Machine block.
 *
 * Standalone entry scripts in this plugin include this file to locate and load
 * Moodle's config.php. When the plugin directory is a symlink (common in local
 * dev setups), PHP resolves __DIR__ to the real path outside Moodle, so the
 * usual __DIR__/../../config.php no longer points at the Moodle root. This
 * loader adds a fallback that derives the Moodle root from the web server's
 * reported script path, which keeps the (non-resolved) symlinked location.
 * config.php sits exactly two directories above the plugin folder in both the
 * classic and the Moodle 5 public/ web-root layout.
 *
 * Usage (from a script in the plugin root):
 *     require_once(__DIR__ . '/configloader.php');
 * Define AJAX_SCRIPT / CLI_SCRIPT / NO_MOODLE_COOKIES etc. before including it,
 * exactly as you would before a direct config.php require.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Candidate locations for Moodle's config.php, most specific first.
$tm_config_candidates = array(__DIR__ . '/../../config.php');
if (!empty($_SERVER['SCRIPT_FILENAME'])) {
    // The web server reports the requested (non-resolved) path, so this survives
    // a symlinked plugin directory where __DIR__ has been resolved away.
    $tm_config_candidates[] = dirname($_SERVER['SCRIPT_FILENAME'], 3) . '/config.php';
}

$tm_config_loaded = false;
foreach ($tm_config_candidates as $tm_config_path) {
    if (is_readable($tm_config_path)) {
        require($tm_config_path);
        $tm_config_loaded = true;
        break;
    }
}
unset($tm_config_candidates, $tm_config_path);

if (empty($tm_config_loaded)) {
    // No Moodle config.php could be located – fail with a clear message.
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Tutoring Machine: Moodle config.php not found.\n");
    } else {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        echo 'Tutoring Machine: Moodle config.php not found.';
    }
    exit(1);
}
unset($tm_config_loaded);
