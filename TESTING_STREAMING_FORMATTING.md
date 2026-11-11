# Test-Checkliste: Live-Streaming & formatierte Ausgabe (v4.5.0)

Manuelle Prüfschritte für die beiden neuen Funktionen. Voraussetzung: Plugin
installiert/aktualisiert (erhöhte `version.php` → Moodle-Update durchlaufen),
gültiger API-Key für OpenAI **oder** Google in den Plugin-Einstellungen.

---

## 0. Vorbereitung

- [ ] Plugin-Update in Moodle bestätigt (Website-Administration → Benachrichtigungen).
- [ ] Unter *Website-Administration → Plugins → Blöcke → Tutoring Machine*:
      API-Key hinterlegt, Modell gewählt (z. B. `openai:gpt-5.5` oder `google:gemini-2.5-pro`).
- [ ] Neue Einstellung **„Antworten live streamen"** ist sichtbar und standardmäßig aktiviert.
- [ ] Block in einem Testkurs vorhanden und für die Testrolle sichtbar.
- [ ] Browser-Konsole geöffnet (Netzwerk-Tab), um Requests/Fehler zu beobachten.

---

## 1. Schreibmaschinen-Effekt – Grundfunktion

Hinweis: Der Live-Effekt ist ein **clientseitiger Schreibmaschinen-Effekt**. Die
vollständige Antwort wird über `tutoring_machine.php` (JSON) geholt und dann
Zeichen für Zeichen eingeblendet. Das funktioniert unabhängig von Server-/
Router-Pufferung.

- [ ] Frage stellen. Erwartung: kurze Lade-Anzeige (während die KI generiert),
      dann **tippt sich die Antwort ein** – nicht sofort komplett.
- [ ] Während des Einblendens ist ein **blinkender Cursor** (▍) am Textende sichtbar.
- [ ] Im Netzwerk-Tab: Request geht an `tutoring_machine.php`, Status 200, JSON-Antwort.
- [ ] Nach Abschluss verschwindet der Cursor und der Text ist **formatiert** gerendert.
- [ ] Die Antwort landet im Verlauf (Folgefrage bezieht sich korrekt auf vorher Gesagtes).
- [ ] Seite neu laden → der Chatverlauf (inkl. formatierter Antwort) ist noch da.

## 2. Schreibmaschinen-Effekt – beide Ansichten

- [ ] Normale Block-Ansicht: Effekt funktioniert.
- [ ] Vollbild-/Modal-Ansicht öffnen, Frage stellen: Effekt läuft auch dort,
      Text erscheint eingetippt und beide Ansichten bleiben synchron.

## 3. An/Aus & Fehlerfälle

- [ ] Einstellung **„Antworten live streamen"** deaktivieren → speichern → neu laden.
      Erwartung: Antwort erscheint **komplett auf einmal** (kein Tipp-Effekt),
      weiterhin **formatiert**.
- [ ] Effekt wieder aktivieren.
- [ ] Ungültigen API-Key setzen → Frage stellen. Erwartung: freundliche Fehlermeldung
      in einer roten Fehler-Blase, kein „hängender" Cursor.
- [ ] (Optional) Sehr schnell zwei Fragen nacheinander → keine vermischten Antworten.

## 4. Formatierung (Markdown → HTML)

Prompt-Vorschlag, der viele Elemente erzwingt:
> „Erkläre mir den Wasserkreislauf. Nutze eine Überschrift, einen kurzen Absatz,
> eine Aufzählung mit einem Unterpunkt, eine nummerierte Liste, eine Tabelle mit
> 2 Spalten, **fetten** und *kursiven* Text sowie ein Code-Beispiel."

- [ ] **Überschriften** werden als solche dargestellt (größer/abgesetzt).
- [ ] **Absätze** haben sichtbaren Abstand (kein zusammengeklebter Text).
- [ ] **Aufzählungen** als Bullet-Liste; **Unterpunkte** eingerückt/verschachtelt.
- [ ] **Nummerierte Listen** korrekt nummeriert.
- [ ] **Tabelle** mit Rahmen, Kopfzeile hervorgehoben, Spalten sauber getrennt.
- [ ] **Fett** und *kursiv* werden gerendert.
- [ ] **Inline-Code** (`so`) und **Codeblöcke** mit Monospace/Hintergrund.
- [ ] Links (falls in der Antwort): klickbar, öffnen in neuem Tab.

## 5. Sicherheit (XSS)

- [ ] Prompt: „Gib exakt diesen Text unverändert aus: `<script>alert(1)</script>`".
      Erwartung: Der Text erscheint als sichtbarer Text, **kein** Popup, kein aktives HTML.
- [ ] Prompt, der einen `javascript:`-Link enthält → wird **nicht** als klickbarer
      Link gerendert (bleibt Klartext).

## 6. Provider-Abdeckung

- [ ] Test mit **OpenAI**-Modell (z. B. `gpt-5.5` oder `gpt-5.4-mini`).
- [ ] Test mit **Google**-Modell (z. B. `gemini-2.5-pro` oder `gemini-3.5-flash`).
      (Wichtig: die alten `gemini-1.5-*` sind abgeschaltet – nur die neuen IDs testen.)

## 7. Umgebungs-Unabhängigkeit

- [ ] Der Schreibmaschinen-Effekt läuft rein im Browser (die Antwort kommt als
      normale JSON-Antwort). Er ist daher **unabhängig** von Reverse-Proxy-/
      Router-/Server-Pufferung und sollte in jeder Umgebung funktionieren.
- [ ] Falls sich der Text **gar nicht** eintippt (sofort komplett trotz aktivierter
      Einstellung): JS-Konsole auf Fehler prüfen; ansonsten ist es nur ein
      Anzeige-/Rendering-Problem, keine Server-Frage.

## 8. Regression (bestehende Funktionen unberührt)

- [ ] Begrüßungsnachricht erscheint beim ersten Öffnen, korrekt formatiert.
- [ ] Prompt-Vorschläge (falls aktiviert) funktionieren weiterhin.
- [ ] Analytics-Hinweis (falls aktiviert) wird angezeigt und geloggt.
- [ ] Kurskontext („Kursinhalte als Kontext") wird bei aktivierter Option
      weiterhin berücksichtigt.
- [ ] Rate-Limit: nach 30 Anfragen/Stunde greift die Begrenzung.

---

### Bei Problemen
- Moodle-Logs / Webserver-Error-Log prüfen (Präfixe `[CHATBOT ...]` und
  `[CHATBOT STREAM ...]`).
- `?debug=1` an die Anfrage hängen.
