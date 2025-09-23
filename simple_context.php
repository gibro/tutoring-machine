<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Simple context viewer for Tutoring Machine block.
 *
 * @package    block_tutoring_machine
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
    if ($block_record && $block_record->blockname == 'tutoring_machine') {
        $block_config = unserialize(base64_decode($block_record->configdata));
    }
}

if (!$block_config) {
    $block_config = new stdClass();
}

$globalincludecontext = get_config('block_tutoring_machine', 'default_include_context');
$include_context = !empty($globalincludecontext);
if (isset($block_config->include_context)) {
    $include_context = (bool)$block_config->include_context;
}

if ($block_config) {
    $block_config->blockid = $blockid;
}

// Basic page setup
$PAGE->set_url('/blocks/tutoring_machine/simple_context.php', array('courseid' => $courseid, 'blockid' => $blockid));
$PAGE->set_context($context);
$PAGE->set_heading(get_string('pluginname', 'block_tutoring_machine') . ': ' . $course->fullname);
$PAGE->set_title(get_string('contextsources', 'block_tutoring_machine'));

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('contextsources', 'block_tutoring_machine'));

// Display the active context sources
require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/content_extractor.php');
require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/link_manager.php');

// Kursmodule laden (für die selektive Aktivitätsauswahl)
$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();

// Zeige Internetsuche-Einstellung
echo html_writer::start_div('card mb-4');
echo html_writer::div('Kontexteinstellungen', 'card-header');
echo html_writer::start_div('card-body');

// Internetsuche
$internet_enabled = !empty($block_config->use_internet);
echo html_writer::start_tag('ul', array('class' => 'list-group'));
echo html_writer::tag('li',
    'Internetsuche: ' . ($internet_enabled ? 'Aktiviert' : 'Deaktiviert'),
    array('class' => 'list-group-item list-group-item-' . ($internet_enabled ? 'success' : 'light'))
);
echo html_writer::tag('li',
    'Kontextweitergabe: ' . ($include_context ? 'Aktiviert' : 'Deaktiviert'),
    array('class' => 'list-group-item list-group-item-' . ($include_context ? 'success' : 'warning'))
);
echo html_writer::end_tag('ul');

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

