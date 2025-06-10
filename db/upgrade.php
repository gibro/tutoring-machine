<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Upgrade script for block_chatbot.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_block_chatbot_upgrade is the function that upgrades
 * the chatbot block's database when needed.
 *
 * @param int $oldversion New old version number.
 * @return boolean
 */
function xmldb_block_chatbot_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025042400) {
        // Create PDF cache table
        $table = new xmldb_table('block_chatbot_pdf_cache');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecached', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes
        $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, ['contenthash']);

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update version number
        upgrade_block_savepoint(true, 2025042400, 'chatbot');
    }

    if ($oldversion < 2025050503) {
        // Create the PDF cache table if it doesn't exis
        // This ensures installations that skipped earlier versions still have the table
        $table = new xmldb_table('block_chatbot_pdf_cache');

        if (!$dbman->table_exists($table)) {
            // Add fields
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecached', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

            // Add keys
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            // Add indexes
            $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, ['contenthash']);

            // Create the table
            $dbman->create_table($table);
        }

        // Update version number
        upgrade_block_savepoint(true, 2025050503, 'chatbot');
    }

    if ($oldversion < 2025063000) {
        // Create analytics table for chatbot usage data
        $table = new xmldb_table('block_chatbot_analytics');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('blockinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('query', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('querytype', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('tokens', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('model', XMLDB_TYPE_CHAR, '50', null, null, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

        // Add indexes
        $table->add_index('blockcourse', XMLDB_INDEX_NOTUNIQUE, ['blockinstanceid', 'courseid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update version number
        upgrade_block_savepoint(true, 2025063000, 'chatbot');
    }

    if ($oldversion < 2025063016) {
        // Define new capability
        // Capability definitions are automatically handled by Moodle during plugin installation or upgrade
        // when the capability is defined in the db/access.php file

        // Clear caches to ensure the new capability is recognized by the system
        cache_helper::purge_by_event('accesslib_clear_all_caches');

        // Update version number
        upgrade_block_savepoint(true, 2025063016, 'chatbot');
    }
    
    if ($oldversion < 2025063103) {
        // Create Office documents cache table
        $table = new xmldb_table('block_chatbot_office_cache');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecached', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Add indexes
        $table->add_index('contenthash', XMLDB_INDEX_UNIQUE, ['contenthash']);

        // Create the table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update version number
        upgrade_block_savepoint(true, 2025063103, 'chatbot');
    }

    return true;
}