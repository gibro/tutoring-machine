<?php
// German language strings for the Chatbot block.
$string['pluginname']         = 'Chatbot';
$string['assistantname']      = 'Assistantname';
$string['assistantnamedesc']  = 'Geben Sie den Namen ein, der im Chat als Assistant angezeigt wird.';
$string['courselogo']         = 'Kursspezifisches Logo';
$string['courselogo_help']    = 'Laden Sie ein eigenes Logo für diese Kursinstanz hoch. Wenn nicht festgelegt, wird das Standard-Logo verwendet.';
// API-Schlüssel
$string['openai_apikey']      = 'OpenAI API-Schlüssel';
$string['openai_apikeydesc']  = 'Geben Sie Ihren OpenAI API-Schlüssel für den Zugriff auf OpenAI-Modelle ein (GPT-4, GPT-4o, usw.).';
$string['google_apikey']      = 'Google API-Schlüssel';
$string['google_apikeydesc']  = 'Geben Sie Ihren Google API-Schlüssel für den Zugriff auf Gemini-Modelle ein.';
$string['anthropic_apikey']   = 'Anthropic API-Schlüssel';
$string['anthropic_apikeydesc'] = 'Geben Sie Ihren Anthropic API-Schlüssel für den Zugriff auf Claude-Modelle ein.';

// Veraltet - für Abwärtskompatibilitä
$string['apikey']             = 'API-Schlüssel (Veraltet)';
$string['apikeydesc']         = 'Geben Sie Ihren API-Schlüssel für die Chatbot-Funktionalität ein (veraltet, verwenden Sie stattdessen anbieterspezifische Schlüssel).';
$string['metaprompt']         = 'Chatbot-Anweisungen';
$string['metaprompt_help']    = 'Geben Sie hier besondere Anweisungen für den Chatbot in diesem Kurs ein. Diese Anweisungen werden vor jede Benutzeranfrage gestellt. Beispiel: "Verwenden Sie stets eine freundliche und einfache Sprache." oder "Sie sind ein Moodle-Support-Assistent, verwenden Sie stets die formelle Anrede."';
$string['course_content_context'] = 'Kursinhalt-Kontext';
$string['course_content_context_desc'] = 'Der Chatbot verwendet Inhalte aus dem Kurs (Textseiten und Glossare) als Kontext für seine Antworten.';

// Darstellungs-Einstellungen
$string['appearance'] = 'Darstellung';
$string['main_color'] = 'Hauptfarbe';
$string['main_color_help'] = 'Wählen Sie die Hauptfarbe für die Chatbot-Oberfläche. Diese Farbe wird für die Kopfzeile, Schaltflächen und Benutzer-Nachrichtenblasen verwendet.';

// Kontextquellen-Einstellungen
$string['contextsources'] = 'Kontextquellen';
$string['use_textpages'] = 'Textseiten einbeziehen';
$string['use_textpages_desc'] = 'Inhalte aus Textseiten des Kurses als Kontext für Antworten verwenden.';
$string['use_textpages_help'] = 'Wenn aktiviert, werden die Inhalte aller Textseiten im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_glossaries'] = 'Glossare einbeziehen';
$string['use_glossaries_desc'] = 'Inhalte aus Glossaren des Kurses als Kontext für Antworten verwenden.';
$string['use_glossaries_help'] = 'Wenn aktiviert, werden die Einträge aller Glossare im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_internet'] = 'Internetsuche erlauben';
$string['use_internet_desc'] = 'Dem Chatbot erlauben, Informationen aus dem Internet zu verwenden, wenn keine Antwort im Kursinhalt gefunden wird.';
$string['use_internet_help'] = 'Wenn aktiviert, kann der Chatbot Informationen aus dem Internet heranziehen, um Fragen zu beantworten, die nicht durch den Kursinhalt abgedeckt sind. Diese Informationen werden mit dem Kursinhalt kombiniert.';
$string['use_h5p'] = 'H5P-Aktivitäten einbeziehen';
$string['use_h5p_desc'] = 'Inhalte aus H5P-Aktivitäten des Kurses als Kontext für Antworten verwenden.';
$string['use_h5p_help'] = 'Wenn aktiviert, werden die Inhalte aller H5P-Aktivitäten im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_pdfs'] = 'PDF-Dokumente einbeziehen';
$string['use_pdfs_desc'] = 'Inhalte aus PDF-Dokumenten des Kurses als Kontext für Antworten verwenden.';
$string['use_pdfs_help'] = 'Wenn aktiviert, werden die Texte aller PDF-Dokumente im Kurs extrahiert und für die Beantwortung von Fragen verwendet.';

