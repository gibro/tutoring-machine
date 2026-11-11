# OKF-Wissensbasis hochladen

Diese Anleitung beschreibt, was du im Block **Tutoring Machine** unter
*Block konfigurieren → Wissensbasis (OKF)* in das Textfeld **Wissensbasis-Inhalt
(OKF)** einfügst und wie du diesen Inhalt aus einem
Open-Knowledge-Format-(OKF-)Bundle erzeugst.

## Was eingefügt wird

Es gibt **keinen Datei-Upload** – der Block hat stattdessen ein Textfeld, in das
du den Inhalt **direkt hineinkopierst**. Kopiere dabei **nicht** die vielen
einzelnen rohen OKF-Konzeptdateien (unter `grundlagen/`, `inhalte/`,
`glossar/` usw.), sondern den Inhalt des daraus **vorgefertigten
Kontext-Artefakts**:

| Datei (Inhalt zum Einfügen) | Pflicht? | Verwendung |
|-------|----------|------------|
| `moodle-bot-context.md` | **Ja** | Alle kuratierten Konzepte + Inhaltsverzeichnis, wird als Kontext an die KI übergeben. |
| `moodle-bot-system-prompt.md` | Optional | Support-Bot-Regeln. Falls du diesen Inhalt zusätzlich einfügen willst, hänge ihn vor den Kontext von `moodle-bot-context.md` an (z. B. mit einer eigenen Überschrift). Ohne diesen Inhalt nutzt der Block seinen eigenen Metaprompt. |

Füge **nur den Inhalt dieser ein bis zwei Dateien** ein, nicht das ganze
`moodle-wissensbasis/`-Verzeichnis.

`moodle-bot-concepts.jsonl` (das dritte Artefakt aus dem OKF-Repo) wird von
diesem Block **nicht** verwendet – es ist nur für ein separates
Retrieval-Setup relevant, das der Block aktuell nicht anbietet.

## Wie du den Inhalt erzeugst

Die Artefakte kommen aus dem separaten `moodle-wissen_okf`-Repository, in dem
die kuratierten Konzepte gepflegt werden. Kurzfassung:

```bash
cd moodle-wissen_okf
bash    moodle-wissensbasis/validate.sh        # 1. Konformität prüfen
python3 moodle-wissensbasis/make_viz.py        # 2. Graph aktualisieren (optional)
python3 moodle-wissensbasis/build_context.py   # 3. Bot-Kontext erzeugen
```

Schritt 3 schreibt `moodle-bot-context.md` (und `moodle-bot-system-prompt.md`)
im Wurzelverzeichnis des OKF-Repos. Öffne diese Datei, kopiere ihren gesamten
Inhalt und füge ihn im Block in das Textfeld ein.

Ausführliche Erklärungen zu Konzepten anlegen/ändern, zur Bundle-Struktur und
zu den beiden Kontext-Varianten (ganzer Kontext vs. Retrieval) stehen in:

* `moodle-wissen_okf/README.md` – wie neue Konzepte angelegt werden
* `moodle-wissen_okf/moodle-bot-integration.md` – wie die Artefakte als
  Bot-Kontext gedacht sind

## Aktualisieren

Ändert sich die Wissensbasis, erzeugst du `moodle-bot-context.md` wie oben neu
und ersetzt den Inhalt im Textfeld im Block durch den neuen Text. Es gibt
keinen automatischen Abgleich – das Aktualisieren ist bewusst manuell.

## Speicherort/Zugriff wählen

Im Block gibt es dafür zusätzlich die Einstellung **Speicherort/Zugriff**:

* **Direkt im Prompt** (Standard, empfohlen): Der eingefügte Text wird bei
  jeder Anfrage in den Kontext eingefügt. Funktioniert mit jedem
  konfigurierten Modell (OpenAI und Google) ohne Zusatzkosten für einen
  Vektor-Store.
* **OpenAI Vektor-Store**: Der eingefügte Text wird als Datei einmalig zu
  OpenAI hochgeladen und dort über Datei-Suche (file_search) durchsucht. Nur
  nutzbar, wenn als Modell ein OpenAI-Modell ausgewählt ist; bei
  Google-Modellen wird automatisch auf „Direkt im Prompt" zurückgefallen.
