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
 * Class helper
 *
 * @package    tool_modifycompletions
 * @copyright  2023 David Pesce <david.pesce@exputo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Validate we have the minimum info to update course completion.
     *
     * @param object $record The record we imported
     * @return bool true if validated
     */
    public static function validate_import_record($record) {

        $userid = $record[0];
        if (empty($userid) || !preg_match('/^\d+$/', $userid)) {
            return false;
        }

        $courseid = $record[1];
        if (empty($courseid) || !preg_match('/^\d+$/', $courseid)) {
            return false;
        }

        $timestamp = $record[2];

        if (empty($timestamp) || !preg_match('/^\d+$/', $timestamp) || !self::is_valid_timestamp($timestamp)) {
            return false;
        }
        return true;
    }

    /**
     * Determine if a timestamp is valid.
     *
     * @param mixed $timestamp
     * @return bool
     */
    public static function is_valid_timestamp($timestamp) {
        return is_numeric($timestamp) && strlen($timestamp) == 10;
    }

    /**
     * Retrieve a course by its required column name.
     *
     * @param string $field name (e.g. idnumber, shortname)
     * @return object|null course object or null
     */
    public static function get_course_by_field($field, $value) {
        global $DB;

        $courses = $DB->get_records('course', [$field => $value]);

        if (count($courses) == 1) {
            $course = array_pop($courses);
            return $course;
        } else {
            return null;
        }
    }

    /**
     * Retrieve a user by its required column name.
     *
     * @param string $field name (e.g. idnumber, username)
     * @return object|null user object or null
     */
    public static function get_user_by_field($field, $value) {
        global $DB;

        $users = $DB->get_records('user', [$field => $value]);

        if (count($users) == 1) {
            $users = array_pop($users);
            return $users;
        } else {
            return null;
        }
    }

    /**
     * Undocumented function
     *
     * @param object $record Validated import record.
     * @return object $response Details of the record processing.
     */
    public static function update_course_completion_date($record) {
        global $DB;
        $response = new \stdClass();
        $response->modified = 0;
        $response->skipped = 1;
        $response->error = true;
        $response->message = null;
        $response->revertdata = null;

        $userid = $record[0];
        $courseid = $record[1];
        $timecompleted = $record[2];

        //Check if the course exists.
        if ($course = self::get_course_by_field('id', $courseid)) {
            $response->course = $course;
            $completion = new \completion_info($course);

            //Check if the user exists.
            if ($user =self::get_user_by_field('id', $userid)) {
                $response->user = $user;

                //Check if a completion for this user in this course exists.
                if ($completion->is_course_complete($user->id)) {
                    $response->modified = 1;
                    $response->skipped = 0;
                    $response->error = false;
                } else {
                    $response->message = 'The user matching ' . $userid
                        . ' does not have a completion in course matching ' . $courseid;
                }
            } else {
                $response->message = 'Unable to find user matching UserID: "' . $userid . '"';
            }
        } else {
            $response->message = 'Unable to find course matching CourseID: "' . $courseid . '"';
        }

        if (!$response->error) {
            // Retrieve the current completion records for this user and course.
            $currentcompletions = self::get_completion_records($record);
            $coursecompletion = $currentcompletions['course_completions'];

            $response->message = '[User: ' . $userid . '| Course: ' . $courseid . '] - '
            . 'updated completion timestamp from: ' . $coursecompletion->timecompleted . ' to: ' . $timecompleted;

            // This data can be used to generate a new CSV that can be used to revert all changes just made.
            $response->revertdata = [$userid, $courseid, $coursecompletion->timecompleted];

            $coursecompletion->timecompleted = $timecompleted;
            $coursecriteriacompletion = $currentcompletions['course_completion_crit_compl'];
            $coursecriteriacompletion->timecompleted = $timecompleted;

            //Modify the completion date in course_completions table.
            $DB->update_record('course_completions', $coursecompletion);

            //Modify the completion date in course_completion_crit_compl table.
            $DB->update_record('course_completion_crit_compl', $coursecriteriacompletion);

        }

        return $response;
    }

    /**
     * Retrieve the current course completion timestamp.
     *
     * @param object $record A validated import record.
     * @return array $currentcompletions An array of course completions.
     */
    public static function get_completion_records($record) {
        global $DB;

        $cc_table = 'course_completions';

        $currentcompletions = [
            'course_completions' => '',
            'course_completion_crit_compl' => '',
        ];

        // Retrieve the current completion timestamp from course_completions table.
        $coursecompletion = $DB->get_record_sql(
            'SELECT * FROM {course_completions} WHERE userid = ? AND course = ?',
            [
                $record[0],
                $record[1]
            ]
        );

        $currentcompletions['course_completions'] = $coursecompletion;

        // Retrieve the current completion timestamp from course_completion_crit_compl table.
        $criteriacompletion = $DB->get_record_sql(
            'SELECT * FROM {course_completion_crit_compl} WHERE userid = ? AND course = ?',
            [
                $record[0],
                $record[1]
            ]
        );

        $currentcompletions['course_completion_crit_compl'] = $criteriacompletion;

        return $currentcompletions;
    }
}