$string['use_forums'] = 'Foren einbeziehen';
$string['use_forums_desc'] = 'Inhalte aus Forenbeiträgen des Kurses als Kontext für Antworten verwenden.';
$string['use_forums_help'] = 'Wenn aktiviert, werden die Diskussionen aller Foren im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_quizzes'] = 'Quizze einbeziehen';
$string['use_quizzes_desc'] = 'Inhalte aus Quizfragen des Kurses als Kontext für Antworten verwenden.';
$string['use_quizzes_help'] = 'Wenn aktiviert, werden die Fragen und Antworten aller Quizze im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_books'] = 'Bücher einbeziehen';
$string['use_books_desc'] = 'Inhalte aus Buch-Aktivitäten des Kurses als Kontext für Antworten verwenden.';
$string['use_books_help'] = 'Wenn aktiviert, werden die Kapitel aller Bücher im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_assignments'] = 'Aufgaben einbeziehen';
$string['use_assignments_desc'] = 'Inhalte aus Aufgabenstellungen des Kurses als Kontext für Antworten verwenden.';
$string['use_assignments_help'] = 'Wenn aktiviert, werden die Beschreibungen aller Aufgaben im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_labels'] = 'Textfelder einbeziehen';
$string['use_labels_desc'] = 'Inhalte aus Textfeldern des Kurses als Kontext für Antworten verwenden.';
$string['use_labels_help'] = 'Wenn aktiviert, werden die Inhalte aller Textfelder im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_urls'] = 'URL-Ressourcen einbeziehen';
$string['use_urls_desc'] = 'Inhalte aus URL-Ressourcen des Kurses als Kontext für Antworten verwenden.';
$string['use_urls_help'] = 'Wenn aktiviert, werden die Beschreibungen und URLs aller URL-Ressourcen im Kurs für die Beantwortung von Fragen verwendet.';

$string['use_lessons'] = 'Lektionen einbeziehen';
$string['use_lessons_desc'] = 'Inhalte aus Lektionen des Kurses als Kontext für Antworten verwenden.';
$string['use_lessons_help'] = 'Wenn aktiviert, werden die Inhalte aller Lektionen im Kurs für die Beantwortung von Fragen verwendet.';

