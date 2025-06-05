<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Simple context viewer for Chatbot block.
 *
 * @package    block_chatbo
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include Moodle config
require_once('../../config.php');
require_once($CFG->libdir . '/weblib.php'); // Für html_to_tex
require_once($CFG->dirroot . '/lib/filelib.php'); // Für get_file_storage
require_once($CFG->dirroot . '/lib/accesslib.php'); // Für context_module

// Security parameters
$courseid = required_param('courseid', PARAM_INT);
$blockid = optional_param('blockid', 0, PARAM_INT);

// Check course exists
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Check permissions
require_login($course);

// Get block configuration if blockid is provided
$block_config = null;
if ($blockid) {
    $block_record = $DB->get_record('block_instances', array('id' => $blockid), '*');
    if ($block_record && $block_record->blockname == 'chatbot') {
        $block_config = unserialize(base64_decode($block_record->configdata));
    }
}

// Basic page setup
$PAGE->set_url('/blocks/chatbot/simple_context.php', array('courseid' => $courseid, 'blockid' => $blockid));
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'block_chatbot') . ': ' . $course->fullname);
$PAGE->set_title(get_string('contextsources', 'block_chatbot'));

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('contextsources', 'block_chatbot'));

// Display the active context sources
require_once($CFG->dirroot . '/blocks/chatbot/classes/content_extractor.php');

// Kontextquellen-Optionen auflisten
$sources = array(
    'use_textpages' => get_string('use_textpages', 'block_chatbot'),
    'use_glossaries' => get_string('use_glossaries', 'block_chatbot'),
    'use_h5p' => get_string('use_h5p', 'block_chatbot'),
    'use_pdfs' => get_string('use_pdfs', 'block_chatbot') . ' (PDF-Dokumente)',
    'use_forums' => get_string('use_forums', 'block_chatbot'),
    'use_quizzes' => get_string('use_quizzes', 'block_chatbot'),
    'use_books' => get_string('use_books', 'block_chatbot'),
    'use_assignments' => get_string('use_assignments', 'block_chatbot'),
    'use_labels' => get_string('use_labels', 'block_chatbot'),
    'use_urls' => get_string('use_urls', 'block_chatbot'),
    'use_lessons' => get_string('use_lessons', 'block_chatbot'),
    'use_internet' => get_string('use_internet', 'block_chatbot')
);

