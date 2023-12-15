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

namespace tool_modifycompletions;

/**
 * Class tracker
 *
 * @package    tool_modifycompletions
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker {

    /**
     * Constant to output nothing.
     */
    const NO_OUTPUT = 0;

    /**
     * Constant to output HTML.
     */
    const OUTPUT_HTML = 1;

    /**
     * Constant to output plain text.
     */
    const OUTPUT_PLAIN = 2;

    /**
     * @var array columns to display.
     */
    protected $columns = array('line', 'result', 'user', 'id', 'fullname', 'status');

    /**
     * @var int row number.
     */
    protected $rownb = 0;

    /**
     * @var int chosen output mode.
     */
    protected $outputmode;

    /**
     * @var object output buffer.
     */
    protected $buffer;

    /** @var array [[userid, courseid, prior completion timestamp],] - All the data needed to revert the changes just made. */
    protected $revertdata = [];

    /**
     * Constructor.
     *
     * @param int $outputmode The desired output mode.
     * @param bool $passthrough Print output as well as buffer it.
     *
     */
    public function __construct($outputmode = self::NO_OUTPUT, $passthrough = true) {
        $this->outputmode = $outputmode;
        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $this->buffer = new \progress_trace_buffer(new \text_progress_trace(), $passthrough);
        }
        if ($this->outputmode == self::OUTPUT_HTML) {
            $this->buffer = new \progress_trace_buffer(new \text_progress_trace(), $passthrough);
        }
    }

    /**
     * Start the output.
     *
     * @return void
     */
    public function start() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $columns = array_flip($this->columns);
            unset($columns['status']);
            $columns = array_flip($columns);
            $this->buffer->output(implode("\t", $columns));
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $ci = 0;

            echo \html_writer::start_tag('table', array('class' => 'generaltable boxaligncenter flexible-wrap',
                'summary' => get_string('uploadcompletionsresult', 'tool_modifycompletions')));
            echo \html_writer::start_tag('tr', array('class' => 'heading r' . $this->rownb));
            echo \html_writer::tag('th', get_string('csvline', 'tool_modifycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('result', 'tool_modifycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('userid', 'tool_modifycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('username', 'tool_modifycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('courseid', 'tool_modifycompletions'),
                array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('shortnamecourse'), array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::tag('th', get_string('status'), array('class' => 'c' . $ci++, 'scope' => 'col'));
            echo \html_writer::end_tag('tr');
        }
    }

    /**
     * Output one more line.
     *
     * @param int $line line number.
     * @param bool $outcome success or not?
     * @param array $status array of statuses.
     * @param object|null $data extra data to display
     * @return void
     */
    public function output($line, $outcome, $status, $data) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        $message = array(
            $line,
            self::getoutcomeindicator($outcome),
            isset($data->user) ? $data->user->id : '',
            isset($data->user) ? $data->user->username : '',
            isset($data->course) ? $data->course->id : '',
            isset($data->course) ? $data->course->shortname : ''
        );

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $this->buffer->output(implode("\t", $message));
            if (is_array($status)) {
                $this->buffer->output(implode("\t  ", $status));
            }
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            $ci = 0;
            $this->rownb++;
            if (is_array($status)) {
                $status = implode(\html_writer::empty_tag('br'), $status);
            }
            echo \html_writer::start_tag('tr', array('class' => 'r' . $this->rownb % 2));
            echo \html_writer::tag('td', $message[0], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $message[1], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $message[2], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $message[3], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $message[4], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $message[5], array('class' => 'c' . $ci++));
            echo \html_writer::tag('td', $status, array('class' => 'c' . $ci++));
            echo \html_writer::end_tag('tr');
        }
    }

    /**
     * Finish the output.
     *
     * @return void
     */
    public function finish() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            echo \html_writer::end_tag('table');
        }
    }

    /**
     * Output the results.
     *
     * @param int $total total completions.
     * @param int $modified count of completions modified.
     * @param int $skipped count of completions skipped.
     * @param int $errors count of errors.
     * @return void
     */
    public function results($total, $modified, $skipped, $errors) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        $message = array(
            get_string('completionstotal', 'tool_modifycompletions', $total),
            get_string('completionsmodified', 'tool_modifycompletions', $modified),
            get_string('completionsskipped', 'tool_modifycompletions', $skipped),
            get_string('completionserrors', 'tool_modifycompletions', $errors)
        );

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            foreach ($message as $msg) {
                $this->buffer->output($msg);
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $buffer = new \progress_trace_buffer(new \text_progress_trace(), $total);
            foreach ($message as $msg) {
                $buffer->output($msg . "<br/>");
            }
            $buffer->finished();
        }
    }

    /**
     * Create and display the link to download the "undo" CSV file.
     *
     * @param array $revertdata
     * @return void
     */
    public function revertdownload($revertdata) {
        $context = \context_system::instance();
        $file_storage = get_file_storage();
        $csv_content = $this->create_csv_string($revertdata);

        // Prepare file record object
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'tool_modifycompletions',
            'filearea'  => 'temp',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'modify-completions-undo.csv');

        // Create the file
        $file = $file_storage->create_file_from_string($fileinfo, $csv_content);

        $pathnamehash = $file->get_pathnamehash();

        $downloadurl = new \moodle_url('/admin/tool/modifycompletions/index.php', array('downloadcsv' => 1));
        $downloadlink = \html_writer::link($downloadurl, get_string('downloadcsv', 'tool_modifycompletions'));

        echo $downloadlink;

    }

    /**
     * Return a CSV string that contains the reverted data.
     *
     * @param array $revertdata
     * @return string
     */
    private function create_csv_string($revertdata) {
        $csv_content = '';
        foreach ($revertdata as $revertline) {
            $csvline = implode(',', $revertline);
            $csv_content .= $csvline . "\n";
        }
        return $csv_content;
    }

    /**
     * Get the outcome indicator
     *
     * @param bool $outcome success or not?
     * @return string|null
     */
    private function getoutcomeindicator($outcome) {
        global $OUTPUT;

        switch ($this->outputmode) {
            case self::OUTPUT_PLAIN:
                return $outcome ? 'OK' : 'NOK';
            case self::OUTPUT_HTML:
                return $outcome ? $OUTPUT->pix_icon('i/valid', '') : $OUTPUT->pix_icon('i/invalid', '');
            default:
               return;
        }
    }

    /**
     * Return text buffer.
     * @return string buffered plain text
     */
    public function get_buffer() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return "";
        }
        return $this->buffer->get_buffer();
    }

}
