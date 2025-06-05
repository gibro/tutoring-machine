<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

// Standardeinstellungen für Moodle-Blöcke
if ($ADMIN->fulltree) {
    // Grundlegende Einstellungen
    $settings->add(new admin_setting_heading(
        'block_chatbot/basicsettings',
        get_string('basicsettings', 'block_chatbot'),
        ''
    ));

    // Einstellung: Assistantname
    $settings->add(new admin_setting_configtext(
        'block_chatbot/assistantname',
        get_string('assistantname', 'block_chatbot'),
        get_string('assistantnamedesc', 'block_chatbot'),
        'Chatbot',
        PARAM_TEXT
    ));

    // Einstellung: API Key für OpenAI
    $settings->add(new admin_setting_configpasswordunmask(
        'block_chatbot/openai_apikey',
        get_string('openai_apikey', 'block_chatbot'),
        get_string('openai_apikeydesc', 'block_chatbot'),
        '',
        PARAM_TEXT
    ));

    // Einstellung: API Key für Google Gemini
    $settings->add(new admin_setting_configpasswordunmask(
        'block_chatbot/google_apikey',
        get_string('google_apikey', 'block_chatbot'),
        get_string('google_apikeydesc', 'block_chatbot'),
        '',
        PARAM_TEXT
    ));

    // Claude API wurde entfern

    // API-Test-Info
    $api_test_info = 'Erweiterte Einstellungen für die API-Kommunikation. ';
    $api_test_info .= 'Bei Verbindungsproblemen können Sie das <a href="'.$CFG->wwwroot.'/blocks/chatbot/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, ';
    $api_test_info .= 'um die Kommunikation mit den AI-Providern zu diagnostizieren.';

    $settings->add(new admin_setting_heading(
        'block_chatbot/apitestinfo',
        'API-Test',
        $api_test_info
    ));

    // Modell-Einstellungen
    $settings->add(new admin_setting_heading(
        'block_chatbot/modelsettings',
        get_string('modelsettings', 'block_chatbot'),
        get_string('modelsettingsdesc', 'block_chatbot')
    ));

    // Einstellung: KI-Modell
    $model_choices = [
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

    $settings->add(new admin_setting_configselect(
        'block_chatbot/default_model',
        get_string('default_model', 'block_chatbot'),
        get_string('default_model_desc', 'block_chatbot'),
        'openai:gpt-4o',
        $model_choices
    ));

    // Einstellung: API-Provider-Auswahl
    $provider_choices = [
        'openai' => get_string('provider_openai', 'block_chatbot'),
        'google' => get_string('provider_google', 'block_chatbot')
    ];

    $settings->add(new admin_setting_configselect(
        'block_chatbot/default_provider',
        get_string('default_provider', 'block_chatbot'),
        get_string('default_provider_desc', 'block_chatbot'),
        'openai',
        $provider_choices
    ));

    // Parameter-Einstellungen
    $settings->add(new admin_setting_heading(
        'block_chatbot/parametersettings',
        get_string('parametersettings', 'block_chatbot'),
        get_string('parametersettingsdesc', 'block_chatbot')
    ));


    // Einstellung: Kreativität (Temperatur)
    $temperature_options = [];
    for ($i = 0.1; $i <= 1.0; $i += 0.1) {
        $temperature_options[number_format($i, 1)] = number_format($i, 1);
    }

    $settings->add(new admin_setting_configselect(
        'block_chatbot/temperature',
        get_string('temperature', 'block_chatbot'),
        get_string('temperature_desc', 'block_chatbot'),
        '0.7', // Standardwer
        $temperature_options
    ));

    // Einstellung: Top-P/Nucleus Sampling
    $top_p_options = [];
    for ($i = 0.1; $i <= 1.0; $i += 0.1) {
        $top_p_options[number_format($i, 1)] = number_format($i, 1);
    }

    $settings->add(new admin_setting_configselect(
        'block_chatbot/top_p',
        get_string('top_p', 'block_chatbot'),
        get_string('top_p_desc', 'block_chatbot'),
        '0.9', // Standardwer
        $top_p_options
    ));

    // Einstellung: Antwortforma
    $response_format_choices = [
        'text' => get_string('response_format_text', 'block_chatbot'),
        'json' => get_string('response_format_json', 'block_chatbot')
    ];

    $settings->add(new admin_setting_configselect(
        'block_chatbot/response_format',
        get_string('response_format', 'block_chatbot'),
        get_string('response_format_desc', 'block_chatbot'),
        'text',
        $response_format_choices
    ));

    // System-Einstellungen
    $systemsettings_desc = 'Erweiterte Einstellungen für die API-Kommunikation. ';
    $systemsettings_desc .= 'Bei Verbindungsproblemen können Sie das <a href="'.$CFG->wwwroot.'/blocks/chatbot/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, ';
    $systemsettings_desc .= 'um die Kommunikation mit den AI-Providern zu diagnostizieren.';

    $settings->add(new admin_setting_heading(
        'block_chatbot/systemsettings',
        get_string('systemsettings', 'block_chatbot'),
        $systemsettings_desc
    ));

    // Einstellung: Maximale Tokens
    $settings->add(new admin_setting_configtext(
        'block_chatbot/max_tokens',
        get_string('max_tokens', 'block_chatbot'),
        get_string('max_tokens_desc', 'block_chatbot'),
        '2500',
        PARAM_INT
    ));

    // Einstellung: Timeou
    $settings->add(new admin_setting_configtext(
        'block_chatbot/timeout',
        get_string('timeout', 'block_chatbot'),
        get_string('timeout_desc', 'block_chatbot'),
        '30',
        PARAM_INT
    ));
}