// Card für die aktivierten Quellen
echo html_writer::start_div('card mb-4');
echo html_writer::div('Aktivierte Kontextquellen', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::start_tag('ul', array('class' => 'list-group'));
foreach ($sources as $key => $label) {
    $enabled = !$block_config || (isset($block_config->{$key}) && $block_config->{$key});
    $status_class = $enabled ? 'success' : 'light';
    echo html_writer::tag('li',
        $label,
        array('class' => "list-group-item list-group-item-$status_class")
    );
}
echo html_writer::end_tag('ul');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Inhaltliche Übersicht über verfügbare Materialien im Kurs
echo html_writer::start_div('card mb-4');
echo html_writer::div('Verfügbare Kursinhalte', 'card-header');
echo html_writer::start_div('card-body');

// Frage die tatsächlichen Kursmodule ab, nach Typ
$available_modules = array();
$module_count = array();

// Modinfo enthält alle Modulinstanzen im Kurs
$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();

// Zähle die Module nach Typ
foreach ($cms as $cm) {
    if (!$cm->uservisible) {
        continue;
    }

    $modname = $cm->modname;
    if (!isset($module_count[$modname])) {
        $module_count[$modname] = 0;
        $available_modules[$modname] = array();
    }

    $module_count[$modname]++;
    if (count($available_modules[$modname]) < 5) { // Maximal 5 Beispiele pro Typ
        $available_modules[$modname][] = $cm->name;
    }
}

// Zeige nur die erkannten/verfügbaren Inhalte an (ohne Häkchen oder Kreuze am Anfang)
if (empty($module_count)) {
    echo 'Keine sichtbaren Aktivitäten oder Materialien im Kurs gefunden.';
} else {
    echo html_writer::start_tag('ul', array('class' => 'list-group'));

    foreach ($module_count as $modname => $count) {
        // Aktivitäten werden immer als verfügbar betrachte
        $status_class = 'success';

        $examples = '';
        if (!empty($available_modules[$modname])) {
            $examples = ' (z.B. "' . implode('", "', $available_modules[$modname]) . '")';
            if (count($available_modules[$modname]) < $count) {
                $examples .= ' und ' . ($count - count($available_modules[$modname])) . ' weitere';
            }
        }

        echo html_writer::tag('li',
            $count . ' ' . ucfirst($modname) . '-' . ($count > 1 ? 'Aktivitäten' : 'Aktivität') . $examples,
            array('class' => "list-group-item list-group-item-$status_class")
        );
    }

    echo html_writer::end_tag('ul');
}

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Vollständiger Kontext, der an die API übertragen wird
echo html_writer::start_div('card mb-4');
echo html_writer::div('Vollständiger API-Kontext', 'card-header');
echo html_writer::start_div('card-body');

// Zeige den vollständigen Kontext an, der an die API übertragen wird
require_once($CFG->dirroot . '/blocks/chatbot/classes/content_extractor.php');

try {
    // Extrahiere den Kontext, wie es der Chatbot tun würde
    $extractor = new block_chatbot_content_extractor($course->id, $block_config);
    $full_context = $extractor->get_context();

    echo html_writer::tag('p', 'Dies ist der vollständige Kontext, der an die API übertragen wird:');
    echo html_writer::tag('div',
        html_writer::tag('pre', s($full_context),
        array('class' => 'border p-2 bg-light', 'style' => 'white-space: pre-wrap; max-height: 400px; overflow-y: auto;'))
    );
} catch (Exception $e) {
    echo html_writer::tag('div', 'Fehler beim Laden des Kontexts: ' . $e->getMessage(), array('class' => 'alert alert-danger'));
}

echo html_writer::end_div(); // card-body für den Vollständigen Kontex
echo html_writer::end_div(); // card für den Vollständigen Kontex

// Serverseitige PDF-Extraktion Anleitung
echo html_writer::start_div('card mb-4');
echo html_writer::div('PDF-Extraktion (Neue Server-seitige Lösung implementiert)', 'card-header');
echo html_writer::start_div('card-body');

echo html_writer::tag('p', '<strong class="text-success">✓ Die server-seitige PDF-Extraktion wurde implementiert!</strong> Das Plugin versucht jetzt automatisch, eine der folgenden Methoden zu verwenden:');

echo html_writer::start_tag('ol');

echo html_writer::tag('li', html_writer::tag('strong', 'pdftotext (empfohlen)') .
    '<br>Ein Kommandozeilentool, das Teil des Poppler-Pakets ist:' .
    '<ul>' .
    '<li>Installation unter Ubuntu/Debian: <code>sudo apt-get install poppler-utils</code></li>' .
    '<li>Installation unter CentOS/RHEL: <code>sudo yum install poppler-utils</code></li>' .
    '<li>Installation unter macOS: <code>brew install poppler</code></li>' .
    '</ul>'
);

echo html_writer::tag('li', html_writer::tag('strong', 'PHP-Bibliotheken (Fallback)') .
    '<br>Wird automatisch verwendet, wenn pdftotext nicht verfügbar ist:' .
    '<ul>' .
    '<li><a href="https://github.com/smalot/pdfparser" target="_blank">Smalot PDF Parser</a>: <code>composer require smalot/pdfparser</code></li>' .
    '<li><a href="https://github.com/spatie/pdf-to-text" target="_blank">Spatie PDF to Text</a>: <code>composer require spatie/pdf-to-text</code></li>' .
    '<li>PDFlib PHP-Erweiterung (wenn verfügbar)</li>' .
    '</ul>'
);

echo html_writer::end_tag('ol');

echo html_writer::tag('h4', 'Installationsprüfung');

// Prüfen, ob pdftotext verfügbar is
$pdftotext_available = false;
$pdftotext_path = '';
$pdf_libraries = [];

if (function_exists('exec')) {
    @exec('which pdftotext', $output_array, $return_var);
    if ($return_var === 0 && !empty($output_array[0])) {
        $pdftotext_available = true;
        $pdftotext_path = $output_array[0];
    } else {
        // Prüfe allgemeine Pfade
        $possible_paths = [
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
            '/opt/homebrew/bin/pdftotext'
        ];

        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                $pdftotext_available = true;
                $pdftotext_path = $path;
                break;
            }
        }
    }
}

