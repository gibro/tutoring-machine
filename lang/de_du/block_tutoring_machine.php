<?php
// German language strings for the Tutoring Machine block.
$string['pluginname']         = 'Tutoring Machine';
$string['tutoring_machine:addinstance'] = 'Einen neuen Tutoring Machine-Block hinzufügen';
$string['tutoring_machine:myaddinstance'] = 'Einen neuen Tutoring Machine-Block zum Dashboard hinzufügen';
$string['configtitle'] = 'Blocktitel';
$string['configtitle_help'] = 'Überschreibt den Standardnamen der Tutoring Machine für diese Blockinstanz. Lass das Feld leer, um den globalen Namen zu behalten.';
$string['configassistantname'] = 'Name des Assistenten';
$string['configassistantname_help'] = 'Trage hier einen individuellen Namen ein, der in allen Antworten der Tutoring Machine für diesen Block angezeigt wird. Leer lassen, um den globalen Assistentennamen zu übernehmen.';
$string['configwelcomemessage'] = 'Willkommensnachricht';
$string['configwelcomemessage_help'] = 'Bestimme die erste Nachricht, die Lernende sehen, wenn sie den Chat öffnen. Nutze sie, um die Rolle des Assistenten zu erklären oder Hinweise zur Transparenz zu geben.';
$string['configwelcomemessageanalytics'] = 'Willkommensnachricht mit Analytics';
$string['configwelcomemessageanalytics_help'] = 'Optionale alternative Nachricht, wenn die Lehr-Analysen aktiv sind. Lass das Feld leer, um die normale Willkommensnachricht weiterzuverwenden.';
$string['configincludecontext'] = 'Kurskontext teilen';
$string['configincludecontext_help'] = 'Wenn aktiviert, schickt die Tutoring Machine ausgewählte Kursmaterialien mit jeder Anfrage an die KI, damit Antworten direkt auf deinen Moodle-Kurs abgestimmt sind. Lass die Option deaktiviert, wenn keine Kursinhalte den Anbieter verlassen sollen.';
$string['configincludecontext_option'] = 'Kursmaterialien im Kontext an die KI weitergeben';
$string['context_links_enable'] = 'Externe Links einbeziehen';
$string['context_links'] = 'Externe Kontext-Links';
$string['context_links_help'] = 'Eine HTTPS-URL pro Zeile. Nur Domains aus der globalen Whitelist werden verarbeitet. Ist die Weitergabe aktiviert, landen die bereinigten Inhalte im Kontext.';
$string['default_welcome_message'] = 'Standard-Willkommensnachricht';
$string['default_welcome_message_desc'] = 'Füllt die Willkommensnachricht für neue Blockinstanzen automatisch aus. Lernende sehen diesen Text, solange du keine kursindividuelle Nachricht speicherst.';
$string['default_welcome_message_value'] = 'Hey! Schön, dass du hier bist. Ich bin dein Lern-Begleiter. Frag mich einfach, wenn du Hilfe brauchst! 😊';
$string['default_welcome_message_analytics'] = 'Standard-Willkommensnachricht (Analytics aktiv)';
$string['default_welcome_message_analytics_desc'] = 'Füllt die alternative Nachricht, wenn die Lehr-Analysen aktiv sind. Lass das Feld leer, um die Standard-Willkommensnachricht zu übernehmen.';
$string['default_welcome_message_analytics_value'] = 'Hey! Schön, dass du hier bist. Ich bin dein Lern-Begleiter. Deine Referentinnen oder/und Referenten haben die Chat-Analyse aktiviert, um aus deinen Fragen zu lernen, welche Inhalte zusätzliche Nachfragen erzeugt haben. Die Analyse findet anonym statt und ist nie auf dich zurückführbar. Stelle also alle Fragen, und denk daran, es gibt keine dummen Fragen ;-).';
$string['assistantname']      = 'Assistent*innen-Name';
$string['assistantnamedesc']  = 'Gebe den Namen ein, der im Chat als Assistent angezeigt wird.';
$string['courselogo']         = 'Kursspezifisches Logo';
$string['courselogo_help']    = 'Lade ein eigenes Logo für diese Kursinstanz hoch. Wenn nicht festgelegt, wird das Standard-Logo verwendet.';
$string['apikey']             = 'API-Schlüssel';
$string['apikeydesc']         = 'Gib deinen OpenAI API-Schlüssel für die Tutoring Machine-Funktionalität ein.';
$string['metaprompt']         = 'Tutoring Machine-Anweisungen';
$string['metaprompt_help']    = 'Gib hier besondere Anweisungen für die Tutoring Machine in diesem Kurs ein. Diese Anweisungen werden vor jede Benutzeranfrage gestellt. Beispiel: "Verwende stets eine freundliche und einfache Sprache." oder "Du bist ein Moodle-Support-Assistent, verwende stets die informelle Anrede und duze die Nutzer*innen."';