// Admin-Einstellungsgruppen
$string['basicsettings'] = 'Grundlegende Einstellungen';
$string['basicsettings_desc'] = 'Konfigurieren Sie hier die Grundeinstellungen für den Chatbot-Block. Um die API-Verbindung zu testen, können Sie das <a href="{$CFG->wwwroot}/blocks/chatbot/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, das die konfigurierten API-Schlüssel überprüft.';
$string['modelsettings'] = 'KI-Modell-Einstellungen';
$string['modelsettingsdesc'] = 'Wählen Sie das KI-Modell aus, das standardmäßig für alle Chatbot-Instanzen verwendet werden soll. Modelle unterscheiden sich in Qualität, Geschwindigkeit und Kosten.';
$string['model_usage_info'] = 'Anwendungsempfehlungen';
$string['model_usage_info_desc'] = 'Modellempfehlungen für verschiedene Lernszenarien:<ul>
<li><strong>Für Tutoring und komplexe Erklärungen:</strong> GPT-4 oder GPT-4o (beste Qualität)</li>
<li><strong>Für Recherche-Hilfe und Quellenverweis:</strong> GPT-4-Turbo (schnell und genau)</li>
<li><strong>Für Zusammenfassungen und einfache Konzepterklärungen:</strong> GPT-4o-Mini (kostengünstig)</li>
<li><strong>Für schnelle Hilfe bei einfachen Fragen:</strong> GPT-4.1-Nano (höchste Geschwindigkeit)</li>
<li><strong>Für ausgewogenes Preis-Leistungs-Verhältnis:</strong> GPT-4.1-Mini (gute Balance)</li>
</ul>';
$string['parametersettings'] = 'Parameter-Einstellungen';
$string['parametersettingsdesc'] = 'Konfigurieren Sie die Parameter für die KI-Antwortgenerierung, um Qualität und Stil der Antworten anzupassen.';
$string['systemsettings'] = 'System-Einstellungen';
$string['systemsettings_desc'] = 'Erweiterte Einstellungen für die API-Kommunikation. Bei Verbindungsproblemen können Sie das <a href="{$CFG->wwwroot}/blocks/chatbot/api_test_web.php" target="_blank">API-Test-Tool</a> verwenden, um die Kommunikation mit den AI-Providern zu diagnostizieren.';

// Anbieter-Einstellungen
$string['default_provider'] = 'Standard-KI-Anbieter';
$string['default_provider_desc'] = 'Wählen Sie den KI-Anbieter aus, der für alle Chatbot-Instanzen verwendet werden soll, sofern nicht anders konfiguriert.';
$string['provider_openai'] = 'OpenAI (GPT-4, GPT-4o)';
$string['provider_google'] = 'Google (Gemini)';
$string['provider_anthropic'] = 'Anthropic (Claude)';

// Modell-Einstellungen
$string['default_model'] = 'Standard-KI-Modell';
$string['default_model_desc'] = 'Wählen Sie das KI-Modell aus, das für alle Chatbot-Instanzen verwendet wird, sofern nicht anders konfiguriert.';
$string['ai_model'] = 'KI-Modell für diesen Kurs';
$string['ai_model_help'] = 'Wählen Sie das KI-Modell, das für diesen Chatbot verwendet werden soll. Wenn Sie die Systemvorgabe verwenden, wird die in den globalen Einstellungen festgelegte Option verwendet.';
$string['use_system_default'] = 'Systemvorgabe verwenden';
$string['usage_intent'] = 'Verwendungszweck';
$string['usage_intent_help'] = 'Wählen Sie den primären Verwendungszweck des Chatbots in diesem Kurs. Dies hilft bei der Auswahl des optimalen KI-Modells.';
$string['usage_intent_tutor'] = 'Tutor & komplexe Erklärungen';
$string['usage_intent_research'] = 'Recherche-Hilfe & Quellenverweis';
$string['usage_intent_summarization'] = 'Zusammenfassungen & Konzepterklärungen';
$string['usage_intent_qa'] = 'Einfache Fragen & Antworten';
$string['usage_intent_creative'] = 'Kreatives Lernen & Ideenfindung';
$string['model_recommendations'] = 'Modellempfehlungen nach Verwendungszweck';

// Parameter-Einstellungen
$string['temperature'] = 'Kreativität (Temperatur)';
$string['temperature_desc'] = 'Steuert, wie kreativ und vielfältig die Antworten des Chatbots sind. Niedrigere Werte (0.1-0.5) erzeugen konsistentere, faktenbezogenere Antworten, während höhere Werte (0.6-1.0) kreativere und vielfältigere Antworten erzeugen.';
$string['top_p'] = 'Antwortvielfalt (Top-P)';
$string['top_p_desc'] = 'Kontrolliert die Variabilität der Wortauswahl - im Unterschied zur Temperatur, die die allgemeine Kreativität steuert. Top-P beschränkt die Auswahl auf die wahrscheinlichsten Token und bestimmt so die "Fokussierung" der Antwort. Ein höherer Wert (0.9-1.0) lässt mehr unterschiedliche Formulierungen zu, während ein niedrigerer Wert (0.1-0.5) die Antworten stärker auf vorhersehbare Formulierungen konzentriert.';

