<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Analytics dashboard for Chatbot block.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/chatbot/classes/analytics_manager.php');

// Parameters
$id = required_param('id', PARAM_INT); // Block instance ID
$courseid = required_param('course', PARAM_INT); // Course ID
$days = optional_param('days', 30, PARAM_INT); // Days to look back

// Set up page
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('block/chatbot:viewanalytics', $context);

$PAGE->set_url(new moodle_url('/blocks/chatbot/analytics.php', array('id' => $id, 'course' => $courseid, 'days' => $days)));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('analytics_dashboard', 'block_chatbot'));
$PAGE->set_heading(get_string('analytics_dashboard', 'block_chatbot'));
$PAGE->navbar->add(get_string('analytics_dashboard', 'block_chatbot'));

// Create analytics manager
$analytics_manager = new block_chatbot_analytics_manager($courseid, $id);

// Check if analytics are enabled
if (!$analytics_manager->is_analytics_enabled()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('analytics_not_enabled', 'block_chatbot'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

// Get analytics data
$analytics_data = $analytics_manager->get_analytics_data($days);

// Prepare page outpu
echo $OUTPUT->header();

// Display page title and description
echo html_writer::tag('h2', get_string('analytics_dashboard', 'block_chatbot'));
echo html_writer::tag('p', get_string('analytics_info', 'block_chatbot'));
echo html_writer::tag('p', get_string('data_anonymized_notice', 'block_chatbot'), array('class' => 'alert alert-info'));

// Days selector
$days_options = array(
    7 => '7 ' . get_string('days'),
    14 => '14 ' . get_string('days'),
    30 => '30 ' . get_string('days'),
    60 => '60 ' . get_string('days'),
    90 => '90 ' . get_string('days')
);

$days_selector = new single_select(
    new moodle_url('/blocks/chatbot/analytics.php', array('id' => $id, 'course' => $courseid)),
    'days',
    $days_options,
    $days,
    null,
    'daysform'
);
$days_selector->set_label(get_string('analytics_timeperiod', 'block_chatbot'));

echo $OUTPUT->render($days_selector);

// Total queries
echo html_writer::tag('h3', get_string('queries_last_days', 'block_chatbot', $days));
echo html_writer::tag('p',
    get_string('total_queries', 'block_chatbot') . ': ' .
    '<strong>' . $analytics_data['total_queries'] . '</strong>'
);

// Display data if available
if ($analytics_data['total_queries'] > 0) {
    // Most common queries
    echo html_writer::tag('h3', get_string('most_common_questions', 'block_chatbot'));

    $table = new html_table();
    $table->head = array(
        get_string('query', 'block_chatbot'),
        get_string('query_count', 'block_chatbot')
    );
    $table->attributes['class'] = 'table table-striped table-hover';

    foreach ($analytics_data['most_common'] as $query_data) {
        $table->data[] = array(
            $query_data['query'],
            $query_data['count']
        );
    }

    echo html_writer::table($table);

    // Query types chart if there are categorized queries
    if (!empty($analytics_data['by_type'])) {
        echo html_writer::tag('h3', get_string('query_types', 'block_chatbot'));

        $chart = new \core\chart_pie();
        $chart->set_title(get_string('query_types', 'block_chatbot'));

        $labels = array();
        $values = array();

        foreach ($analytics_data['by_type'] as $type => $count) {
            $labels[] = get_string('querytype_' . $type, 'block_chatbot', $type);
            $values[] = $count;
        }

        $series = new \core\chart_series(get_string('queries', 'block_chatbot'), $values);
        $chart->add_series($series);
        $chart->set_labels($labels);

        echo $OUTPUT->render($chart);
    }
} else {
    echo $OUTPUT->notification(get_string('no_analytics_data', 'block_chatbot'), 'info');
}

// Schließen-Button
echo html_writer::start_div('text-center mt-3');
echo html_writer::tag('button', 'Fenster schließen', array(
    'class' => 'btn btn-primary',
    'onclick' => 'window.close();'
));
echo html_writer::end_div();

echo $OUTPUT->footer();