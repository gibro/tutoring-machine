<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Scheduled tasks for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'block_tutoring_machine\task\cleanup_analytics',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'block_tutoring_machine\task\refresh_links',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '*/4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
