<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Modify course completions interface.
 *
 * @package    tool_modifycompletions
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

admin_externalpage_setup('toolmodifycompletions');

$importid = optional_param('importid', '', PARAM_INT);
$confirm  = optional_param('confirm', '0', PARAM_BOOL);

$pagetitle = get_string('modifycompletions', 'tool_modifycompletions');
$returnurl = new moodle_url('/admin/tool/modifycompletions/index.php', []);

if (empty($importid)) {
    $mform1 = new \tool_modifycompletions\form\step1_form();

    if ($form1data = $mform1->get_data()) {
        $importid = csv_import_reader::get_new_iid('modifycompletions');
        $cir = new csv_import_reader($importid, 'modifycompletions');
        $content = $mform1->get_file_content('completionsfile');
        $readcount = $cir->load_csv_content($content, $form1data->encoding, $form1data->delimiter_name);
        unset($content);
        if ($readcount === false) {
            throw new \moodle_exception('csvfileerror', 'tool_modifycompletions', $returnurl, $cir->get_error());
        } else if ($readcount == 0) {
            throw new \moodle_exception('csvemptyfile', 'error', $returnurl, $cir->get_error());
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help($pagetitle, 'modifycompletions', 'tool_modifycompletions');
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} else {
    $cir = new csv_import_reader($importid, 'modifycompletions');
}

// Data to set in the form.
//debugging('hi', DEBUG_DEVELOPER);
$data = ['importid' => $importid];

$context = context_system::instance();
$mform2 = new \tool_modifycompletions\form\step2_form(null, array('contextid' => $context->id, 'columns' => $cir->get_columns(),
    'data' => $data, 'cir' => $cir));

// Was the second form submitted.
if ($form2data = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
} else if ($form2data = $mform2->get_data()) {

    $defaults = (array) $form2data->defaults;

    $importid = $form2data->importid;
    $processor = new \tool_modifycompletions\processor($cir, $defaults);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle);
    $processor->execute(new \tool_modifycompletions\tracker(\tool_modifycompletions\tracker::OUTPUT_HTML, false));
    echo $OUTPUT->continue_button($returnurl);

    // Deleting the file after processing.
    if (!empty($form2data->restorefile)) {
        @unlink($form2data->restorefile);
    }

} else {
    // First time.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($pagetitle);
    if (!completion_info::is_enabled_for_site()) {
        echo get_string('completionnotenabledforsite', 'tool_modifycompletions');
    } else {
        $mform2->display();
    }
}
echo $OUTPUT->footer();
