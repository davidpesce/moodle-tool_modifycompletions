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

namespace tool_modifycompletions\form;

/**
 * Class step2_form
 *
 * @package    tool_modifycompletions
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class step2_form extends \moodleform{
    /**
     * The standard form definiton.
     * @return void
     */
    public function definition() {

        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $importid = $data['importid'];
        $columns = $this->_customdata['columns'];
        $cir = $this->_customdata['cir'];

        $mform->addElement('hidden', 'confirm', 1);
        $mform->setType('confirm', PARAM_BOOL);

        $mform->addElement('hidden', 'needsconfirm', 1);
        $mform->setType('needsconfirm', PARAM_BOOL);

        $mform->addElement('hidden', 'importid');
        $mform->setType('importid', PARAM_INT);
        $mform->setDefault('importid', $importid);

        $mform->addElement('header', 'previewheader', get_string('previewheader', 'tool_modifycompletions'));
        $mform->setExpanded('previewheader', true);

        $cir->init();
        $rowcount = 0;

        $tablehtml = '<table class="generaltable boxaligncenter flexible-wrap">';
        $tablehtml .= '<tr><th>User ID</th><th>Course ID</th><th>Timestamp</th></tr>';

        while (($rowdata = $cir->next()) && ($rowcount < 10)) {
            $tablehtml .= '<tr>';
            foreach ($rowdata as $cell) {
                $tablehtml .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $tablehtml .= '</tr>';
            $rowcount++;
        }

        $tablehtml .= '</table>';

        $cir->close();

        $mform->addElement('html', $tablehtml);

        $this->add_action_buttons(true, get_string('confirm', 'tool_modifycompletions'));
    }
}
