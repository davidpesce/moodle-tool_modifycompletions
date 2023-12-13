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
 * English language pack for Modify Course Completions
 *
 * @package    tool_modifycompletions
 * @category   string
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Modify Course Completions';
$string['modifycompletions'] = 'Modify Course Completions';
$string['modifycompletions_help'] = 'Help text';
$string['details'] = "
<p>This tool is used to modify the timestamps of existing course completions. Once the process is complete, the users completion timestamp (date and time) will be modified with the timestamp provided in the upload. An ouput of the results will be available after the process is completed.</p>
<p><strong>It is recommended that you make a backup of your database before proceeding</strong>.</p>
<p><strong>The following requirements apply:</strong>
<ul>
<li>The file must be a CSV file.</li>
<li>The CSV file must contain the following fields: <code>userid, courseid, timestamp</code></li>
<li>An existing course completion must exist for the userid and courseid specified.</li>
</ul>
</p>
<p>An upload template can be downloaded [<a download href='/admin/tool/modifycompletions/uploadtemplate.csv'>here</a>].</p>
";
$string["coursefile"] = "File";
$string["coursefile_help"] = "This file must be a CSV.";
$string["csvdelimiter"] = "Delimiter";
$string["csvdelimiter_help"] = "The CSV delimiter of the file.";
$string["csvcolumncounterror"] = "The number of columns does not match the required columns.";
$string["encoding"] = "Encoding";
$string["encoding_help"] = "Encoding of the file.";
$string['privacy:metadata'] = 'The Modify Course Completions plugin does not store any data.';
$string['import'] = 'Import';
$string['csvfileerror'] = 'There is something wrong with the format of the CSV file. Please check the number of headings and columns match, and that the separator and file encoding are correct. {$a}';
$string['invalidimportfile'] = 'File format is invalid.';
$string['completionstotal'] = 'Completions total: {$a}';
$string['completionsmodified'] = 'Completions modified: {$a}';
$string['completionsskipped'] = 'Completions skipped: {$a}';
$string['completionserrors'] = 'Completions errors: {$a}';
$string['uploadcompletionsresult'] = 'Upload results';
$string['csvline'] = 'Line';
$string['id'] = 'ID';
$string['result'] = 'Result';
$string['columnsheader'] = 'Columns';
$string['confirm'] = 'Confirm';
$string['uploadactivitycompletionsresult'] = 'Upload results';
$string['completionnotenabledforsite'] = 'Completions are not enabled for this site.';
$string['cachedef_helper'] = 'Upload page caching';
$string['userid'] = 'User ID';
$string['username'] = 'Username';
$string['courseid'] = 'Course ID';
$string['previewheader'] = 'Preview of the first ten rows';