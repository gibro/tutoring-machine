# Moodle Tutoring Machine Block

Ein Block für Moodle, der eine KI-basierte Tutoring Machine zur Unterstützung von Lernenden bereitstellt. Die Tutoring Machine kann Fragen zum Kursinhalt basierend auf verschiedenen Kursressourcen beantworten.

## Installation

1. Entpacke das Plugin in das `/blocks/tutoring_machine`-Verzeichnis Ihrer Moodle-Installation.
2. Navigiere zu "Website-Administration" > "Mitteilungen" > "Benachrichtigungen aktualisieren".
3. Folge dem Installationsprozess und bestätige die Installation.

## Konfiguration

Das Plugin kann über die Blockeinstellungen in jeder Kursseite konfiguriert werden:

1. Schalte den Bearbeitungsmodus ein.
2. Füge den "Tutoring Machine"-Block hinzu.
3. Klicken Sie auf das Zahnrad-Symbol und wähle "Tutoring Machine konfigurieren".
4. Passe die Einstellungen nach Bedarf an:
   - **Tutoring Machine-Anweisungen**: Gebe spezielle Anweisungen für die Tutoring Machine ein, zum Beispiel: Gebe keine Lösungen vor, sondern verweise auf das Material, in dem die Lösung auf die gestellte Frage zu finden ist.
   - **Willkommensnachrichten**: Lege Begrüßungstexte fest – inklusive einer optionalen Analytics-Variante –, damit Lernende direkt verstehen, welche Rolle der Bot übernimmt.
   - **Kontextweitergabe**: Schalte explizit frei, ob Kursmaterialien überhaupt an den KI-Anbieter übertragen werden dürfen. Erst danach lassen sich einzelne Aktivitäten selektiv einbeziehen.
   - **Willkommensnachrichten**: Lege Begrüßungstexte fest – inklusive einer optionalen Analytics-Variante –, damit Lernende direkt verstehen, welche Rolle der Bot übernimmt.
   - **Kontextweitergabe**: Schalte explizit frei, ob Kursinhalte überhaupt an den KI-Anbieter übertragen werden dürfen. Erst danach lassen sich einzelne Aktivitäten selektiv einbeziehen.
   - **KI-Modell-Einstellungen**: Wähle das Modell aus, dass für deine Aufgabe im Kurs geeignet ist
   - **Darstellung**: Wähle die Hauptfarbe für die Benutzeroberfläche
   - **Kontext**: Wähle, welche Kursressourcen als Kontext verwendet werden sollen:
     - Textseiten
     - Glossare
     - H5P-Aktivitäten
     - PDF-Dokumente
     - Internetsuche (für Fragen, die nicht durch Kursinhalte beantwortet werden können)
     Quellen, die nicht ausgewählt werden, werden auch nicht in den Kontext einbezogen, Die Tutoring Machine gibt dann die Antwort, dass dazu keine Informationen im Kurs gefunden werden konnten und die Referent*innen befragt werden müssen.
   - **Lehr-Analysen**: Wenn die Lehr-Analysen aktiviert werden, werden alle Prompts der Teilnehmenden gespeichert und in einem für die Referent*innen zugänglichen Analyse-Dashboard ausgewertet, bzw. angezeigt. Der Willkommensprompt weist die Teilnehmenden darauf hin, dass ihre Eingaben gespeichert und den Referent*innen zugänglich gemacht werden.
   - **Prompt-Empfehlungen**: Wenn aktiviert, wird ein weiterer Button angezeigt, mit dem den Teilnehmenden Prompt-Empfehlungen für die Nutzung der Tutoring Machine vorgeschlagen werden. Die können ausgewählt und abgeschickt werden. Um zu verschiedenen Kategorien Prompt-Empfehlugnen zu machen kann eine Raute vorausgeschickt werden. 
   Beispiel:
   #Anfänger
	Wie alt ist Einstein geworden?
	Wann ist Einstein geboren?
   #Forgeschrittene
	Wie alt ist Watts geworden?
	Wann ist Watts geboren?

## Globale Einstellungen

Globale Einstellungen für die Tutoring Machine können unter "Website-Administration" > "Plugins" > "Blöcke" > "Tutoring Machine" konfiguriert werden:

