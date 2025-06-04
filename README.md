# Moodle Chatbot Block

Ein Block für Moodle, der einen KI-basierten Chatbot zur Unterstützung von Lernenden bereitstellt. Der Chatbot kann Fragen zum Kursinhalt basierend auf verschiedenen Kursressourcen beantworten.

## Installation

1. Entpacken Sie das Plugin in das `/blocks/chatbot`-Verzeichnis Ihrer Moodle-Installation.
2. Navigieren Sie zu "Website-Administration" > "Mitteilungen" > "Benachrichtigungen aktualisieren".
3. Folgen Sie dem Installationsprozess und bestätigen Sie die Installation.

## Konfiguration

Das Plugin kann über die Blockeinstellungen in jeder Kursseite konfiguriert werden:

1. Schalten Sie den Bearbeitungsmodus ein.
2. Fügen Sie den "Chatbot"-Block hinzu.
3. Klicken Sie auf das Zahnrad-Symbol und wählen Sie "Block konfigurieren".
4. Passen Sie die Einstellungen nach Bedarf an:
   - **Chatbot-Anweisungen**: Geben Sie spezielle Anweisungen für den Chatbot ein
   - **Hauptfarbe**: Wählen Sie die Hauptfarbe für die Benutzeroberfläche
   - **Kontextquellen**: Wählen Sie, welche Kursressourcen als Kontext verwendet werden sollen:
     - Textseiten
     - Glossare
     - H5P-Aktivitäten
     - PDF-Dokumente
     - Internetsuche (für Fragen, die nicht durch Kursinhalte beantwortet werden können)

## Widget-Logo anpassen

Das Chat-Widget verwendet ein Standard-Logo, das Sie folgendermaßen anpassen können:

1. Erstellen Sie ein eigenes Logo mit folgenden Eigenschaften:
   - Größe: 140 x 108 Pixel
   - Format: PNG mit transparentem Hintergrund empfohlen
   - Dateiname: `widget-logo.png`

2. Ersetzen Sie die Datei im Plugin-Verzeichnis:
   ```
   /blocks/chatbot/pix/widget-logo.png
   ```

3. Löschen Sie den Browser-Cache, damit das neue Logo angezeigt wird.

**Hinweis:** Sie müssen direkten Zugriff auf das Dateisystem des Servers haben (z.B. per FTP oder SSH), um das Logo zu ersetzen.

## Globale Einstellungen

Globale Einstellungen für den Chatbot können unter "Website-Administration" > "Plugins" > "Blöcke" > "Chatbot" konfiguriert werden:

- **Assistantname**: Der Name, der im Chat-Interface angezeigt wird
- **OpenAI API-Schlüssel**: Ihr OpenAI API-Schlüssel für die Verbindung mit OpenAI-Modellen
- **Google API-Schlüssel**: Ihr Google API-Schlüssel für die Verbindung mit Gemini-Modellen
- **Standard-Modell**: Das zu verwendende Standard-KI-Modell im Format "provider:model"
  - Unterstützte OpenAI-Modelle: gpt-4o, gpt-4-turbo, gpt-4o-mini und weitere
  - Unterstützte Google-Modelle: gemini-1.5-pro, gemini-1.5-flash, gemini-pro

## Unterstützte Kursressourcen

Der Chatbot kann folgende Kursressourcen als Kontext verwenden:

- Textseiten (Moodle Page-Aktivitäten)
- Glossare und deren Einträge
- H5P-Aktivitäten (wenn installiert)
- PDF-Dokumente (mit automatischer Textextraktion)

## Sicherheitsfunktionen

Der Chatbot implementiert folgende Sicherheitsfunktionen:

- **Eingabevalidierung**: Alle Benutzereingaben werden geprüft und bereinigt
- **CSRF-Schutz**: Verhinderung von Cross-Site Request Forgery durch Session-Key-Validierung
- **Rate-Limiting**: Begrenzung auf 30 Anfragen pro Stunde pro Benutzer
- **XSS-Schutz**: Bereinigung von HTML-Inhalten zur Vermeidung von Cross-Site-Scripting
- **Zugriffskontrolle**: Prüfung der Benutzerberechtigungen

## Technische Anforderungen

- PHP 8.2 oder höher
- Moodle 4.1 oder höher
- OpenAI API-Schlüssel oder Google Gemini API-Schlüssel
- Optional für PDF-Verarbeitung:
  - pdftotext (Poppler-Utils)
  - Smalot/PdfParser PHP-Bibliothek (über Composer installierbar)
  - PHP Imagick-Erweiterung

## API-Test-Tools

Zur Überprüfung der API-Verbindungen stehen zwei Test-Tools zur Verfügung:

### Kommandozeilen-Test

Der Kommandozeilen-Test kann über die Moodle-Wurzel ausgeführt werden:

```
php blocks/chatbot/api_test.php [provider]
```

Wobei `[provider]` einer der folgenden Werte sein kann:
- `openai` - Test nur für OpenAI API
- `google` - Test nur für Google Gemini API
- `all` - Test für alle konfigurierten APIs (Standard)

Die Ergebnisse werden sowohl in der Konsole ausgegeben als auch in einer Logdatei im Verzeichnis `blocks/chatbot/logs/` gespeichert.

### Web-basierter Test

Der web-basierte Test kann über folgende URL aufgerufen werden:

```
https://ihre-moodle-url/blocks/chatbot/api_test_web.php
```

Dieser Test bietet eine benutzerfreundliche Oberfläche zur Überprüfung der API-Verbindungen und ist nur für Administratoren im Entwicklungsmodus verfügbar.

## Unterstützte Sprachen

- Englisch
- Deutsch
- Deutsch Du-Form

## Caching-System

Der Chatbot nutzt ein umfassendes Caching-System, um die Leistung zu verbessern und die Anzahl der API-Aufrufe zu reduzieren:

- **PDF-Caching**: Extrahierte PDF-Inhalte werden in der Datenbank gespeichert (TTL: 1 Woche)
- **Kursinhalt-Caching**: Vollständiger extrahierter Kurskontext wird im Moodle Cache gespeichert (TTL: 12 Stunden)
- **Ressourcen-Caching**: Einzelne Ressourcen (Seiten, Glossare, H5P) werden separat gecacht (TTL: 24 Stunden)
- **Automatische Cache-Invalidierung**: Cache-Einträge werden automatisch ungültig, wenn Kursinhalte aktualisiert werden

Dieses Caching-System reduziert die Verarbeitungszeit und verbessert die Reaktionsfähigkeit des Chatbots erheblich.

## Änderungsprotokoll

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