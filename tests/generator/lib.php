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
    /** @var int Large-material links created, for synthetic file ids. */
    private int $largelinkcount = 0;

    /**
     * Create a diagnostic record directly, with controlled dates, request state, failure and result.
     *
     * Not an analysis: it writes the plugin's tables only, so tests can reach every displayed state.
     * Accepted keys: courseid (required), any field of block_coursecare_diag, timecompletedago / timeattemptedago
     * (seconds before now), and the child rows:
     * - categories: category => [filecount, logicalbytes, uniquecount, uniquebytes] (block_coursecare_cat);
     * - activities: cmid => fields of block_coursecare_act (cmid 0 = course level; missing fields are 0);
     * - largelinks: list of [cmid, filesize] (block_coursecare_large, with a synthetic fileid).
     *
     * @param array|stdClass $record Values.
     * @return stdClass The stored record.
     */
    public function create_diagnostic($record): stdClass {
        global $DB;

        $record = (array)$record;
        $children = [];
        foreach (['categories', 'activities', 'largelinks'] as $key) {
            $children[$key] = $record[$key] ?? [];
            unset($record[$key]);
        }
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
            $defaults += ['filecount' => 1, 'logicalbytes' => 0, 'timeattempted' => $record['timecompleted'], 'partial' => 0,
                // Feature 009: a 0.9.0 analysis always stores its suggestions; calculated, none, unless given.
                'suggestions' => \block_coursecare\local\analysis\stored_suggestions::encode([])];
        }
        if (!empty($record['lastattemptfailed'])) {
            $defaults['failurecount'] = 1;
            $defaults['failurereason'] = 'moodle_exception';
            $defaults['timeattempted'] = $now;
        }
        if (isset($record['suggestions']) && is_array($record['suggestions'])) {
            // Feature 009: a list of assignment course module ids found by the analysis.
            $record['suggestions'] = \block_coursecare\local\analysis\stored_suggestions::encode(array_map(
                fn($cmid): array => ['kind' => \block_coursecare\local\analysis\stored_suggestions::ATTEMPTS, 'cmid' => (int)$cmid],
                $record['suggestions']
            ));
        }
        $record = (object)($record + $defaults);
        $record->id = $DB->insert_record('block_coursecare_diag', $record);
        foreach ($children['categories'] as $category => $values) {
            $this->create_diagnostic_category(['courseid' => $record->courseid, 'category' => $category] + $values);
        }
        foreach ($children['activities'] as $cmid => $values) {
            $this->create_diagnostic_activity(['courseid' => $record->courseid, 'cmid' => $cmid] + $values);
        }
        foreach ($children['largelinks'] as $link) {
            $this->create_diagnostic_largelink(['courseid' => $record->courseid] + $link);
        }
        return $DB->get_record('block_coursecare_diag', ['id' => $record->id]);
    }

    /**
     * Id of the diagnostic of a course, created by create_diagnostic().
     *
     * @param array $record Values with courseid.
     * @return int
     */
    private function diagnostic_id(array $record): int {
        global $DB;

        if (empty($record['courseid'])) {
            throw new coding_exception('courseid is required');
        }
        return (int)$DB->get_field('block_coursecare_diag', 'id', ['courseid' => $record['courseid']], MUST_EXIST);
    }

    /**
     * Add a category row to the diagnostic of a course.
     *
     * Keys: courseid, category, filecount, logicalbytes, uniquecount, uniquebytes (missing counts are 0).
     *
     * @param array|stdClass $record Values.
     * @return stdClass
     */
    public function create_diagnostic_category($record): stdClass {
        global $DB;

        $record = (array)$record;
        $row = ['diagid' => $this->diagnostic_id($record), 'category' => (string)$record['category']];
        foreach (\block_coursecare\local\analysis\diagnostic_result::CATEGORY_FIELDS as $field) {
            $row[$field] = (int)($record[$field] ?? 0);
        }
        $row['id'] = $DB->insert_record('block_coursecare_cat', (object)$row);
        return (object)$row;
    }

    /**
     * Add an activity row to the diagnostic of a course.
     *
     * Keys: courseid, cmid (0 = course level) and any field of block_coursecare_act (missing fields are 0).
     *
     * @param array|stdClass $record Values.
     * @return stdClass
     */
    public function create_diagnostic_activity($record): stdClass {
        global $DB;

        $record = (array)$record;
        $row = ['diagid' => $this->diagnostic_id($record), 'cmid' => (int)($record['cmid'] ?? 0)];
        foreach (\block_coursecare\local\analysis\diagnostic_result::ACTIVITY_FIELDS as $field) {
            $row[$field] = (int)($record[$field] ?? 0);
        }
        $row['id'] = $DB->insert_record('block_coursecare_act', (object)$row);
        return (object)$row;
    }

    /**
     * Add a large-material link to the diagnostic of a course, with a synthetic file id.
     *
     * Keys: courseid, cmid (0 = course level), filesize.
     *
     * @param array|stdClass $record Values.
     * @return stdClass
     */
    public function create_diagnostic_largelink($record): stdClass {
        global $DB;

        $record = (array)$record;
        $this->largelinkcount++;
        $row = ['diagid' => $this->diagnostic_id($record), 'cmid' => (int)($record['cmid'] ?? 0),
            'fileid' => 900000000 + $this->largelinkcount, 'filesize' => (int)$record['filesize']];
        $row['id'] = $DB->insert_record('block_coursecare_large', (object)$row);
        return (object)$row;
    }

    /**
     * Add a history point to a course (Feature 006), on the day a given number of days ago in the site timezone.
     *
     * Keys: courseid, daysago (0 = today), and any field of block_coursecare_hist (missing values are 0; the method
     * version is the current one). logicalbytes defaults to the sum of the four categories.
     *
     * @param array|stdClass $record Values.
     * @return stdClass
     */
    public function create_diagnostic_history($record): stdClass {
        global $DB;

        $record = (array)$record;
        if (empty($record['courseid'])) {
            throw new coding_exception('courseid is required to create a CourseCare history point');
        }
        $time = time() - (int)($record['daysago'] ?? 0) * DAYSECS;
        unset($record['daysago']);
        $categories = ['materialbytes', 'submissionbytes', 'otherbytes', 'unclassifiedbytes'];
        $row = $record + array_fill_keys($categories, 0) + [
            'timeday' => \block_coursecare\local\collector\diagnostic_store::history_day($time),
            'timecompleted' => $time,
            'methodversion' => \block_coursecare\local\analysis\indicator_calculator::METHOD_VERSION,
            'partial' => 0,
            'filecount' => 1,
            'uniquecount' => 1,
            'uniquebytes' => 0,
        ];
        $row['logicalbytes'] = $row['logicalbytes'] ?? array_sum(array_intersect_key($row, array_flip($categories)));
        $row['id'] = $DB->insert_record('block_coursecare_hist', (object)$row);
        return (object)$row;
    }

    /**
     * Add a suggestion about an assignment to the stored suggestions of the diagnostic of a course (Feature 009).
     *
     * @param array|stdClass $record courseid and cmid.
     */
    public function create_diagnostic_suggestion($record): void {
        global $DB;

        $record = (array)$record;
        $diag = $DB->get_record('block_coursecare_diag', ['courseid' => $record['courseid']], 'id, suggestions', MUST_EXIST);
        $items = \block_coursecare\local\analysis\stored_suggestions::decode($diag->suggestions) ?? [];
        $items[] = ['kind' => \block_coursecare\local\analysis\stored_suggestions::ATTEMPTS, 'cmid' => (int)$record['cmid']];
        $DB->set_field(
            'block_coursecare_diag',
            'suggestions',
            \block_coursecare\local\analysis\stored_suggestions::encode($items),
            ['id' => $diag->id]
        );
    }
}
