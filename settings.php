<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

// Standardeinstellungen für Moodle-Blöcke
if ($ADMIN->fulltree) {
    // Grundlegende Einstellungen
    $settings->add(new admin_setting_heading(
        'block_tutoring_machine/basicsettings',
        get_string('basicsettings', 'block_tutoring_machine'),
        ''
    ));

    // Einstellung: Assistantname
    $settings->add(new admin_setting_configtext(
        'block_tutoring_machine/assistantname',
        get_string('assistantname', 'block_tutoring_machine'),
        get_string('assistantnamedesc', 'block_tutoring_machine'),
        'Tutoring Machine',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_tutoring_machine/default_include_context',
        get_string('default_include_context', 'block_tutoring_machine'),
        get_string('default_include_context_desc', 'block_tutoring_machine'),
        0
    ));

    $defaultwhitelist = "example.com\nopeneducationalresources.de\noercommons.org";
    $settings->add(new admin_setting_configtextarea(
        'block_tutoring_machine/linkdomain_whitelist',
        get_string('linkdomain_whitelist', 'block_tutoring_machine'),
        get_string('linkdomain_whitelist_desc', 'block_tutoring_machine'),
        $defaultwhitelist,
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_tutoring_machine/respect_robots',
        get_string('respect_robots', 'block_tutoring_machine'),
        get_string('respect_robots_desc', 'block_tutoring_machine'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'block_tutoring_machine/link_useragent',
        get_string('link_useragent', 'block_tutoring_machine'),
        get_string('link_useragent_desc', 'block_tutoring_machine'),
        'MoodleTutoringMachineBot/1.0 (+contact: admin@example.org)',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'block_tutoring_machine/link_refresh_ttl',
        get_string('link_refresh_ttl', 'block_tutoring_machine'),
        get_string('link_refresh_ttl_desc', 'block_tutoring_machine'),
        '86400',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtextarea(
        'block_tutoring_machine/default_welcome_message',
        get_string('default_welcome_message', 'block_tutoring_machine'),
        get_string('default_welcome_message_desc', 'block_tutoring_machine'),
        get_string('default_welcome_message_value', 'block_tutoring_machine'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtextarea(
        'block_tutoring_machine/default_welcome_message_analytics',
        get_string('default_welcome_message_analytics', 'block_tutoring_machine'),
        get_string('default_welcome_message_analytics_desc', 'block_tutoring_machine'),
        get_string('default_welcome_message_analytics_value', 'block_tutoring_machine'),
        PARAM_TEXT
    ));

    // Einstellung: API Key für OpenAI
    $settings->add(new admin_setting_configpasswordunmask(
        'block_tutoring_machine/openai_apikey',
        get_string('openai_apikey', 'block_tutoring_machine'),
        get_string('openai_apikeydesc', 'block_tutoring_machine'),
        '',
        PARAM_TEXT
    ));

    // Einstellung: API Key für Google Gemini
    $settings->add(new admin_setting_configpasswordunmask(
        'block_tutoring_machine/google_apikey',
        get_string('google_apikey', 'block_tutoring_machine'),
        get_string('google_apikeydesc', 'block_tutoring_machine'),
        '',
        PARAM_TEXT
    ));

    // Claude API wurde entfern

    // API-Test-Info
    $api_test_info = 'Erweiterte Einstellungen für die API-Kommunikation. ';
    $api_test_info .= 'Bei Verbindungsproblemen können Sie das <a href="'.$CFG->wwwroot.'/blocks/tutoring_machine/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, ';
    $api_test_info .= 'um die Kommunikation mit den AI-Providern zu diagnostizieren.';

    $settings->add(new admin_setting_heading(
        'block_tutoring_machine/apitestinfo',
        'API-Test',
        $api_test_info
    ));

    // Modell-Einstellungen
    $settings->add(new admin_setting_heading(
        'block_tutoring_machine/modelsettings',
        get_string('modelsettings', 'block_tutoring_machine'),
        get_string('modelsettingsdesc', 'block_tutoring_machine')
    ));

    // Einstellung: KI-Modell
    $model_choices = [
        // OpenAI Models
        'openai:gpt-5' => 'OpenAI GPT-5 (komplexes Reasoning, Agenten-Workflows & Code - höchste Qualität)',
        'openai:gpt-5-mini' => 'OpenAI GPT-5 Mini (Kostenoptimiertes Reasoning & Chat - ausgewogene Leistung)',
        'openai:gpt-5-nano' => 'OpenAI GPT-5 Nano (Hochdurchsatz für einfache Aufgaben - sehr geringe Latenz)',

        // Google Gemini Models
        'google:gemini-1.5-pro' => 'Google Gemini 1.5 Pro (umfangreiches Wissen - allgemeine Verwendung)',
        'google:gemini-1.5-flash' => 'Google Gemini 1.5 Flash (schnelle Antworten - kostengünstig)',

        // Anthropic Models wurden entfernt
    ];

    $settings->add(new admin_setting_configselect(
        'block_tutoring_machine/default_model',
        get_string('default_model', 'block_tutoring_machine'),
        get_string('default_model_desc', 'block_tutoring_machine'),
        'openai:gpt-5',
        $model_choices
    ));

    // Einstellung: API-Provider-Auswahl
    $provider_choices = [
        'openai' => get_string('provider_openai', 'block_tutoring_machine'),
        'google' => get_string('provider_google', 'block_tutoring_machine')
    ];

    $settings->add(new admin_setting_configselect(
        'block_tutoring_machine/default_provider',
        get_string('default_provider', 'block_tutoring_machine'),
        get_string('default_provider_desc', 'block_tutoring_machine'),
        'openai',
        $provider_choices
    ));

    // Parameter-Einstellungen
    $settings->add(new admin_setting_heading(
        'block_tutoring_machine/parametersettings',
        get_string('parametersettings', 'block_tutoring_machine'),
        get_string('parametersettingsdesc', 'block_tutoring_machine')
    ));


    // Einstellung: Kreativität (Temperatur)
    $temperature_options = [];
    for ($i = 0.1; $i <= 1.0; $i += 0.1) {
        $temperature_options[number_format($i, 1)] = number_format($i, 1);
    }

    $settings->add(new admin_setting_configselect(
        'block_tutoring_machine/temperature',
        get_string('temperature', 'block_tutoring_machine'),
        get_string('temperature_desc', 'block_tutoring_machine'),
        '0.7', // Standardwer
        $temperature_options
    ));

    // Einstellung: Top-P/Nucleus Sampling
    $top_p_options = [];
    for ($i = 0.1; $i <= 1.0; $i += 0.1) {
        $top_p_options[number_format($i, 1)] = number_format($i, 1);
    }

    $settings->add(new admin_setting_configselect(
        'block_tutoring_machine/top_p',
        get_string('top_p', 'block_tutoring_machine'),
        get_string('top_p_desc', 'block_tutoring_machine'),
        '0.9', // Standardwer
        $top_p_options
    ));

    // Einstellung: Antwortforma
    $response_format_choices = [
        'text' => get_string('response_format_text', 'block_tutoring_machine'),
        'json' => get_string('response_format_json', 'block_tutoring_machine')
    ];

    $settings->add(new admin_setting_configselect(
        'block_tutoring_machine/response_format',
        get_string('response_format', 'block_tutoring_machine'),
        get_string('response_format_desc', 'block_tutoring_machine'),
        'text',
        $response_format_choices
    ));

    // System-Einstellungen
    $systemsettings_desc = 'Erweiterte Einstellungen für die API-Kommunikation. ';
    $systemsettings_desc .= 'Bei Verbindungsproblemen können Sie das <a href="'.$CFG->wwwroot.'/blocks/tutoring_machine/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, ';
    $systemsettings_desc .= 'um die Kommunikation mit den AI-Providern zu diagnostizieren.';

    $settings->add(new admin_setting_heading(
        'block_tutoring_machine/systemsettings',
        get_string('systemsettings', 'block_tutoring_machine'),
        $systemsettings_desc
    ));

    // Einstellung: Maximale Tokens
    $settings->add(new admin_setting_configtext(
        'block_tutoring_machine/max_tokens',
        get_string('max_tokens', 'block_tutoring_machine'),
        get_string('max_tokens_desc', 'block_tutoring_machine'),
        '2500',
        PARAM_INT
    ));

    // Einstellung: Timeou
    $settings->add(new admin_setting_configtext(
        'block_tutoring_machine/timeout',
        get_string('timeout', 'block_tutoring_machine'),
        get_string('timeout_desc', 'block_tutoring_machine'),
        '30',
        PARAM_INT
    ));
}
