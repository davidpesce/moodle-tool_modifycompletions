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
 * Class processor is used to handle the uploaded file.
 *
 * @package    tool_modifycompletions
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {

    /** @var int The import id. */
    public $importid = 0;

    /** @var \csv_import_reader */
    protected $cir;

    /** @var array default values. */
    protected $defaults = array();

    /** @var array CSV columns. */
    protected $columns = array();

    /** @var array of errors where the key is the line number. */
    protected $errors = array();

    /** @var int line number. */
    protected $linenb = 0;

    /** @var bool whether the process has been started or not. */
    protected $processstarted = false;

    /** @var array [[userid, courseid, prior completion timestamp],] - All the data needed to revert the changes just made. */
    protected $revertdata = [];

    /**
     * Constructor
     *
     * @param \csv_import_reader $cir import reader object
     * @param array $defaults default data value
     */
    public function __construct(\csv_import_reader $cir, array $defaults = array()) {

        $this->cir = $cir;
        $this->columns = $cir->get_columns();
        $this->defaults = $defaults;
        $this->validate();
        $this->reset();

    }

    /**
     * Execute the process.
     *
     * @param object $tracker the output tracker to use.
     * @return void
     */
    public function execute($tracker = null) {
        if ($this->processstarted) {
              throw new \coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
              $tracker = new \tool_modifycompletions\tracker(\tool_modifycompletions\tracker::NO_OUTPUT);
        }
        $tracker->start();

        $total = 0;
        $modified = 0;
        $skipped = 0;
        $errors = 0;

        // Need extra time and memory to process big files.
        \core_php_time_limit::raise();
        \raise_memory_limit(MEMORY_EXTRA);

        // Loop over the CSV lines.
        while ($line = $this->cir->next()) {
            $this->linenb++;
            $total++;

            if (\tool_modifycompletions\helper::validate_import_record($line)) {

                $response = \tool_modifycompletions\helper::update_course_completion_date($line);
                $modified = $modified + $response->modified;
                $skipped = $skipped + $response->skipped;

                if ($response->modified != 0) {
                    $status = array("Course completion updated", $response->message);
                } else {
                    $status = array("Course completion skipped", $response->message);
                }

                $tracker->output($this->linenb, true, $status, $response);
                array_push($this->revertdata, $response->revertdata);
            } else {
                $errors++;
                $status = array("Invalid Import Record");
                $tracker->output($this->linenb, false, $status, null);
            }
        }

        $tracker->finish();
        $tracker->results($total, $modified, $skipped, $errors);
        $tracker->revertdownload($this->revertdata);
    }

    /**
     * Reset the current process.
     *
     * @return void.
     */
    public function reset() {
        $this->processstarted = false;
        $this->linenb = 0;
        $this->cir->init();
        $this->errors = array();
    }

    /**
     * Validation.
     *
     * @return void
     */
    protected function validate() {
        $foundcount = count($this->list_found_headers());
        $requiredcount = count($this->list_required_headers());

        if (empty($this->columns)) {
            throw new \moodle_exception('cannotreadtmpfile', 'error');
        } else if ($foundcount < $requiredcount) {
            throw new \moodle_exception('csvcolumncounterror', 'tool_modifycompletions');
        }
    }

    /**
     * Return the list of required headers for the import.
     *
     * @return array contains the column headers
     */
    public static function list_required_headers() {
        return array(
            'useridnumber',
            'courseidnumber',
            'timestamp'
        );
    }

    /**
     * Return the list of headers found in the CSV.
     *
     * @return array contains the column headers
     */
    public function list_found_headers() {
        return $this->columns;
    }

    /**
     * Log errors on the current line.
     *
     * @param array $errors array of errors
     * @return void
     */
    protected function log_error($errors) {
        if (empty($errors)) {
            return;
        }

        foreach ($errors as $code => $langstring) {
            if (!isset($this->errors[$this->linenb])) {
                $this->errors[$this->linenb] = array();
            }
            $this->errors[$this->linenb][$code] = $langstring;
        }
    }

    /**
     * Parse a line to return an array(column => value)
     *
     * @param array $line returned by csv_import_reader
     * @return array
     */
    protected function parse_line($line) {
        $data = array();
        foreach ($line as $keynum => $value) {
            if (!isset($this->columns[$keynum])) {
                // This should not happen.
                continue;
            }

            $key = $this->columns[$keynum];
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * Return the errors.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

}