- **Assistantname**: Der Name, der im Chat-Interface angezeigt wird
- **Kurskontext standardmäßig teilen**: Voreinstellung, ob neue Blöcke Kursmaterialien automatisch an die KI weitergeben oder zunächst lokal behalten.
- **Domain-Whitelist für Links**: Nur Domains aus dieser Liste werden für externe Kontextlinks abgefragt.
- **robots.txt respektieren**: Steuert, ob der Linkcrawler Hinweise aus robots.txt beachtet und gesperrte Pfade auslässt.
- **Crawler-User-Agent**: HTTP User-Agent, unter dem der Linkcrawler auftritt.
- **Link-Aktualisierungsintervall**: Legt fest, wie lange gecachte Link-Inhalte gültig bleiben, bevor sie erneut geladen werden.
- **OpenAI API-Schlüssel**: Dein OpenAI API-Schlüssel für die Verbindung mit OpenAI-Modellen
- **Google API-Schlüssel**: Dein Google API-Schlüssel für die Verbindung mit Gemini-Modellen
- **Standard-Modell**: Das zu verwendende Standard-KI-Modell im Format "provider:model"
  - Unterstützte OpenAI-Modelle: gpt-5, gpt-5-mini, gpt-5-nano und weitere
  - Unterstützte Google-Modelle: gemini-1.5-pro, gemini-1.5-flash, gemini-pro

## Unterstützte Kursressourcen

Die Tutoring Machine kann folgende Kursressourcen als Kontext verwenden:

- Textseiten (Moodle Page-Aktivitäten)
- Glossare und deren Einträge
- H5P-Aktivitäten (wenn installiert)
- PDF-Dokumente (mit automatischer Textextraktion)

## Sicherheitsfunktionen

Die Tutoring Machine implementiert folgende Sicherheitsfunktionen:

- **Eingabevalidierung**: Alle Benutzereingaben werden geprüft und bereinigt
- **CSRF-Schutz**: Verhinderung von Cross-Site Request Forgery durch Session-Key-Validierung
- **Rate-Limiting**: Begrenzung auf 30 Anfragen pro Stunde pro Benutzer
- **XSS-Schutz**: Bereinigung von HTML-Inhalten zur Vermeidung von Cross-Site-Scripting
- **Zugriffskontrolle**: Prüfung der Benutzerberechtigungen

## Technische Anforderungen

- PHP 8.2 oder höher
- Moodle 4.1 oder höher
- OpenAI API-Schlüssel oder Google Gemini API-Schlüssel
- Für PDF-Textextraktion (mindestens eine der folgenden Optionen):
  - **pdftotext** (empfohlen, Teil von Poppler-Utils)
    - Ubuntu/Debian: `apt-get install poppler-utils`
    - CentOS/RHEL: `yum install poppler-utils`
    - macOS: `brew install poppler`
  - Alternative PHP-Bibliotheken:
    - Smalot/PdfParser: `composer require smalot/pdfparser`
    - Spatie/PdfToText: `composer require spatie/pdf-to-text`

## API-Test-Tools

Zur Überprüfung der API-Verbindungen stehen zwei Test-Tools zur Verfügung:

### Kommandozeilen-Test

Der Kommandozeilen-Test kann über die Moodle-Wurzel ausgeführt werden:

```
php blocks/tutoring_machine/api_test.php [provider]
```

Wobei `[provider]` einer der folgenden Werte sein kann:
- `openai` - Test nur für OpenAI API
- `google` - Test nur für Google Gemini API
- `all` - Test für alle konfigurierten APIs (Standard)

Die Ergebnisse werden sowohl in der Konsole ausgegeben als auch in einer Logdatei im Verzeichnis `blocks/tutoring_machine/logs/` gespeichert.

### Web-basierter Test

Der web-basierte Test kann über folgende URL aufgerufen werden:

```
https://ihre-moodle-url/blocks/tutoring_machine/api_test_web.php
```

Dieser Test bietet eine benutzerfreundliche Oberfläche zur Überprüfung der API-Verbindungen und ist nur für Administratoren im Entwicklungsmodus verfügbar.

## Unterstützte Sprachen

- Englisch
- Deutsch
- Deutsch Du-Form

## Caching-System

