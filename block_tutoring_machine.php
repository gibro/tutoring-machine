<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Tutoring Machine block main class.
 *
 * @package    block_tutoring_machine
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_tutoring_machine\link_manager;

/**
 * Tutoring Machine block class definition
 */
class block_tutoring_machine extends block_base {

    /**
     * Initialize block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_tutoring_machine');
    }
    
    /**
     * Default title
     */
    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->config->title;
        }
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

        if (isset($data->assistantname)) {
            $data->assistantname = trim($data->assistantname);

            $globalname = get_config('block_tutoring_machine', 'assistantname');
            if ($globalname === false || $globalname === null || $globalname === '') {
                $globalname = get_string('pluginname', 'block_tutoring_machine');
            }

            if ($data->assistantname === '' || $data->assistantname === $globalname) {
                unset($data->assistantname);
            }
        }

        $welcomefields = array(
            'welcome_message' => array(
                'configkey' => 'default_welcome_message',
                'fallbackstring' => 'default_welcome_message_value',
            ),
            'welcome_message_analytics' => array(
                'configkey' => 'default_welcome_message_analytics',
                'fallbackstring' => 'default_welcome_message_analytics_value',
            ),
        );

        foreach ($welcomefields as $field => $metadata) {
            if (!isset($data->{$field})) {
                continue;
            }

            $value = trim($data->{$field});
            if ($value === '') {
                unset($data->{$field});
                continue;
            }

            $globalvalue = get_config('block_tutoring_machine', $metadata['configkey']);
            if ($globalvalue === false || $globalvalue === null || trim($globalvalue) === '') {
                $globalvalue = get_string($metadata['fallbackstring'], 'block_tutoring_machine');
            } else {
                $globalvalue = trim($globalvalue);
            }

            if ($value === $globalvalue) {
                unset($data->{$field});
            } else {
                $data->{$field} = $value;
            }
        }

        if (!empty($this->instance->id)) {
            $courseid = link_manager::resolve_courseid($this->instance->id);
            if (!empty($data->context_links_enable)) {
                $urls = [];
                if (!empty($data->context_links)) {
                    $urls = preg_split('/\R+/', $data->context_links);
                }
                link_manager::sync_links_for_block($this->instance->id, $courseid, $urls);
            } else {
                link_manager::sync_links_for_block($this->instance->id, $courseid, []);
            }
        }

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

        // Get assistant name from global plugin settings
        $config = get_config('block_tutoring_machine');
        $global_assistant_name = !empty($config->assistantname) ? $config->assistantname : get_string('pluginname', 'block_tutoring_machine');

        $contextoptions = array();
        if (!empty($this->instance) && !empty($this->instance->id)) {
            $contextoptions['context'] = context_block::instance($this->instance->id);
        }

        $global_assistant_name = format_string($global_assistant_name, true, $contextoptions);

        $assistant_name = $global_assistant_name;

        $instance_assistant = '';
        if (!empty($this->config) && !empty($this->config->assistantname)) {
            $instance_assistant = format_string($this->config->assistantname, true, $contextoptions);
        }
        if ($instance_assistant !== '') {
            $assistant_name = $instance_assistant;
        }

        // Determine the display title for this block instance (matches block header)
        $display_title = format_string($this->title, true, $contextoptions);
        if ($display_title === '') {
            $display_title = $assistant_name;
        }

        // Get logo URL
        $logo_url = $this->get_logo_url();

        // Get the main color from block config, default to #007bff if not se
        $main_color = !empty($this->config->main_color) ? $this->config->main_color : '#007bff';

        // Check if analytics are enabled for this block
        $analytics_enabled = !empty($this->config->enable_analytics) && $this->config->enable_analytics;
        $analytics_notice = '';

        if ($analytics_enabled) {
            $analytics_notice = get_string('data_collection_notice', 'block_tutoring_machine');
        }

        // Determine welcome messages with configurable overrides
        $default_welcome = isset($config->default_welcome_message) && trim($config->default_welcome_message) !== ''
            ? $config->default_welcome_message
            : get_string('default_welcome_message_value', 'block_tutoring_machine');
        $default_welcome = trim($default_welcome);
        if ($default_welcome === '') {
            $default_welcome = get_string('default_welcome_message_value', 'block_tutoring_machine');
        }

        $default_welcome_analytics = isset($config->default_welcome_message_analytics) &&
            trim($config->default_welcome_message_analytics) !== ''
            ? $config->default_welcome_message_analytics
            : '';
        $default_welcome_analytics = trim($default_welcome_analytics);

        $welcome_message = $default_welcome;
        if (!empty($this->config) && !empty($this->config->welcome_message)) {
            $welcome_message = $this->config->welcome_message;
        }
        $welcome_message = trim($welcome_message);

        $welcome_message_analytics = $default_welcome_analytics !== '' ? $default_welcome_analytics : $default_welcome;
        if (!empty($this->config) && !empty($this->config->welcome_message_analytics)) {
            $welcome_message_analytics = $this->config->welcome_message_analytics;
        }
        $welcome_message_analytics = trim($welcome_message_analytics);
        if ($welcome_message_analytics === '') {
            $welcome_message_analytics = $welcome_message;
        }

        // Load HTML template with error handling
        $html = $this->load_html_template(
            $CFG->dirroot . '/blocks/tutoring_machine/templates/body.html',
            $assistant_name,
            $logo_url,
            $main_color,
            $analytics_notice,
            $global_assistant_name,
            $display_title,
            $welcome_message,
            $welcome_message_analytics
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
        return new moodle_url('/blocks/tutoring_machine/pix/widget-logo.png');
    }

    /**
     * Load HTML template and replace placeholders
     *
     * @param string $template_path Path to HTML template
     * @param string $assistant_name Assistant name
     * @param moodle_url $logo_url Logo URL
     * @param string $main_color Main color for the Tutoring Machine interface
     * @param string $analytics_notice Notice about analytics data collection
     * @param string $welcome_message Default welcome message for the chat
     * @param string $welcome_message_analytics Welcome message when analytics are enabled
     * @return string Final HTML conten
     */
    private function load_html_template(
        $template_path,
        $assistant_name,
        $logo_url,
        $main_color = '#007bff',
        $analytics_notice = '',
        $global_assistant_name = '',
        $display_title = '',
        $welcome_message = '',
        $welcome_message_analytics = ''
    ) {
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
        $global_assistant_name = $global_assistant_name !== '' ? $global_assistant_name : $assistant_name;
        $display_title = $display_title !== '' ? $display_title : $assistant_name;

        $html = str_replace('%%CHATBOT_NAME%%', s($global_assistant_name), $template_content);
        $html = str_replace('%%BLOCK_ASSISTANT_NAME%%', s($assistant_name), $html);
        $html = str_replace('%%CHATBOT_TITLE%%', s($display_title), $html);
        $html = str_replace('%%CHATBOT_LOGO%%', $logo_url, $html);
        $html = str_replace('%%BLOCK_ID%%', $this->instance->id, $html);
        $html = str_replace('%%MAIN_COLOR%%', $main_color, $html);
        $html = str_replace('%%COURSE_ID%%', $COURSE->id, $html);
        $html = str_replace('%%ANALYTICS_NOTICE%%', $analytics_notice, $html);
        $html = str_replace('%%ANALYTICS_ENABLED%%', (!empty($analytics_notice) ? 'true' : 'false'), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS_ENABLED%%', ($prompt_suggestions_enabled ? 'true' : 'false'), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS%%', s($prompt_suggestions), $html);
        $html = str_replace('%%PROMPT_SUGGESTIONS_BUTTON_TEXT%%', get_string('prompt_suggestions_button_text', 'block_tutoring_machine'), $html);
        $html = str_replace('%%WELCOME_MESSAGE%%', s($welcome_message), $html);
        $html = str_replace('%%WELCOME_MESSAGE_ANALYTICS%%', s($welcome_message_analytics), $html);

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
