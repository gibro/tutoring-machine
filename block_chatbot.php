<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Chatbot block main class.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Chatbot block class definition
 */
class block_chatbot extends block_base {

    /**
     * Initialize block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_chatbot');
    }

    /**
     * Signal that this block has global configuration settings
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Allows multiple instances of the block within a course
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Processes instance configuration after edit save
     *
     * @param object $data The submitted form data
     * @param bool $nolongerused No longer used parameter
     * @return bool True if successful
     */
    public function instance_config_save($data, $nolongerused = false) {
        $data = (object)$data; // Make sure it's an objec
        return parent::instance_config_save($data, $nolongerused);
    }

    /**
     * Get block conten
     *
     * @return object|string Block conten
     */
    public function get_content() {
        global $CFG, $COURSE, $USER, $OUTPUT, $DB, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        // Get assistant name from plugin settings
        $config = get_config('block_chatbot');
        $assistant_name = !empty($config->assistantname) ? $config->assistantname : 'Chatbot';

        // Get logo URL
        $logo_url = $this->get_logo_url();

        // Get the main color from block config, default to #007bff if not se
        $main_color = !empty($this->config->main_color) ? $this->config->main_color : '#007bff';

        // Check if analytics are enabled for this block
        $analytics_enabled = !empty($this->config->enable_analytics) && $this->config->enable_analytics;
        $analytics_notice = '';

        if ($analytics_enabled) {
            $analytics_notice = get_string('data_collection_notice', 'block_chatbot');
        }

        // Load HTML template with error handling
        $html = $this->load_html_template(
            $CFG->dirroot . '/blocks/chatbot/templates/body.html',
            $assistant_name,
            $logo_url,
            $main_color,
            $analytics_notice
        );

        $this->content = new stdClass;
        $this->content->text = $html;

        // Kein Footer mehr benötigt, da alle Links in die Einstellungen verschoben wurden
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Get the logo URL
     *
     * @return moodle_url Logo URL
     */
    private function get_logo_url() {
        return new moodle_url('/blocks/chatbot/pix/widget-logo.png');
    }

    /**
     * Load HTML template and replace placeholders
     *
     * @param string $template_path Path to HTML template
     * @param string $assistant_name Assistant name
     * @param moodle_url $logo_url Logo URL
     * @param string $main_color Main color for the chatbot interface
     * @param string $analytics_notice Notice about analytics data collection
     * @return string Final HTML conten
     */
    private function load_html_template($template_path, $assistant_name, $logo_url, $main_color = '#007bff', $analytics_notice = '') {
        global $COURSE;

        // Make sure analytics_notice is set to empty string if not provided
        if (empty($analytics_notice)) {
            $analytics_notice = '';
        }

        // Check if prompt suggestions are enabled and get the suggestions
        $prompt_suggestions_enabled = !empty($this->config->enable_prompt_suggestions) && $this->config->enable_prompt_suggestions;
        $prompt_suggestions = '';
        if ($prompt_suggestions_enabled && !empty($this->config->prompt_suggestions)) {
            $prompt_suggestions = $this->config->prompt_suggestions;
        }

        if (!file_exists($template_path)) {
            return 'Error: body.html not found.';
        }

        $template_content = file_get_contents($template_path);
        if ($template_content === false) {
            return 'Error: Could not read body.html template.';
        }

        // Replace placeholders
        $html = str_replace('%%CHATBOT_NAME%%', s($assistant_name), $template_content);
        $html = str_replace('%%CHATBOT_LOGO%%', $logo_url, $html);
        $html = str_replace('%%BLOCK_ID%%', $this->instance->id, $html);
        $html = str_replace('%%MAIN_COLOR%%', $main_color, $html);
        $html = str_replace('%%COURSE_ID%%', $COURSE->id, $html);
        $html = str_replace('%%ANALYTICS_NOTICE%%', $analytics_notice, $html);
        $html = str_replace('%%ANALYTICS_ENABLED%%', (!empty($analytics_notice) ? 'true' : 'false'), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS_ENABLED%%', ($prompt_suggestions_enabled ? 'true' : 'false'), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS%%', s($prompt_suggestions), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS_BUTTON_TEXT%%', get_string('prompt_suggestions_button_text', 'block_chatbot'), $html);

        return $html;
    }

    // Analytics logging has been moved to the analytics_logger.php class

    /**
     * Defines where the block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view' => true,
            'site' => true,
            'mod' => false,
            'my' => true,
        );
    }

    /**
     * Add custom HTML attributes to the block instance
     *
     * @return array
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();

        // Add the block instance id as a data attribute to access in JS
        $attributes['data-block-id'] = $this->instance->id;

        return $attributes;
    }
}