Die Tutoring Machine nutzt ein umfassendes Caching-System, um die Leistung zu verbessern und die Anzahl der API-Aufrufe zu reduzieren:

- **PDF-Caching**: Extrahierte PDF-Inhalte werden in der Datenbank gespeichert (TTL: 1 Woche)
- **Kursinhalt-Caching**: Vollständiger extrahierter Kurskontext wird im Moodle Cache gespeichert (TTL: 12 Stunden)
- **Ressourcen-Caching**: Einzelne Ressourcen (Seiten, Glossare, H5P) werden separat gecacht (TTL: 24 Stunden)
- **Automatische Cache-Invalidierung**: Cache-Einträge werden automatisch ungültig, wenn Kursinhalte aktualisiert werden

Dieses Caching-System reduziert die Verarbeitungszeit und verbessert die Reaktionsfähigkeit der Tutoring Machine erheblich.

## Änderungsprotokoll

### Version 4.3.0
- OpenAI-Integration vollständig auf die Responses API umgestellt; GPT-5-Modelle nutzen jetzt native Reasoning- und Verbosity-Parameter, inkl. Weitergabe der Chain of Thought zwischen Turns.
- Alle veralteten GPT-4-Varianten entfernt und die Modell-Auswahl auf GPT-5, GPT-5 Mini und GPT-5 Nano fokussiert – sowohl global als auch in Kursinstanzen, Tests und Diagnose-Tools.
- Admin- und Entwickler-Werkzeuge (CLI/Web-Tester, Direktaufruf, Konfigurationscheck) aktualisiert, um ausschließlich die neuen Endpunkte und Modelle zu verwenden.
- Lokalisierungen und Dokumentation überarbeitet, damit Empfehlungen, Provider-Bezeichnungen und Setup-Hinweise konsistent auf die GPT-5-Familie verweisen.

### Version 4.2.0
- Externe Links lassen sich pro Blockinstanz hinterlegen; der Bot hält geprüfte Domains synchron, cached Inhalte und nimmt sie (bei aktivierter Kontextweitergabe) in die Antworten auf.
- Aktivitätsbeschreibungen (z. B. Videotranskripte in Kurs-Intros) fließen zusätzlich zum eigentlichen Inhalt in den Kontext ein.

### Version 4.1.0
- Kurskontext wird nur noch nach expliziter Freigabe an den KI-Anbieter gesendet; Selektionsmasken und Diagnoseansicht folgen diesem Opt-in.
- Begrüßungstexte lassen sich global vorbelegen und pro Blockinstanz anpassen, inklusive eigener Analytics-Hinweise.

### Version 4.0.0
- Komplettes Refactoring der OpenAI-Integration: Dateivorbereitung und Vector-Store-Synchronisation laufen jetzt über eigene Service-Klassen.
- Kontextdateien werden automatisch geprüft: PDFs werden unverändert übernommen; Office-Dokumente (DOC/DOCX/PPT/PPTX/RTF/ODT/ODP) werden als Originaldateien hochgeladen und ausschließlich über `file_search` genutzt. Nicht unterstützte Formate werden übersprungen und in der Antwort vermerkt.
- Vector Stores werden automatisch neu erstellt, wenn gecachte IDs beim Anbieter nicht mehr existieren.
- Technische Grundlage für zukünftige Erweiterungen (z. B. alternative Provider, erweiterte Konvertierungen).

### Version 3.14.5
- Selektive Kontextquellen lösen jetzt automatisch einen Upload der Originaldateien zur Responses-API aus, unterstützt durch einen kursweiten Vector Store.
- Antworten werden mit Fußnoten versehen, die auf die genutzten Kursdokumente verweisen.
- Office-Dokumente werden bei Bedarf serverseitig in Text umgewandelt, damit sie von `file_search` verarbeitet werden können.

### Version 3.14.4
- Bei OpenAI-Modellen werden PDF- und Office-Dokumente jetzt als Originaldateien über die Responses-API bereitgestellt; eine serverseitige Textkonvertierung entfällt.
- Kontextdateien werden automatisch hochgeladen und nach Content-Hash gecacht; das Modell nutzt die `file_search`-Funktion, um direkt aus den Kursdateien zu antworten, ohne wiederholt dieselben Dateien senden zu müssen.
- Antworten enthalten automatisch Fußnoten mit Quellenverweisen, wenn Dateiinhalte verwendet werden.

