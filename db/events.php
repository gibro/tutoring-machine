<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Events observers for block_tutoring_machine.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // When a page is updated
    [
        'eventname' => '\mod_page\event\course_module_updated',
        'callback'  => '\block_tutoring_machine\observers::page_updated',
    ],
    // When a glossary is updated
    [
        'eventname' => '\mod_glossary\event\course_module_updated',
        'callback'  => '\block_tutoring_machine\observers::glossary_updated',
    ],
    // When a glossary entry is created
    [
        'eventname' => '\mod_glossary\event\entry_created',
        'callback'  => '\block_tutoring_machine\observers::glossary_entry_updated',
    ],
    // When a glossary entry is updated
    [
        'eventname' => '\mod_glossary\event\entry_updated',
        'callback'  => '\block_tutoring_machine\observers::glossary_entry_updated',
    ],
    // When a glossary entry is deleted
    [
        'eventname' => '\mod_glossary\event\entry_deleted',
        'callback'  => '\block_tutoring_machine\observers::glossary_entry_updated',
    ],
    // When an H5P activity is updated
    [
        'eventname' => '\mod_hvp\event\course_module_updated',
        'callback'  => '\block_tutoring_machine\observers::h5p_updated',
    ],
    // When a file (potentially PDF) is updated
    [
        'eventname' => '\core\event\file_updated',
        'callback'  => '\block_tutoring_machine\observers::file_updated',
    ],
    // When a resource module is updated (which might contain a PDF)
    [
        'eventname' => '\mod_resource\event\course_module_updated',
        'callback'  => '\block_tutoring_machine\observers::resource_updated',
    ],
    // When a course is updated (for course content cache)
    [
        'eventname' => '\core\event\course_updated',
        'callback'  => '\block_tutoring_machine\observers::course_updated',
    ],
];