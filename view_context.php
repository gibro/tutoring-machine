<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Context viewer for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include the Moodle configuration file
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php'); // Standard Moodle Installation
} else {
    // Fallback - search for config.php in parent directories
    $directory = __DIR__;
    $found = false;
    for ($i = 0; $i < 5; $i++) { // Try up to 5 levels up
        $directory = dirname($directory);
        if (file_exists($directory . '/config.php')) {
            require_once($directory . '/config.php');
            $found = true;
            break;
        }
    }
    if (!$found) {
        die('Could not find Moodle config.php file');
    }
}

require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__ . '/classes/content_extractor.php');

// Security parameters
$courseid = required_param('courseid', PARAM_INT);
$blockid = optional_param('blockid', 0, PARAM_INT);

// Check course exists
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Check permissions
require_login($course);
// Check for basic course participation capability instead of plugin-specific capability
// This allows the feature to work even before installing the plugin update with the new capability
if (!has_capability('block/tutoring_machine:view', $context) &&
    !has_capability('moodle/course:view', $context)) {
    require_capability('block/tutoring_machine:view', $context); // Will trigger the appropriate error
}

// Get block configuration if blockid is provided
$block_config = null;
if ($blockid) {
    $block_record = $DB->get_record('block_instances', array('id' => $blockid), '*');
    if ($block_record && $block_record->blockname == 'tutoring_machine') {
        $block_config = unserialize(base64_decode($block_record->configdata));
    }
}

// Page setup
$PAGE->set_url('/blocks/tutoring_machine/view_context.php', array('courseid' => $courseid, 'blockid' => $blockid));
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'block_tutoring_machine') . ': ' . $course->fullname);
$PAGE->set_title(get_string('pluginname', 'block_tutoring_machine') . ': ' . get_string('contextsources', 'block_tutoring_machine'));
$PAGE->navbar->add(get_string('pluginname', 'block_tutoring_machine'));
$PAGE->navbar->add(get_string('contextsources', 'block_tutoring_machine'));

// Get the context from the content extractor
$extractor = new block_tutoring_machine_content_extractor($courseid, $block_config);
$context_content = $extractor->get_context();

// Format the context for display
$formatted_context = nl2br(htmlspecialchars($context_content));

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'block_tutoring_machine') . ': ' . get_string('contextsources', 'block_tutoring_machine'));

// Display an info box explaining what this page shows
echo html_writer::div(
    get_string('contextsources', 'block_tutoring_machine') . ' ' .
    get_string('course_content_context_desc', 'block_tutoring_machine'),
    'alert alert-info'
);

// Add a notice about how this is displayed to the AI
echo html_writer::div(
    'Diese Seite zeigt den exakten Kontext, der dem KI-Modell bei jeder Anfrage zusammen mit der Benutzerfrage bereitgestellt wird.',
    'alert alert-warning'
);

// Add section for context controls
echo html_writer::start_div('card mb-4');
echo html_writer::div('Kontexteinstellungen', 'card-header');
echo html_writer::start_div('card-body');

// Show which context sources are enabled
echo html_writer::tag('h5', 'Aktivierte Kontextquellen:');
echo html_writer::start_tag('ul', array('class' => 'list-group mb-3'));

$sources = array(
    'use_textpages' => get_string('use_textpages', 'block_tutoring_machine'),
    'use_glossaries' => get_string('use_glossaries', 'block_tutoring_machine'),
    'use_h5p' => get_string('use_h5p', 'block_tutoring_machine'),
    'use_pdfs' => get_string('use_pdfs', 'block_tutoring_machine'),
    'use_forums' => get_string('use_forums', 'block_tutoring_machine'),
    'use_quizzes' => get_string('use_quizzes', 'block_tutoring_machine'),
    'use_books' => get_string('use_books', 'block_tutoring_machine'),
    'use_assignments' => get_string('use_assignments', 'block_tutoring_machine'),
    'use_labels' => get_string('use_labels', 'block_tutoring_machine'),
    'use_urls' => get_string('use_urls', 'block_tutoring_machine'),
    'use_lessons' => get_string('use_lessons', 'block_tutoring_machine'),
    'use_internet' => get_string('use_internet', 'block_tutoring_machine')
);

foreach ($sources as $key => $label) {
    $enabled = !$block_config || (isset($block_config->{$key}) && $block_config->{$key});
    $status_class = $enabled ? 'list-group-item-success' : 'list-group-item-light';
    $status_icon = $enabled ? '' : '';

    echo html_writer::tag('li',
        $status_icon . ' ' . $label,
        array('class' => "list-group-item $status_class")
    );
}

echo html_writer::end_tag('ul');
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Show the full context in a card with scrollable conten
echo html_writer::start_div('card');
echo html_writer::div('Vollst�ndiger Kontext', 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::div($formatted_context, 'pre-scrollable', array('style' => 'max-height: 500px; white-space: pre-wrap;'));
echo html_writer::end_div(); // card-body

// Add info about context size
$context_size = strlen($context_content);
$token_estimate = ceil($context_size / 4); // Rough estimate of tokens (4 chars per token)

echo html_writer::start_div('card-footer text-muted');
echo "Kontextgr��e: " . number_format($context_size, 0, ',', '.') . " Zeichen, ca. " .
     number_format($token_estimate, 0, ',', '.') . " Token";
echo html_writer::end_div(); // card-footer
echo html_writer::end_div(); // card

echo $OUTPUT->footer();