$string['response_format'] = 'Antwortformat';
$string['response_format_desc'] = 'Legt fest, in welchem Format die Antworten des KI-Modells zurückgegeben werden sollen. "Text" ist das Standardformat für natürlichsprachliche Antworten. "JSON" erzwingt eine strukturierte Ausgabe nach JSON-Schema, was besonders nützlich ist, wenn die Antworten programmatisch weiterverarbeitet werden sollen. Bei JSON-Format werden Antworten immer in gültigen JSON-Objekten geliefert, wodurch die Zuverlässigkeit der Verarbeitung erhöht wird, aber die Natürlichkeit des Texts eingeschränkt sein kann.';
$string['response_format_text'] = 'Text (natürlichsprachliche Antworten)';
$string['response_format_json'] = 'JSON (strukturierte, maschinenlesbare Antworten)';
$string['max_tokens'] = 'Maximale Token-Anzahl';
$string['max_tokens_desc'] = 'Die maximale Anzahl von Tokens (Wortteilen), die das Modell für eine Antwort generieren kann. Höhere Werte ermöglichen längere Antworten, erhöhen aber die Kosten und die Antwortzeit.';
$string['timeout'] = 'Zeitlimit (Sekunden)';
$string['timeout_desc'] = 'Die maximale Zeit in Sekunden, die auf eine Antwort vom KI-Service gewartet wird, bevor die Anfrage abbricht.';

// Fehlermeldungen
$string['noapikey'] = 'API-Schlüssel nicht konfiguriert. Bitte konfigurieren Sie den API-Schlüssel in den Einstellungen des Chatbot-Blocks.';
$string['apiconnectionerror'] = 'Fehler bei der Verbindung zur API. Bitte versuchen Sie es später erneut.';
$string['toomanyrequests'] = 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.';
$string['invalidcourseid'] = 'Ungültige Kurs-ID.';
$string['usernotincourse'] = 'Sie sind nicht in diesem Kurs eingeschrieben.';
$string['invalidblockid'] = 'Ungültige Block-ID.';
$string['blocktypenotchatbot'] = 'Block ist kein Chatbot-Block.';
$string['accessdenied'] = 'Zugriff verweigert.';
$string['nopermission'] = 'Sie haben keine Berechtigung, auf diese Ressource zuzugreifen.';
$string['missingparam'] = 'Erforderlicher Parameter fehlt: {$a}.';
$string['invalidparam'] = 'Ungültiger Parameter: {$a}.';
$string['internalerror'] = 'Ein interner Fehler ist aufgetreten.';
$string['csrfcheck'] = 'CSRF-Prüfung fehlgeschlagen.';
$string['messagerequired'] = 'Nachricht darf nicht leer sein.';

// Teaching Analytics
$string['teachinganalytics'] = 'Lehr-Analysen';
$string['enable_analytics'] = 'Lehr-Analysen aktivieren';
$string['enable_analytics_desc'] = 'Sammelt anonymisierte Nutzereingaben zur Analyse der am häufigsten gestellten Fragen. Daten werden ohne Benutzer-Identifikation gespeichert.';
$string['enable_analytics_help'] = 'Wenn aktiviert, werden die Fragen der Lernenden anonym gespeichert, um den Lehrenden Einblicke in häufig gestellte Fragen zu geben. Es werden keine personenbezogenen Daten erfasst.';
$string['analytics_notice_title'] = 'Datenschutzhinweis';
$string['analytics_notice'] = '<strong>Wichtig:</strong> Wenn Sie die Lehr-Analysen aktivieren, wird den Lernenden bei der ersten Nutzung des Chatbots ein Hinweis angezeigt, dass ihre Anfragen anonym zu Analysezwecken gespeichert werden.';
$string['analytics_retention'] = 'Aufbewahrungsdauer der Daten';
$string['analytics_retention_help'] = 'Legt fest, wie lange die anonymisierten Anfragen gespeichert werden, bevor sie automatisch gelöscht werden.';
$string['retention_1week'] = '1 Woche';
$string['retention_1month'] = '1 Monat';
$string['retention_3months'] = '3 Monate';
$string['retention_6months'] = '6 Monate';
$string['retention_1year'] = '1 Jahr';