### Version 3.14.3
- Pro Block ist nun ein eigener Assistentenname konfigurierbar; die Chat-Sprechblasen übernehmen diesen Namen automatisch.
- Der neue Block-Parameter bleibt optional – leere Felder fallen auf den globalen Namen zurück, spätere Globaleinstellungen greifen wieder für alle Blöcke ohne eigenen Namen.

### Version 3.14.2
- Blocktitel lässt sich pro Instanz setzen und dient jetzt gleichzeitig als Absendername jeder KI-Antwort im Chat.
- Das Bearbeitungsformular bietet erneut das Feld "Blocktitel" inklusive Hilfetext; leere Werte verwenden den globalen Namen.

### Version 3.14.1
- Gemeinsame `handleSendMessage`-Logik synchronisiert Chatverlauf zwischen Block- und Vollbildansicht.
- Willkommens- und Fehlermeldungen werden vor dem Rendern sanitisiert, um HTML aus Nutzereingaben zu filtern.
- Requests prüfen `sesskey`, `blockid` und `courseid` robuster und bereinigen Ladeindikatoren über `removeLoadingIndicator()`.

### Version 3.14.0
- Vollbildmodus implementiert: Modal-Container, Buttons und Styling erleichtern das Arbeiten mit langen Antworten.
- Toggle-/Close-Handler übertragen Chatverlauf zwischen Block und Modal und verwalten den `isFullscreen`-Status.

### Version 3.13.1
- Blockkonfiguration bietet einen Button zum direkten Öffnen des Analytics-Dashboards.
- Analytics-SQL nutzt konsistente `COUNT`-Aliase, damit Statistiken zuverlässig berechnet werden.

### Version 3.13.0
- Chatoberfläche wurde als Blockinhalt umgesetzt; das bisherige schwebende Widget einschließlich Größensteuerung entfällt.
- Link zur Kontext-Quellenansicht ist in die Blockeinstellungen umgezogen und ersetzt den bisherigen Footer-Link.

### Version 3.12.4
- Office-Dokumente (Word, Excel, PowerPoint) werden als Kontext extrahiert und gecacht; Unterstützung inkl. LibreOffice-Fallback.
- Neuer Cache-Typ `block_chatbot_office_cache` wird per Upgrade installiert und verwaltet Office-Inhalte getrennt vom PDF-Cache.

### Version 3.12.0
- Selektive Aktivitätsauswahl erlaubt das Markieren einzelner Kursaktivitäten pro Abschnitt als Kontextquelle.
- Der Content-Extractor filtert anhand der ausgewählten Aktivitäten und pflegt `specific_activities` im Cache.
- Die Kontextdiagnose listet ausschließlich die aktivierten Aktivitäten und Sektionen auf.

### Version 3.11.0
- Umfassendes Code-Refactoring für bessere Wartbarkeit
- Entfernung veralteter Codepfade und Fallbacks
- Optimierung der API-Client-Implementierung
- Verbesserte Dokumentation im Quellcode
- Entfernung überflüssiger Leerzeichen und Formatierungskorrekturen
- Konsolidierung der Providers und Modelle auf die aktuellen Standards
- Verbesserung der Fehlerbehandlung in der gesamten Codebasis

### Version 3.10.4
- Verbesserte Benutzeroberfläche für Analytics- und Kontextquellen-Ansichten
- Öffnen des Analysendashboards in einem neuen Fenster
- Hinzufügen eines "Fenster schließen"-Buttons zum Analysendashboard
- Konsistente UI für alle Popup-Fenster (Analysen und Kontextquellen)
- Verbesserte Darstellung der PDF-Extraktionsoptionen
- Automatische Erkennung und Anzeige verfügbarer PDF-Extraktionsmethoden

### Version 3.10.3
- Implementierung einer server-seitigen PDF-Textextraktion
- Unterstützung für pdftotext (poppler-utils) zur PDF-Extraktion
- Fallback auf PHP-basierte PDF-Bibliotheken wenn verfügbar
- Automatische Erkennung verfügbarer PDF-Extraktionsmethoden
- Verbesserte Fehlerbehandlung und Logging bei der PDF-Verarbeitung
- Detaillierte Installationsanleitung für PDF-Extraktionstools

