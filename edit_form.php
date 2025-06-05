<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Form for editing Chatbot block instances.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing Chatbot block instances.
 */
class block_chatbot_edit_form extends block_edit_form {

    /**
     * Adds custom form fields to the block editing form.
     *
     * @param MoodleQuickForm $mform The form being built.
     */
    protected function specific_definition($mform) {
        // Section header title.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Metaprompt field for custom instructions
        $mform->addElement('textarea', 'config_metaprompt',
            get_string('metaprompt', 'block_chatbot'),
            array('rows' => 5, 'cols' => 50));

        $mform->addHelpButton('config_metaprompt', 'metaprompt', 'block_chatbot');

        // KI-Modell section header
        $mform->addElement('header', 'ai_model', get_string('modelsettings', 'block_chatbot'));

        // Get system default model
        $config = get_config('block_chatbot');
        $default_model = isset($config->default_model) ? $config->default_model : 'gpt-4';

        // Model choices with descriptions for learning applications
        $model_choices = [
            '' => get_string('use_system_default', 'block_chatbot') . ' (' . $default_model . ')',

            // OpenAI Models
            'openai:gpt-4' => 'OpenAI GPT-4 (für Tutoring und komplexe Erklärungen - sehr hohe Qualität)',
            'openai:gpt-4-turbo' => 'OpenAI GPT-4 Turbo (für Recherche-Hilfe - schnell und genau)',
            'openai:gpt-4o' => 'OpenAI GPT-4o (für vielseitige Einsatzzwecke - sehr hohe Qualität)',
            'openai:gpt-4o-mini' => 'OpenAI GPT-4o Mini (für Zusammenfassungen - kostengünstig)',
            'openai:gpt-4.1-nano' => 'OpenAI GPT-4.1 Nano (für einfache Fragen - höchste Geschwindigkeit)',
            'openai:gpt-4.1-mini' => 'OpenAI GPT-4.1 Mini (kreatives Lernen - gute Balance)',

            // Google Gemini Models
            'google:gemini-1.5-pro' => 'Google Gemini 1.5 Pro (umfangreiches Wissen - allgemeine Verwendung)',
            'google:gemini-1.5-flash' => 'Google Gemini 1.5 Flash (schnelle Antworten - kostengünstig)',

            // Anthropic Models wurden entfern
        ];

        // Add model selector
        $mform->addElement('select', 'config_ai_model',
            get_string('ai_model', 'block_chatbot'),
            $model_choices);
        $mform->setDefault('config_ai_model', '');
        $mform->addHelpButton('config_ai_model', 'ai_model', 'block_chatbot');

        // Appearance section header
        $mform->addElement('header', 'appearance', get_string('appearance', 'block_chatbot'));

        // Add color picker for main color
        $mform->addElement('text', 'config_main_color',
            get_string('main_color', 'block_chatbot'),
            array('type' => 'color'));
        $mform->setDefault('config_main_color', '#007bff');
        $mform->setType('config_main_color', PARAM_TEXT);
        $mform->addHelpButton('config_main_color', 'main_color', 'block_chatbot');

        // Context sources section header
        $mform->addElement('header', 'contextsources', get_string('contextsources', 'block_chatbot'));

        // Überschrift für Kontextquellen
        $mform->addElement('static', 'activities_label', '',
            '<div style="margin-bottom: 15px;"><strong>Einbezogene Aktivitäten:</strong></div>');

        // Textseiten
        $mform->addElement('advcheckbox', 'config_use_textpages', 'Textseite', '', array(), array(0, 1));
        $mform->setDefault('config_use_textpages', 1);
        $mform->addHelpButton('config_use_textpages', 'use_textpages', 'block_chatbot');

        // Glossare
        $mform->addElement('advcheckbox', 'config_use_glossaries', 'Glossare', '', array(), array(0, 1));
        $mform->setDefault('config_use_glossaries', 1);
        $mform->addHelpButton('config_use_glossaries', 'use_glossaries', 'block_chatbot');

        // H5P-Aktivitäten
        $mform->addElement('advcheckbox', 'config_use_h5p', 'H5P-Aktivitäten', '', array(), array(0, 1));
        $mform->setDefault('config_use_h5p', 1);
        $mform->addHelpButton('config_use_h5p', 'use_h5p', 'block_chatbot');

        // PDF-Dokumente
        $mform->addElement('advcheckbox', 'config_use_pdfs', 'PDF-Dokumente', '', array(), array(0, 1));
        $mform->setDefault('config_use_pdfs', 1);
        $mform->addHelpButton('config_use_pdfs', 'use_pdfs', 'block_chatbot');

        // Foren
        $mform->addElement('advcheckbox', 'config_use_forums', 'Foren', '', array(), array(0, 1));
        $mform->setDefault('config_use_forums', 1);
        $mform->addHelpButton('config_use_forums', 'use_forums', 'block_chatbot');

        // Quizze
        $mform->addElement('advcheckbox', 'config_use_quizzes', 'Quizze', '', array(), array(0, 1));
        $mform->setDefault('config_use_quizzes', 1);
        $mform->addHelpButton('config_use_quizzes', 'use_quizzes', 'block_chatbot');

        // Bücher
        $mform->addElement('advcheckbox', 'config_use_books', 'Bücher', '', array(), array(0, 1));
        $mform->setDefault('config_use_books', 1);
        $mform->addHelpButton('config_use_books', 'use_books', 'block_chatbot');

        // Aufgaben
        $mform->addElement('advcheckbox', 'config_use_assignments', 'Aufgaben', '', array(), array(0, 1));
        $mform->setDefault('config_use_assignments', 1);
        $mform->addHelpButton('config_use_assignments', 'use_assignments', 'block_chatbot');

        // Textfelder
        $mform->addElement('advcheckbox', 'config_use_labels', 'Textfelder', '', array(), array(0, 1));
        $mform->setDefault('config_use_labels', 1);
        $mform->addHelpButton('config_use_labels', 'use_labels', 'block_chatbot');

        // URL-Ressourcen
        $mform->addElement('advcheckbox', 'config_use_urls', 'URL-Ressourcen', '', array(), array(0, 1));
        $mform->setDefault('config_use_urls', 1);
        $mform->addHelpButton('config_use_urls', 'use_urls', 'block_chatbot');

        // Lektionen
        $mform->addElement('advcheckbox', 'config_use_lessons', 'Lektionen', '', array(), array(0, 1));
        $mform->setDefault('config_use_lessons', 1);
        $mform->addHelpButton('config_use_lessons', 'use_lessons', 'block_chatbot');

        // Internetsuche
        $mform->addElement('advcheckbox', 'config_use_internet', 'Internetsuche', '', array(), array(0, 1));
        $mform->setDefault('config_use_internet', 0);
        $mform->addHelpButton('config_use_internet', 'use_internet', 'block_chatbot');

        // Teaching analytics section header
        $mform->addElement('header', 'teachinganalytics', get_string('teachinganalytics', 'block_chatbot'));

        // Checkbox for enabling teaching analytics
        $mform->addElement('advcheckbox', 'config_enable_analytics',
            get_string('enable_analytics', 'block_chatbot'),
            get_string('enable_analytics_desc', 'block_chatbot'),
            array(), array(0, 1));
        $mform->setDefault('config_enable_analytics', 0); // Disabled by defaul
        $mform->addHelpButton('config_enable_analytics', 'enable_analytics', 'block_chatbot');

        // Notice about user privacy
        $mform->addElement('static', 'analytics_notice',
            get_string('analytics_notice_title', 'block_chatbot'),
            get_string('analytics_notice', 'block_chatbot'));

        // Analytics data retention period
        $retention_options = array(
            7 => get_string('retention_1week', 'block_chatbot'),
            30 => get_string('retention_1month', 'block_chatbot'),
            90 => get_string('retention_3months', 'block_chatbot'),
            180 => get_string('retention_6months', 'block_chatbot'),
            365 => get_string('retention_1year', 'block_chatbot')
        );

        $mform->addElement('select', 'config_analytics_retention',
            get_string('analytics_retention', 'block_chatbot'),
            $retention_options);
        $mform->setDefault('config_analytics_retention', 30); // Default to 1 month
        $mform->disabledIf('config_analytics_retention', 'config_enable_analytics', 'neq', 1);

        // Prompt suggestions section header
        $mform->addElement('header', 'prompt_suggestions_section', get_string('prompt_suggestions', 'block_chatbot'));

        // Checkbox for enabling prompt suggestions
        $mform->addElement('advcheckbox', 'config_enable_prompt_suggestions',
            get_string('enable_prompt_suggestions', 'block_chatbot'),
            get_string('enable_prompt_suggestions_desc', 'block_chatbot'),
            array(), array(0, 1));
        $mform->setDefault('config_enable_prompt_suggestions', 0); // Disabled by defaul

        // Textarea for prompt suggestions
        $mform->addElement('textarea', 'config_prompt_suggestions',
            get_string('prompt_suggestions', 'block_chatbot'),
            array('rows' => 8, 'cols' => 50, 'placeholder' => get_string('prompt_suggestions_placeholder', 'block_chatbot')));
        $mform->addHelpButton('config_prompt_suggestions', 'prompt_suggestions', 'block_chatbot');
        $mform->disabledIf('config_prompt_suggestions', 'config_enable_prompt_suggestions', 'neq', 1);
    }

    /**
     * Sets the current data for the form.
     *
     * @param array $defaults The default values for the form.
     */
    function set_data($defaults) {
        if (!is_object($defaults)) {
            $defaults = (object)$defaults;
        }

        parent::set_data($defaults);
    }
}