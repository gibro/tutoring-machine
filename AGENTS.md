# Repository Guidelines

## Project Structure & Module Organization
The plugin lives under `blocks/tutoring_machine` in a Moodle tree. `classes/` holds analytics, caching, and API clients that Moodle auto-loads; OpenAI-spezifische Services wie Datei- und Vector-Store-Manager (inklusive Text-Fallback für DOCX/PPTX) liegen unter `classes/openai/`. `classes/task/` enthält geplante Bereinigungsjobs. UI lebt in `templates/` Mustache-Dateien, während `lang/` Sprachpakete wie `en`, `de` und `de_du` bereitstellt. Datenbank-Wiring (Capabilities, Cache-Definitionen, Tasks) liegt unter `db/`. Statische Assets liegen in `pix/`, und Admin-Tools (Analytics-Dashboard, API-Diagnostik) befinden sich im Projektroot neben `settings.php` und `view_context.php`.

## Build, Test, and Development Commands
After copying into a Moodle instance run `php admin/cli/upgrade.php` to install or upgrade the block. Initialise the PHPUnit environment once with `php admin/tool/phpunit/cli/init.php`, then execute automated checks via `vendor/bin/phpunit --testsuite block_tutoring_machine`. Use `php -l path/to/file.php` for quick syntax linting before committing. Validate provider connectivity from the Moodle root using `php blocks/tutoring_machine/api_test.php all`; the web tester is available at `/blocks/tutoring_machine/api_test_web.php` for administrator smoke tests. When OpenAI is active, ensure `$CFG->tempdir` is writable because Kontext-Dateien werden dort temporär abgelegt, ehe sie über die Responses-API (inkl. Vector Store) hochgeladen werden.

## Coding Style & Naming Conventions
Follow the Moodle PHP guidelines: 4-space indentation, Yoda conditions for comparisons, and full docblocks on public members. Namespace classes with the `block_tutoring_machine_` prefix and camelCase methods. Keep Mustache templates logic-free, passing presentation data through renderer classes. Add or update strings in every locale under `lang/{lang}/block_tutoring_machine.php` and mirror the same string identifiers.

## Testing Guidelines
House new PHPUnit coverage in a `tests/` directory (create when needed) using filenames like `block_tutoring_machine_analytics_test.php`. Extend `advanced_testcase`, prefix methods with `test_`, and call `$this->resetAfterTest()` when touching Moodle state. Use Moodle data generators over manual inserts. Run the CLI and web API testers after changing provider logic or credentials, and inspect generated logs under `logs/` for errors.

## Commit & Pull Request Guidelines
Recent commits are short, imperative summaries (often German), e.g. `Verbessert Analytics-Logging`. Keep each commit focused on a functional slice and mention configuration or schema adjustments in the body. Pull requests should outline behaviour changes, link Moodle tracker or GitHub issues, include reproduction and testing notes, and attach UI screenshots for template or styling updates.
