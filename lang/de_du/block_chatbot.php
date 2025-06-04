<?php
// German language strings for the Chatbot block.
$string['pluginname']         = 'Chatbot';
$string['assistantname']      = 'Assistent*innen-Name';
$string['assistantnamedesc']  = 'Gebe den Namen ein, der im Chat als Assistent angezeigt wird.';
$string['courselogo']         = 'Kursspezifisches Logo';
$string['courselogo_help']    = 'Lade ein eigenes Logo für diese Kursinstanz hoch. Wenn nicht festgelegt, wird das Standard-Logo verwendet.';
$string['apikey']             = 'API-Schlüssel';
$string['apikeydesc']         = 'Gib deinen OpenAI API-Schlüssel für die Chatbot-Funktionalität ein.';
$string['metaprompt']         = 'Chatbot-Anweisungen';
$string['metaprompt_help']    = 'Gib hier besondere Anweisungen für den Chatbot in diesem Kurs ein. Diese Anweisungen werden vor jede Benutzeranfrage gestellt. Beispiel: "Verwende stets eine freundliche und einfache Sprache." oder "Du bist ein Moodle-Support-Assistent, verwende stets die informelle Anrede und duze die Nutzer*innen."';

// Darstellungs-Einstellungen
$string['appearance'] = 'Darstellung';
$string['main_color'] = 'Hauptfarbe';
$string['main_color_help'] = 'Wähle die Hauptfarbe für die Chatbot-Oberfläche. Diese Farbe wird für die Kopfzeile, Schaltflächen und Benutzer-Nachrichtenblasen verwendet.';

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

// Teaching Analytics
$string['teachinganalytics'] = 'Lehr-Analysen';
$string['enable_analytics'] = 'Lehr-Analysen aktivieren';
$string['enable_analytics_desc'] = 'Sammelt anonymisierte Nutzereingaben zur Analyse der am häufigsten gestellten Fragen. Daten werden ohne Benutzer-Identifikation gespeichert.';
$string['enable_analytics_help'] = 'Wenn aktiviert, werden die Fragen der Lernenden anonym gespeichert, um den Lehrenden Einblicke in häufig gestellte Fragen zu geben. Es werden keine personenbezogenen Daten erfasst.';
$string['data_collection_notice'] = 'Hey! Schön, dass du hier bist. Ich bin dein Lern-Begleiter. Deine Referentinnen oder/und Referenten haben die Chat-Analyse aktiviert, um aus deinen Fragen zu lernen, welche Inhalte zusätzliche Nachfragen erzeugt haben. Die Analyse findet anonym statt und ist nie auf dich zurückführbar. Stelle also alle Fragen, und denk daran, es gibt keine dummen Fragen ;-).';

// Prompt-Empfehlungen
$string['prompt_suggestions'] = 'Prompt-Empfehlungen';
$string['prompt_suggestions_desc'] = 'Stelle Empfehlungen für effektive Prompts bereit, die Lernende mit dem Chatbot verwenden können.';
$string['prompt_suggestions_help'] = 'Gib eine Empfehlung pro Zeile ein. Diese werden als anklickbare Optionen in der Chat-Oberfläche angezeigt.';
$string['enable_prompt_suggestions'] = 'Prompt-Empfehlungen aktivieren';
$string['enable_prompt_suggestions_desc'] = 'Zeigt einen Button in der Chat-Oberfläche an, mit dem Lernende aus vordefinierten Prompt-Empfehlungen auswählen können.';
$string['prompt_suggestions_button_text'] = 'Vorschläge';
$string['prompt_suggestions_placeholder'] = 'Z.B.:
Erkläre mir das Konzept von...
Vergleiche und kontrastiere...
Fasse die wichtigsten Punkte zu... zusammen
Hilf mir zu verstehen...';