if ($include_context) {
    // Selektive Aktivitätsauswahl
    $specific_activities_enabled = !empty($block_config->use_specific_activities);

    if ($specific_activities_enabled) {
        echo html_writer::start_div('card mb-4');
        echo html_writer::div('Ausgewählte Aktivitäten für den Kontext', 'card-header');
        echo html_writer::start_div('card-body');
        
        // Nachricht zur Erklärung
        echo html_writer::div(
            '<div class="alert alert-info">Die Aktivitätsauswahl ist aktiviert. Nur die unten aufgeführten Aktivitäten werden in den Kontext einbezogen.</div>',
            'mb-3'
        );
        
        // Aktivitäten nach Sektionen sammeln
        $selected_activities = array();
        foreach ($cms as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            
            $activity_key = "activity_{$cm->id}";
            if (!empty($block_config->{$activity_key})) {
                $section_num = $cm->sectionnum;
                $section_name = get_section_name($course, $section_num);
                
                if (!isset($selected_activities[$section_num])) {
                    $selected_activities[$section_num] = array(
                        'name' => $section_name,
                        'activities' => array()
                    );
                }
                
                $selected_activities[$section_num]['activities'][] = $cm;
            }
        }
        
        // Prüfen, ob Aktivitäten ausgewählt wurden
        if (empty($selected_activities)) {
            echo html_writer::div(
                '<div class="alert alert-warning">Es wurden keine spezifischen Aktivitäten ausgewählt. Alle Aktivitäten im Kurs werden berücksichtigt.</div>',
                'mb-3'
            );
        } else {
            // Nach Sektionen anzeigen
            foreach ($selected_activities as $section_data) {
                echo html_writer::tag('h5', $section_data['name'], array('class' => 'mt-3 mb-2'));
                
                echo html_writer::start_tag('ul', array('class' => 'list-group mb-3'));
                foreach ($section_data['activities'] as $cm) {
                    $icon_url = $cm->get_icon_url()->out(false);
                    $icon_html = html_writer::img($icon_url, '', array('class' => 'icon', 'style' => 'width:16px;height:16px;margin-right:5px;'));
                    
                    echo html_writer::tag('li',
                        $icon_html . s($cm->name),
                        array('class' => 'list-group-item list-group-item-success')
                    );
                }
                echo html_writer::end_tag('ul');
            }
        }
        
        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card
    }

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

    $linkrecords = \block_tutoring_machine\link_manager::get_links_for_block($blockid);
    echo html_writer::start_div('card mb-4');
    echo html_writer::div('Externe Kontext-Links', 'card-header');
    echo html_writer::start_div('card-body');

    if (empty($linkrecords)) {
        echo html_writer::div('<div class="alert alert-info mb-0">Keine externen Links konfiguriert.</div>');
    } else {
        echo html_writer::start_tag('ul', ['class' => 'list-group']);
        foreach ($linkrecords as $link) {
            $status = $link->status ?? 'pending';
            $labelclass = 'light';
            if ($status === 'ok') {
                $labelclass = 'success';
            } else if ($status === 'error' || $status === 'blocked') {
                $labelclass = 'danger';
            } else if ($status === 'unsupported') {
                $labelclass = 'warning';
            }

            $title = !empty($link->title) ? format_string($link->title) : $link->url;
            $info = 'Status: ' . $status;
            if (!empty($link->lastfetch)) {
                $info .= ' · ' . userdate($link->lastfetch);
            }
            if (!empty($link->lasterror)) {
                $info .= ' · ' . format_string($link->lasterror);
            }

            $body = html_writer::tag('strong', s($title)) . '<br>' .
                html_writer::link($link->url, s($link->url), ['target' => '_blank', 'rel' => 'noreferrer']) . '<br>' .
                html_writer::tag('small', s($info));

            echo html_writer::tag('li', $body, ['class' => 'list-group-item list-group-item-' . $labelclass]);
        }
        echo html_writer::end_tag('ul');
    }

    echo html_writer::end_div();
    echo html_writer::end_div();

    // Vollständiger Kontext, der an die API übertragen wird
    echo html_writer::start_div('card mb-4');
    echo html_writer::div('Vollständiger API-Kontext', 'card-header');
    echo html_writer::start_div('card-body');

    // Zeige den vollständigen Kontext an, der an die API übertragen wird
    require_once($CFG->dirroot . '/blocks/tutoring_machine/classes/content_extractor.php');

    try {
        // Force cache update by adding a timestamp to URL if specific activities are enabled
        if (!empty($block_config->use_specific_activities)) {
            $_GET['cache_buster'] = time();
        }
        
        // Extrahiere den Kontext, wie es die Tutoring Machine tun würde
        $extractor = new block_tutoring_machine_content_extractor($course->id, $block_config);
        $full_context = $extractor->get_context();
        
        // Informationen über die selektive Aktivitätsauswahl
        $selection_info = '';
        if ($specific_activities_enabled) {
            $selection_info = '<div class="alert alert-info mb-3">Der folgende Kontext enthält nur die selektiv ausgewählten Aktivitäten.</div>';
        }
        
        echo html_writer::tag('p', 'Dies ist der vollständige Kontext, der an die API übertragen wird:');
        echo $selection_info;
        echo html_writer::tag('div',
            html_writer::tag('pre', s($full_context),
            array('class' => 'border p-2 bg-light', 'style' => 'white-space: pre-wrap; max-height: 400px; overflow-y: auto;'))
        );
    } catch (Exception $e) {
        echo html_writer::tag('div', 'Fehler beim Laden des Kontexts: ' . $e->getMessage(), array('class' => 'alert alert-danger'));
    }

    echo html_writer::end_div(); // card-body für den Vollständigen Kontex
    echo html_writer::end_div(); // card für den Vollständigen Kontex
} else {
    echo html_writer::start_div('card mb-4');
    echo html_writer::div('Kontextweitergabe', 'card-header');
    echo html_writer::start_div('card-body');
    echo html_writer::div('<div class="alert alert-info mb-0">Die Weitergabe von Kursinhalten an die KI ist deaktiviert. Es werden keine Materialien im Kontext übertragen.</div>');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

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
