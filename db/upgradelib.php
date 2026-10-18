<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade helpers for the CourseCare block.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Turn the current result of each course into its first history point (Feature 006, FR-006).
 *
 * The current result is a real, dated measurement: its values and its date become the point of that day. Courses
 * without a completed result get no point, and nothing older is created. Running it again adds nothing. Reads the
 * plugin's own tables only, course by course.
 *
 * @return int Points created.
 */
function block_coursecare_seed_history(): int {
    global $DB;

    $columns = ['material' => 'materialbytes', 'submission' => 'submissionbytes', 'other' => 'otherbytes',
        'unclassified' => 'unclassifiedbytes'];
    $timezone = core_date::get_server_timezone();
    $created = 0;
    $results = $DB->get_recordset_select(
        'block_coursecare_diag',
        'timecompleted IS NOT NULL',
        null,
        'id',
        'id, courseid, timecompleted, methodversion, partial, filecount, logicalbytes, uniquecount, uniquebytes'
    );
    foreach ($results as $result) {
        $timeday = usergetmidnight((int)$result->timecompleted, $timezone);
        if ($DB->record_exists('block_coursecare_hist', ['courseid' => $result->courseid, 'timeday' => $timeday])) {
            continue;
        }
        $point = [
            'courseid' => (int)$result->courseid,
            'timeday' => $timeday,
            'timecompleted' => (int)$result->timecompleted,
            'methodversion' => (int)$result->methodversion,
            'partial' => (int)$result->partial,
            'filecount' => (int)$result->filecount,
            'logicalbytes' => (int)$result->logicalbytes,
            'uniquecount' => (int)$result->uniquecount,
            'uniquebytes' => (int)$result->uniquebytes,
        ] + array_fill_keys(array_values($columns), 0);
        $categories = $DB->get_records('block_coursecare_cat', ['diagid' => $result->id], '', 'category, logicalbytes');
        foreach ($categories as $category) {
            if (isset($columns[$category->category])) {
                $point[$columns[$category->category]] = (int)$category->logicalbytes;
            }
        }
        $DB->insert_record('block_coursecare_hist', (object)$point);
        $created++;
    }
    $results->close();
    return $created;
}
