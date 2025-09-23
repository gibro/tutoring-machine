<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Event observers for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_tutoring_machine;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/cache_manager.php');

/**
 * Event observers for Tutoring Machine block
 *
 * These observers automatically invalidate cached content when relevant content is updated
 */
class observers {
    /**
     * Callback for page_updated even
     *
     * @param \mod_page\event\course_module_updated $event The even
     * @return bool Success or failure
     */
    public static function page_updated($event) {
        global $DB;

        // Get the page instance ID
        $cmid = $event->contextinstanceid;
        $cm = get_coursemodule_from_id('page', $cmid, 0, false, MUST_EXIST);
        $pageid = $cm->instance;

        // Invalidate the page cache
        \block_tutoring_machine_cache_manager::invalidate_page_cache($pageid);

        // Also invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($cm->course);

        return true;
    }

    /**
     * Callback for glossary_updated even
     *
     * @param \mod_glossary\event\course_module_updated $event The even
     * @return bool Success or failure
     */
    public static function glossary_updated($event) {
        global $DB;

        // Get the glossary instance ID
        $cmid = $event->contextinstanceid;
        $cm = get_coursemodule_from_id('glossary', $cmid, 0, false, MUST_EXIST);
        $glossaryid = $cm->instance;

        // Invalidate the glossary cache
        \block_tutoring_machine_cache_manager::invalidate_glossary_cache($glossaryid);

        // Also invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($cm->course);

        return true;
    }

    /**
     * Callback for glossary entry events (created, updated, deleted)
     *
     * @param \mod_glossary\event\entry_created|\mod_glossary\event\entry_updated|\mod_glossary\event\entry_deleted $event The even
     * @return bool Success or failure
     */
    public static function glossary_entry_updated($event) {
        global $DB;

        // Get the glossary ID
        $glossaryid = $event->other['glossaryid'];

        // Get the course ID
        $glossary = $DB->get_record('glossary', ['id' => $glossaryid], '*', MUST_EXIST);

        // Invalidate the glossary cache
        \block_tutoring_machine_cache_manager::invalidate_glossary_cache($glossaryid);

        // Also invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($glossary->course);

        return true;
    }

    /**
     * Callback for h5p_updated even
     *
     * @param \mod_hvp\event\course_module_updated $event The even
     * @return bool Success or failure
     */
    public static function h5p_updated($event) {
        global $DB;

        // Get the H5P instance ID
        $cmid = $event->contextinstanceid;
        $cm = get_coursemodule_from_id('hvp', $cmid, 0, false, MUST_EXIST);
        $h5pid = $cm->instance;

        // Invalidate the H5P cache
        \block_tutoring_machine_cache_manager::invalidate_h5p_cache($h5pid);

        // Also invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($cm->course);

        return true;
    }

    /**
     * Callback for file_updated even
     *
     * @param \core\event\file_updated $event The even
     * @return bool Success or failure
     */
    public static function file_updated($event) {
        global $DB;

        // Get the file details
        $eventdata = $event->get_data();

        // Only process PDF files
        if ($eventdata['other']['mimetype'] === 'application/pdf') {
            // Invalidate the PDF cache
            \block_tutoring_machine_cache_manager::invalidate_pdf_cache($eventdata['other']['contenthash']);

            // Try to determine the course ID
            if (isset($eventdata['courseid']) && $eventdata['courseid'] > 0) {
                // Invalidate the course content cache
                \block_tutoring_machine_cache_manager::invalidate_course_cache($eventdata['courseid']);
            }
        }

        return true;
    }

    /**
     * Callback for resource_updated even
     *
     * @param \mod_resource\event\course_module_updated $event The even
     * @return bool Success or failure
     */
    public static function resource_updated($event) {
        global $DB, $CFG;

        // Get the resource instance ID
        $cmid = $event->contextinstanceid;
        $cm = get_coursemodule_from_id('resource', $cmid, 0, false, MUST_EXIST);
        $resourceid = $cm->instance;

        // Get the resource file
        $context = \context_module::instance($cmid);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        foreach ($files as $file) {
            // If it's a PDF, invalidate its cache
            if ($file->get_mimetype() === 'application/pdf') {
                \block_tutoring_machine_cache_manager::invalidate_pdf_cache($file->get_contenthash());
            }
        }

        // Also invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($cm->course);

        return true;
    }

    /**
     * Callback for course_updated even
     *
     * @param \core\event\course_updated $event The even
     * @return bool Success or failure
     */
    public static function course_updated($event) {
        $courseid = $event->objectid;

        // Invalidate the course content cache
        \block_tutoring_machine_cache_manager::invalidate_course_cache($courseid);

        return true;
    }
}