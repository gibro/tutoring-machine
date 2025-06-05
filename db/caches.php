<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Cache definitions for block_chatbot.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Rate limiting cache
    'rate_limits' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600, // Time to live - 1 hour
        'staticacceleration' => true,
        'staticaccelerationsize' => 30, // Maximum number of items to store in accelerated cache
    ],

    // Course content cache
    'course_content' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false, // Complex data
        'ttl' => 43200, // Time to live - 12 hours
        'staticacceleration' => true,
        'staticaccelerationsize' => 10, // Store up to 10 courses in memory
    ],

    // Page content cache
    'page_content' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true, // Simple text data
        'ttl' => 86400, // Time to live - 24 hours
        'staticacceleration' => true,
        'staticaccelerationsize' => 50, // Store up to 50 pages in memory
    ],

    // Glossary content cache
    'glossary_content' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false, // Complex data
        'ttl' => 86400, // Time to live - 24 hours
        'staticacceleration' => true,
        'staticaccelerationsize' => 20, // Store up to 20 glossaries in memory
    ],

    // H5P content cache
    'h5p_content' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true, // Simple text data
        'ttl' => 86400, // Time to live - 24 hours
        'staticacceleration' => true,
        'staticaccelerationsize' => 20, // Store up to 20 H5P activities in memory
    ],

    // Miscellaneous content cache (forums, quizzes, books, etc.)
    'misc_content' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true, // Simple text data
        'ttl' => 86400, // Time to live - 24 hours
        'staticacceleration' => true,
        'staticaccelerationsize' => 100, // Store up to 100 items in memory
    ],
];