// Darstellungs-Einstellungen
$string['appearance'] = 'Darstellung';
$string['main_color'] = 'Hauptfarbe';
$string['main_color_help'] = 'Wähle die Hauptfarbe für die Tutoring Machine-Oberfläche. Diese Farbe wird für die Kopfzeile, Schaltflächen und Benutzer-Nachrichtenblasen verwendet.';

// Kontextquellen-Einstellungen
$string['contextsources'] = 'Kontextquellen';
$string['use_textpages'] = 'Textseiten einbeziehen';
$string['use_textpages_desc'] = 'Inhalte aus Textseiten des Kurses als Kontext für Antworten verwenden.';
$string['use_textpages_help'] = 'Wenn aktiviert, werden die Inhalte aller Textseiten im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_glossaries'] = 'Glossare einbeziehen';
$string['use_glossaries_desc'] = 'Inhalte aus Glossaren des Kurses als Kontext für Antworten verwenden.';
$string['use_glossaries_help'] = 'Wenn aktiviert, werden die Einträge aller Glossare im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_internet'] = 'Internetsuche erlauben';
$string['use_internet_desc'] = 'Der Tutoring Machine erlauben, Informationen aus dem Internet zu verwenden, wenn keine Antwort im Kursinhalt gefunden wird.';
$string['use_internet_help'] = 'Wenn aktiviert, kann die Tutoring Machine Informationen aus dem Internet heranziehen, um Fragen zu beantworten, die nicht durch den Kursinhalt abgedeckt sind. Diese Informationen werden mit dem Kursinhalt kombiniert.';
$string['use_h5p'] = 'H5P-Aktivitäten einbeziehen';
$string['use_h5p_desc'] = 'Inhalte aus H5P-Aktivitäten des Kurses als Kontext für Antworten verwenden.';
$string['use_h5p_help'] = 'Wenn aktiviert, werden die Inhalte aller H5P-Aktivitäten im Kurs für die Beantwortung von Fragen verwendet.';
$string['use_pdfs'] = 'PDF-Dokumente einbeziehen';
$string['use_pdfs_desc'] = 'Inhalte aus PDF-Dokumenten des Kurses als Kontext für Antworten verwenden.';
$string['use_pdfs_help'] = 'Wenn aktiviert, werden die Texte aller PDF-Dokumente im Kurs extrahiert und für die Beantwortung von Fragen verwendet.';

$string['use_office'] = 'Office-Dokumente einbeziehen';
$string['use_office_desc'] = 'Inhalte aus Word-, Excel- und PowerPoint-Dokumenten des Kurses als Kontext für Antworten verwenden.';
$string['use_office_help'] = 'Wenn aktiviert, werden die Texte aller Microsoft Office-Dokumente (Word, Excel, PowerPoint) im Kurs extrahiert und für die Beantwortung von Fragen verwendet.';

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
$string['prompt_suggestions_desc'] = 'Stelle Empfehlungen für effektive Prompts bereit, die Lernende mit dem Tutoring Machine verwenden können.';
$string['prompt_suggestions_help'] = 'Gib eine Empfehlung pro Zeile ein. Diese werden als anklickbare Optionen in der Chat-Oberfläche angezeigt.';
$string['enable_prompt_suggestions'] = 'Prompt-Empfehlungen aktivieren';
$string['enable_prompt_suggestions_desc'] = 'Zeigt einen Button in der Chat-Oberfläche an, mit dem Lernende aus vordefinierten Prompt-Empfehlungen auswählen können.';
$string['prompt_suggestions_button_text'] = 'Vorschläge';
$string['prompt_suggestions_placeholder'] = 'Z.B.:
Erkläre mir das Konzept von...
Vergleiche und kontrastiere...
Fasse die wichtigsten Punkte zu... zusammen
Hilf mir zu verstehen...';
$string['linkdomain_whitelist'] = 'Domain-Whitelist für Links';
$string['linkdomain_whitelist_desc'] = 'Nur URLs von diesen Domains werden geholt (eine Domain pro Zeile). Subdomains sind automatisch erlaubt.';
$string['respect_robots'] = 'robots.txt respektieren';
$string['respect_robots_desc'] = 'Wenn aktiviert, prüft die Tutoring Machine robots.txt und lässt gesperrte Pfade aus.';
$string['link_useragent'] = 'User-Agent für Linkabrufe';
$string['link_useragent_desc'] = 'HTTP User-Agent, mit dem externe Links abgerufen werden.';
$string['link_refresh_ttl'] = 'Link-Aktualisierungsintervall (Sekunden)';
$string['link_refresh_ttl_desc'] = 'Wie lange gecachte Link-Inhalte gültig bleiben, bevor sie neu geladen werden. Leer lassen = 24 Stunden.';
$string['task_refresh_links'] = 'Externe Linkquellen aktualisieren';
$string['default_include_context'] = 'Kurskontext standardmäßig teilen';
$string['default_include_context_desc'] = 'Legt fest, ob neue Blöcke Kursmaterialien automatisch beim KI-Anbieter einreichen. Referent*innen können das pro Kurs überschreiben.';