### Version 3.10.2
- Sicherheitsverbesserungen und Härtung gegen XSS-Angriffe
- Fehlerbehandlung optimiert mit detaillierteren Fehlermeldungen

### Version 3.10.1
- Verbesserte Kontext-Anzeige in simple_context.php
- Anpassungen zur Unterstützung von PHP 8.2-8.4

### Version 3.10.0
- Vollständige Implementierung des Kontext-Systems mit Cache-Unterstützung
- Anzeige des vollständigen API-Kontexts in der Diagnoseansicht
- Einstellungsoptionen zur Konfiguration der Kontextquellen

### Version 3.8.0
- Implementierung von Lehr-Analysen für anonymisierte Nutzereingaben
- Neue Funktionen zur Analyse häufig gestellter Fragen
- Dashboard für Kursleiter mit Statistiken und Trendanalysen
- Konfigurierbare Aufbewahrungsfristen für Analysedaten
- Automatische Bereinigung alter Daten durch geplante Tasks
- Datenschutzhinweise für Lernende bei aktivierten Analysen

### Version 3.7.2
- Integration des API-Test-Tools in die Administrationsschnittstelle
- Links zum API-Test-Tool direkt in den Plugin-Einstellungen
- Verbesserte Benutzerfreundlichkeit für Administratoren bei der Fehlerdiagnose
- Entfernung der Entwicklungsmodus-Beschränkung für das API-Test-Tool

### Version 3.7.1
- Vollständiges Refactoring des API-Client-Codes
- Verbesserte Fehlerbehandlung und detaillierte Fehlerdiagnose
- Robusteres HTTP-Request-Management mit automatischen Wiederholungsversuchen
- Optimierte Logging-Funktionalität für bessere Fehlerbehebung
- Unterstützung für JSON-Response-Format bei allen unterstützten Modellen
- Umfassende Dokumentation im Code
- Neue API-Test-Tools für Kommandozeile und Web-Interface

### Version 3.7.0
- Multi-Provider-API mit Unterstützung für OpenAI und Google Gemini
- Vereinfachte API-Schnittstelle mit einheitlicher Implementierung
- Unterstützung für die neuesten Modelle von OpenAI und Google
- Verbesserte Fehlerbehandlung für API-Anfragen
- Leistungsoptimierungen und reduzierter Ressourcenverbrauch
- Automatische API-Key-Verwaltung für verschiedene Provider

### Version 3.3.0
- Umfassende Verbesserungen am Caching-System
- Neuer zentraler Cache-Manager für alle Inhaltstypen
- Dedizierte Cache-Stores für verschiedene Inhaltstypen (Seiten, Glossare, H5P)
- Automatische Cache-Invalidierung durch Event-Observer
- Performance-Optimierungen bei der Inhaltsextraktion
- Verbesserte Metadaten für Cache-Einträge

### Version 3.2.0
- Verbesserte Sicherheitsmaßnahmen implementiert
- CSRF-Schutz durch Session-Key-Validierung
- Eingabevalidierung und -bereinigung verbessert
- Rate-Limiting-Mechanismus implementiert (max. 30 Anfragen pro Stunde)
- Fehlerbehandlung und Protokollierung optimiert
- Bereinigung von HTML-Inhalten im Client zur Vermeidung von XSS-Angriffen

### Version 3.1.0
- Vollständiges Code-Refactoring mit verbesserter Architektur
- Einführung von dedizierten Klassen für API-Client, Inhaltsextraktion und Nachrichtenverarbeitung
- Verbesserte Fehlerbehandlung und Protokollierung
- Modernisierte Codestruktur für bessere Wartbarkeit

### Version 3.0.0
- Komplette Überarbeitung der Codestruktur
- Einführung von OOP-Prinzipien
- Verbesserte Fehlerbehandlung

### Version 2.6.0
- Unterstützung für PDF-Inhaltsextraktion hinzugefügt
- Cache-System für PDF-Inhalte implementiert
- Unterstützung für H5P-Aktivitäten verbessert

## Lizenz

GNU GPL v3 oder später