$string['analytics_dashboard'] = 'Analysen-Dashboard';
$string['analytics_dashboard_desc'] = 'Zeigt Statistiken zu den am häufigsten gestellten Fragen im Kurs.';
$string['no_analytics_data'] = 'Keine Analysedaten verfügbar. Aktivieren Sie die Lehr-Analysen und warten Sie, bis Lernende den Chatbot genutzt haben.';
$string['most_common_questions'] = 'Häufigste Fragen';
$string['query_count'] = 'Anzahl';
$string['total_queries'] = 'Gesamtzahl der Anfragen';
$string['queries_last_days'] = 'Anfragen der letzten {$a} Tage';
$string['analytics_timeperiod'] = 'Zeitraum';
$string['analytics_not_enabled'] = 'Die Lehr-Analysen sind für diesen Chatbot nicht aktiviert. Bitte aktivieren Sie die Analysen in den Block-Einstellungen.';
$string['query'] = 'Anfrage';
$string['query_types'] = 'Anfragetypen';
$string['queries'] = 'Anfragen';
$string['querytype_content'] = 'Inhaltsfragen';
$string['querytype_assignment'] = 'Aufgabenfragen';
$string['querytype_exam'] = 'Prüfungsfragen';
$string['querytype_grade'] = 'Notenfragen';
$string['querytype_technical'] = 'Technische Fragen';
$string['querytype_schedule'] = 'Terminfragen';
$string['data_anonymized_notice'] = 'Alle Daten sind anonymisiert. Es sind keine Rückschlüsse auf einzelne Benutzer möglich.';
$string['analytics_info'] = 'Die Lehr-Analysen zeigen anonymisierte Anfragen von Lernenden an den Chatbot. Diese Informationen können helfen, häufige Fragen zu identifizieren und Lehrmaterialien entsprechend anzupassen.';
$string['data_collection_notice'] = 'Hey! Schön, dass du hier bist. Ich bin dein Lern-Begleiter. Deine Referentinnen oder/und Referenten haben die Chat-Analyse aktiviert, um aus deinen Fragen zu lernen, welche Inhalte zusätzliche Nachfragen erzeugt haben. Die Analyse findet anonym statt und ist nie auf dich zurückführbar. Stelle also alle Fragen, und denk daran, es gibt keine dummen Fragen ;-).';

// Task strings
$string['task_cleanup_analytics'] = 'Bereinigung alter Analytics-Daten für den Chatbot';

// Prompt-Empfehlungen
$string['prompt_suggestions'] = 'Prompt-Empfehlungen';
$string['prompt_suggestions_desc'] = 'Stellen Sie Empfehlungen für effektive Prompts bereit, die Lernende mit dem Chatbot verwenden können.';
$string['prompt_suggestions_help'] = 'Geben Sie eine Empfehlung pro Zeile ein. Diese werden als anklickbare Optionen in der Chat-Oberfläche angezeigt.';
$string['enable_prompt_suggestions'] = 'Prompt-Empfehlungen aktivieren';
$string['enable_prompt_suggestions_desc'] = 'Zeigt einen Button in der Chat-Oberfläche an, mit dem Lernende aus vordefinierten Prompt-Empfehlungen auswählen können.';
$string['prompt_suggestions_button_text'] = 'Vorschläge';
$string['prompt_suggestions_placeholder'] = 'Z.B.:
Erkläre mir das Konzept von...
Vergleiche und kontrastiere...
Fasse die wichtigsten Punkte zu... zusammen
Hilf mir zu verstehen...';