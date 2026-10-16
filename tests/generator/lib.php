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
 * Data generator of the CourseCare block for tests.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursecare_generator extends testing_block_generator {
    /**
     * Create a diagnostic record directly, with controlled dates, request state, failure and result.
     *
     * Not an analysis: it writes block_coursecare_diag only, so tests can reach every displayed state.
     * Accepted keys: courseid (required), any field of the table, and timecompletedago / timeattemptedago, in
     * seconds before now.
     *
     * @param array|stdClass $record Values.
     * @return stdClass The stored record.
     */
    public function create_diagnostic($record): stdClass {
        global $DB;

        $record = (array)$record;
        if (empty($record['courseid'])) {
            throw new coding_exception('courseid is required to create a CourseCare diagnostic');
        }
        $now = time();
        foreach (['timecompleted', 'timeattempted'] as $field) {
            if (isset($record[$field . 'ago'])) {
                $record[$field] = $now - (int)$record[$field . 'ago'];
                unset($record[$field . 'ago']);
            }
        }
        $defaults = [
            'requeststatus' => 0, 'lastattemptfailed' => 0, 'failurecount' => 0, 'timemodified' => $now,
            'methodversion' => \block_coursecare\local\analysis\indicator_calculator::METHOD_VERSION,
        ];
        if (isset($record['timecompleted'])) {
            $defaults += ['filecount' => 1, 'logicalbytes' => 0, 'timeattempted' => $record['timecompleted'], 'partial' => 0];
        }
        if (!empty($record['lastattemptfailed'])) {
            $defaults['failurecount'] = 1;
            $defaults['failurereason'] = 'moodle_exception';
            $defaults['timeattempted'] = $now;
        }
        $record = (object)($record + $defaults);
        $record->id = $DB->insert_record('block_coursecare_diag', $record);
        return $DB->get_record('block_coursecare_diag', ['id' => $record->id]);
    }
}
