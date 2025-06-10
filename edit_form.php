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

        // Kontext-Einstellungen
        $mform->addElement('header', 'context_settings', 'Kontext');
        
        // Kontextquellen anzeigen - Link
        global $COURSE;
        $url = new moodle_url('/blocks/chatbot/simple_context.php', 
            array('courseid' => $COURSE->id, 'blockid' => $this->block->instance->id));
        $context_link = html_writer::link($url, get_string('contextsources', 'block_chatbot'), 
            array('class' => 'btn btn-secondary mb-3', 'target' => '_blank'));
        $mform->addElement('static', 'context_sources_link', 'Kontextquellen anzeigen', $context_link);
        
        // Internetsuche
        $mform->addElement('advcheckbox', 'config_use_internet', 'Internetsuche', 
            'Darf der Chatbot bei Fragen, die nicht durch den Kurskontext beantwortet werden können, auf Internetwissen zurückgreifen?', 
            array(), array(0, 1));
        $mform->setDefault('config_use_internet', 0);
        $mform->addHelpButton('config_use_internet', 'use_internet', 'block_chatbot');
        
        // Erklärung zur Aktivitätsauswahl
        $mform->addElement('static', 'specific_activities_explanation', '',
            '<div class="alert alert-info">Wählen Sie hier aus, welche konkreten Aktivitäten und Materialien ' .
            'im Chatbot-Kontext berücksichtigt werden sollen. Der Chatbot kann nur auf die hier ausgewählten ' .
            'Materialien eingehen. Wenn keine Aktivierung erfolgt, wird der gesamte Kursinhalt berücksichtigt.</div>');
        
        // Aktiviere die selektive Aktivitätsauswahl
        $mform->addElement('advcheckbox', 'config_use_specific_activities', 
            'Aktivitätsauswahl aktivieren', 
            'Nur ausgewählte Aktivitäten in den Kontext einbeziehen', 
            array(), array(0, 1));
        $mform->setDefault('config_use_specific_activities', 0);
        
        // Aktivitäten des Kurses auflisten und als Auswahlmöglichkeiten anbieten
        global $COURSE;
        $modinfo = get_fast_modinfo($COURSE);
        $cms = $modinfo->get_cms();
        
        // Aktivitäten nach Sektionen gruppieren
        $activities_by_section = array();
        
        foreach ($cms as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            
            $section_num = $cm->sectionnum;
            $section_name = get_section_name($COURSE, $section_num);
            
            if (!isset($activities_by_section[$section_num])) {
                $activities_by_section[$section_num] = array(
                    'name' => $section_name,
                    'activities' => array()
                );
            }
            
            $activities_by_section[$section_num]['activities'][] = $cm;
        }
        
        // Für jede Sektion eine Gruppe von Checkboxen erstellen
        foreach ($activities_by_section as $section_num => $section_data) {
            if (empty($section_data['activities'])) {
                continue;
            }
            
            $mform->addElement('html', '<div class="card mb-3">');
            $mform->addElement('html', '<div class="card-header">' . $section_data['name'] . '</div>');
            $mform->addElement('html', '<div class="card-body">');
            
            // Alle auswählen/abwählen Buttons
            $mform->addElement('html', '<div class="mb-2">');
            $mform->addElement('html', '<button type="button" class="btn btn-sm btn-outline-secondary select-all-section-' . $section_num . '">Alle auswählen</button> ');
            $mform->addElement('html', '<button type="button" class="btn btn-sm btn-outline-secondary deselect-all-section-' . $section_num . '">Alle abwählen</button>');
            $mform->addElement('html', '</div>');
            
            // Aktivitäten als Checkboxen anzeigen
            foreach ($section_data['activities'] as $cm) {
                $activity_icon = $cm->get_icon_url()->out(false);
                $checkbox_id = 'id_config_activity_' . $cm->id;
                
                $icon_html = '<img src="' . $activity_icon . '" alt="" class="icon" style="width:16px;height:16px;margin-right:5px;">';
                $mform->addElement('advcheckbox', 'config_activity_' . $cm->id, '', $icon_html . s($cm->name), array('id' => $checkbox_id), array(0, 1));
                $mform->setDefault('config_activity_' . $cm->id, 1); // Standardmäßig ausgewählt
                
                // Die Checkbox deaktivieren, wenn die selektive Aktivitätsauswahl nicht aktiviert ist
                $mform->disabledIf('config_activity_' . $cm->id, 'config_use_specific_activities', 'neq', 1);
            }
            
            $mform->addElement('html', '</div>'); // card-body
            $mform->addElement('html', '</div>'); // card
        }
        
        // JavaScript für die "Alle auswählen/abwählen" Buttons
        $script = "
        require(['jquery'], function($) {
            $(document).ready(function() {
                // Verarbeite die Buttons für jede Sektion
                " . implode("\n", array_map(function($section_num) {
                    return "
                    $('.select-all-section-{$section_num}').on('click', function() {
                        // Finde die übergeordnete Card (der aktuelle Abschnitt)
                        var sectionCard = $(this).closest('.card');
                        
                        // Wähle nur Checkboxen innerhalb dieser Card
                        sectionCard.find('[id^=\"id_config_activity_\"]').each(function() {
                            if(!$(this).prop('disabled')) {
                                $(this).prop('checked', true);
                            }
                        });
                    });
                    
                    $('.deselect-all-section-{$section_num}').on('click', function() {
                        // Finde die übergeordnete Card (der aktuelle Abschnitt)
                        var sectionCard = $(this).closest('.card');
                        
                        // Wähle nur Checkboxen innerhalb dieser Card
                        sectionCard.find('[id^=\"id_config_activity_\"]').each(function() {
                            if(!$(this).prop('disabled')) {
                                $(this).prop('checked', false);
                            }
                        });
                    });
                    ";
                }, array_keys($activities_by_section))) . "
                
                // Verarbeite die Hauptcheckbox für selektive Aktivitätsauswahl
                $('#id_config_use_specific_activities').on('change', function() {
                    var isChecked = $(this).prop('checked');
                    $('[id^=\"id_config_activity_\"]').prop('disabled', !isChecked);
                });
            });
        });
        ";
        
        $mform->addElement('html', '<script>' . $script . '</script>');

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
        
        // Analysen-Dashboard-Button
        global $COURSE;
        $url = new moodle_url('/blocks/chatbot/analytics.php', 
            array('id' => $this->block->instance->id, 'course' => $COURSE->id));
        $analytics_link = html_writer::link($url, get_string('analytics_dashboard', 'block_chatbot'), 
            array('class' => 'btn btn-primary mb-3', 'target' => '_blank'));
        $mform->addElement('static', 'analytics_dashboard_link', '', $analytics_link);
        $mform->disabledIf('analytics_dashboard_link', 'config_enable_analytics', 'neq', 1);

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