// Prüfe PHP-Bibliotheken
if (class_exists('\Smalot\PdfParser\Parser')) {
    $pdf_libraries[] = 'Smalot\PdfParser';
}
if (class_exists('\Spatie\PdfToText\Pdf')) {
    $pdf_libraries[] = 'Spatie\PdfToText';
}
if (extension_loaded('pdflib')) {
    $pdf_libraries[] = 'PDFlib Extension';
}

// Zeige Status an
echo html_writer::start_div('alert ' . ($pdftotext_available ? 'alert-success' : 'alert-warning'));

if ($pdftotext_available) {
    echo html_writer::tag('p', '<strong>✓ pdftotext gefunden:</strong> ' . $pdftotext_path .
        '<br>Dies ist die empfohlene und schnellste Methode zur PDF-Textextraktion.');
} else {
    echo html_writer::tag('p', '<strong>✗ pdftotext nicht gefunden.</strong> Die Extraktion wird weniger zuverlässig sein.' .
        '<br>Bitte installieren Sie poppler-utils für optimale Ergebnisse.');
}

// Zeige PHP-Bibliotheken
if (!empty($pdf_libraries)) {
    echo html_writer::tag('p', '<strong>Verfügbare PHP-Bibliotheken:</strong> ' . implode(', ', $pdf_libraries));
} else {
    echo html_writer::tag('p', '<strong>Keine PHP-PDF-Bibliotheken gefunden.</strong> ' .
        'Ohne pdftotext oder eine kompatible PHP-Bibliothek ist die PDF-Extraktion nicht möglich.');
}

echo html_writer::end_div(); // alert div

echo html_writer::tag('p', 'Das Plugin versucht nun automatisch, PDF-Inhalte mit den verfügbaren Methoden zu extrahieren, in dieser Prioritätsreihenfolge:');

echo html_writer::start_tag('ol');
echo html_writer::tag('li', 'pdftotext (wenn verfügbar) - schnellste und zuverlässigste Methode');
echo html_writer::tag('li', 'PHP-Bibliotheken als Fallback (wenn eine der unterstützten Bibliotheken installiert ist)');
echo html_writer::tag('li', 'Fehlermeldung, wenn keine Methode verfügbar ist');
echo html_writer::end_tag('ol');

echo html_writer::tag('p', 'Die extrahierten PDF-Inhalte werden im Cache gespeichert, um die Performance zu verbessern.');
echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// Keine weiteren Inhaltsbeispiele nach Typ
// Das ist absichtlich leer, da dieser Abschnitt entfernt wurde

// JavaScript für die Accordion-Funktionalitä
$script = "
require(['jquery', 'bootstrap'], function($) {
    $('.collapse').on('show.bs.collapse', function() {
        $(this).prev('.card-header').addClass('bg-light');
    }).on('hide.bs.collapse', function() {
        $(this).prev('.card-header').removeClass('bg-light');
    });
});
";

$PAGE->requires->js_amd_inline($script);

// Schließen-Button
echo html_writer::start_div('text-center mt-3');
echo html_writer::tag('button', 'Fenster schließen', array(
    'class' => 'btn btn-primary',
    'onclick' => 'window.close();'
));
echo html_writer::end_div();

echo $OUTPUT->footer();