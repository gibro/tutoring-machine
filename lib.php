<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Library functions for the Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Basic PDF to text conversion class
 * This class provides a fallback method to extract text from PDFs when
 * other methods like pdftotext aren't available.
 */
class pdf2text {
    protected $filename;
    private $data;

    /**
     * Set filename to extract text from
     *
     * @param string $filename Path to PDF file
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * Decode the PDF file conten
     *
     * @return boolean Success or failure
     */
    public function decodePDF() {
        if (!file_exists($this->filename)) {
            return false;
        }

        $this->data = file_get_contents($this->filename);
        if (!$this->data) {
            return false;
        }

        return true;
    }

    /**
     * Extract text content from PDF
     * This is a simple implementation that extracts text objects
     * Note: This won't work on all PDFs, but serves as a basic fallback
     *
     * @return string Extracted tex
     */
    public function output() {
        if (empty($this->data)) {
            return '';
        }

        $text = '';

        // Extract text between BT and ET markers (Basic Text objects)
        preg_match_all('/(BT.*?ET)/s', $this->data, $textObjects);

        foreach ($textObjects[1] as $textObject) {
            // Extract text enclosed in parentheses or between < and >
            preg_match_all('/\(([^\)]*)\)/', $textObject, $matches);
            foreach ($matches[1] as $match) {
                $text .= $match . " ";
            }

            // Also try to extract hex encoded tex
            preg_match_all('/<([^>]*)>/', $textObject, $hexMatches);
            foreach ($hexMatches[1] as $match) {
                // Convert hex to ASCII
                $hex = str_replace(' ', '', $match);
                for ($i = 0; $i < strlen($hex); $i += 2) {
                    if (isset($hex[$i]) && isset($hex[$i+1])) {
                        $text .= chr(hexdec($hex[$i].$hex[$i+1]));
                    }
                }
                $text .= " ";
            }
        }

        // Clean up the tex
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}

/**
 * Function to determine if current user can access specified block instance
 *
 * @param int $blockid The block instance ID
 * @return bool True if user can access, false otherwise
 */
function block_tutoring_machine_can_access_block($blockid) {
    global $DB, $USER, $COURSE;

    // Get block instance
    $blockinstance = $DB->get_record('block_instances', array('id' => $blockid), '*', MUST_EXIST);
    if (!$blockinstance) {
        return false;
    }

    // Check if block is in a course contex
    $context = context::instance_by_id($blockinstance->parentcontextid);
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return false;
    }

    // Check if user is enrolled in the course
    $courseid = $context->instanceid;
    $coursecontext = context_course::instance($courseid);

    return is_enrolled($coursecontext, $USER->id);
}

// Keine pluginfile-Funktion mehr erforderlich,
// da wir direkt das feste Logo aus dem pix-Verzeichnis verwenden

/**
 * Add nodes to navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course objec
 * @param context $context The course contex
 */
function block_tutoring_machine_extend_navigation_course($navigation, $course, $context) {
    // Die Funktion ist jetzt leer, da wir keinen Link in der Kursnavigation hinzufügen möchten
    // Der Kontext-Viewer ist nur über die Blockeinstellungen erreichbar
}

/**
 * Add nodes to the settings navigation.
 *
 * @param settings_navigation $navigation The settings navigation objec
 * @param context $context The context of the course
 */
function block_tutoring_machine_extend_settings_navigation($navigation, $context) {
    global $CFG, $PAGE;

    // Only add settings if in course contex
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return;
    }

    // Check if user has capability to view settings
    // Check for either the Tutoring Machine view capability or basic course view capability
    if (has_capability('block/tutoring_machine:view', $context) || has_capability('moodle/course:view', $context)) {
        // Find the course administration node
        if ($coursenode = $navigation->get('courseadmin')) {
            // Add link to the context viewer
            $url = new moodle_url($CFG->wwwroot . '/blocks/tutoring_machine/view_context.php',
                array('courseid' => $PAGE->course->id));

            $coursenode->add(
                get_string('contextsources', 'block_tutoring_machine'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'tutoring_machine_context',
                new pix_icon('i/info', '')
            );
        }
    }
}