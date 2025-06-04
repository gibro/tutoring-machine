<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Library functions for the Chatbot block.
 *
 * @package    block_chatbot
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
     * Decode the PDF file content
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
     * @return string Extracted text
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
            
            // Also try to extract hex encoded text
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
        
        // Clean up the text
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
function block_chatbot_can_access_block($blockid) {
    global $DB, $USER, $COURSE;
    
    // Get block instance
    $blockinstance = $DB->get_record('block_instances', array('id' => $blockid), '*', MUST_EXIST);
    if (!$blockinstance) {
        return false;
    }
    
    // Check if block is in a course context
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