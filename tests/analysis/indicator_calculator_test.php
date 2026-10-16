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

namespace block_coursecare\analysis;

use block_coursecare\local\analysis\diagnostic_result;
use block_coursecare\local\analysis\indicator_calculator;

/**
 * Pure tests of the indicator calculation from hand-built aggregate rows (contracts/indicators.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\indicator_calculator
 * @covers     \block_coursecare\local\analysis\diagnostic_result
 */
final class indicator_calculator_test extends \basic_testcase {
    /** @var int One MB. */
    private const MB = 1048576;

    /**
     * Contexts used by the rows: 10 = course, 20 = module.
     *
     * @return array
     */
    private static function contexts(): array {
        return [
            10 => (object)['id' => 10, 'contextlevel' => CONTEXT_COURSE, 'instanceid' => 5, 'path' => '/1/3/10'],
            20 => (object)['id' => 20, 'contextlevel' => CONTEXT_MODULE, 'instanceid' => 7, 'path' => '/1/3/10/20'],
        ];
    }

    /**
     * Build a Q1 row.
     *
     * @param int $contextid Context.
     * @param string $component Component.
     * @param string $filearea Area.
     * @param int $filecount Records.
     * @param int $bytes Bytes.
     * @param int $largecount Large records.
     * @param int $largebytes Large bytes.
     * @param string $reftype Reference type.
     * @return \stdClass
     */
    private static function row(
        int $contextid,
        string $component,
        string $filearea,
        int $filecount,
        int $bytes,
        int $largecount = 0,
        int $largebytes = 0,
        string $reftype = 'none'
    ): \stdClass {
        return (object)compact(
            'contextid',
            'component',
            'filearea',
            'reftype',
            'filecount',
            'bytes',
            'largecount',
            'largebytes'
        );
    }

    /**
     * A and the record count add only the main scope: excluded areas, backups, recycle bin and references are out.
     */
    public function test_main_scope_only(): void {
        $rows = [
            self::row(20, 'mod_resource', 'content', 3, 6 * self::MB),
            self::row(10, 'course', 'summary', 1, self::MB),
            self::row(20, 'mod_folder', 'temp', 2, 9 * self::MB),
            self::row(10, 'backup', 'course', 1, 40 * self::MB),
            self::row(10, 'tool_recyclebin', 'recyclebin_course', 1, 5 * self::MB),
            self::row(20, 'mod_resource', 'content', 1, 3 * self::MB, 0, 0, indicator_calculator::REF_INTERNAL),
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows);

        $this->assertSame(7 * self::MB, $result->summary['logicalbytes']);
        $this->assertSame(4, $result->summary['filecount']);
        $this->assertSame(0, $result->summary['unclassifiedcount']);
        $this->assertSame(0, $result->summary['partial']);
        $this->assertSame(indicator_calculator::METHOD_VERSION, $result->summary['methodversion']);
        $this->assertSame(50 * self::MB, $result->summary['largethreshold']);
    }

    /**
     * Large files of the main scope are counted from the aggregate columns; others are ignored.
     */
    public function test_large_files_logical(): void {
        $rows = [
            self::row(20, 'mod_resource', 'content', 3, 130 * self::MB, 2, 120 * self::MB),
            self::row(10, 'backup', 'course', 1, 400 * self::MB, 1, 400 * self::MB),
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows);
        $this->assertSame(2, $result->summary['largecount']);
        $this->assertSame(120 * self::MB, $result->summary['largebytes']);
    }

    /**
     * Unclassified areas count in A and mark the result as partial (FR-023, FR-024).
     */
    public function test_unclassified_counts_and_marks_partial(): void {
        $rows = [
            self::row(20, 'local_fake', 'stuff', 2, 2 * self::MB),
            self::row(20, 'mod_page', 'content', 1, self::MB),
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows);
        $this->assertSame(3 * self::MB, $result->summary['logicalbytes']);
        $this->assertSame(2, $result->summary['unclassifiedcount']);
        $this->assertSame(2 * self::MB, $result->summary['unclassifiedbytes']);
        $this->assertSame(1, $result->summary['partial']);
    }

    /**
     * No rows: zero is a measured result, not missing data.
     */
    public function test_empty_course(): void {
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), []);
        $this->assertSame(0, $result->summary['logicalbytes']);
        $this->assertSame(0, $result->summary['filecount']);
    }

    /**
     * Without unique-content rows, the unique indicators stay null (not calculated, never zero).
     */
    public function test_unique_fields_null_without_unique_rows(): void {
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), [
            self::row(20, 'mod_page', 'content', 1, self::MB),
        ]);
        $this->assertNull($result->summary['uniquebytes']);
        $this->assertNull($result->summary['uniquecount']);
        $this->assertNull($result->summary['bruniquebytes']);
        $this->assertSame([], $result->categories);
        $this->assertNull($result->summary['largeuniquebytes']);
        $this->assertNull($result->summary['largelinkstruncated']);
        $this->assertSame([], $result->activities);
        $this->assertSame([], $result->largelinks);
    }

    /**
     * B comes from the unique rows of the main group; the aggregate unique from the backup group (T030).
     */
    public function test_unique_content_and_backup_aggregate(): void {
        $rows = [
            self::row(20, 'mod_resource', 'content', 3, 30 * self::MB),
            self::row(10, 'backup', 'course', 1, 5 * self::MB),
            self::row(10, 'tool_recyclebin', 'recyclebin_course', 1, 5 * self::MB),
        ];
        $groups = [
            indicator_calculator::GROUP_MAIN => (object)['contents' => 1, 'bytes' => 10 * self::MB],
            indicator_calculator::GROUP_BACKUP => (object)['contents' => 1, 'bytes' => 5 * self::MB],
        ];
        $categories = [
            'material' => (object)['contents' => 1, 'bytes' => 10 * self::MB],
            'backup' => (object)['contents' => 1, 'bytes' => 5 * self::MB],
            'recyclebin' => (object)['contents' => 1, 'bytes' => 5 * self::MB],
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows, $groups, $categories);

        $this->assertSame(30 * self::MB, $result->summary['logicalbytes']);
        $this->assertSame(10 * self::MB, $result->summary['uniquebytes']);
        $this->assertSame(1, $result->summary['uniquecount']);
        $this->assertSame(2, $result->summary['brcount']);
        $this->assertSame(10 * self::MB, $result->summary['brbytes']);
        $this->assertSame(5 * self::MB, $result->summary['bruniquebytes']);
        $this->assertSame(['filecount' => 1, 'logicalbytes' => 5 * self::MB, 'uniquecount' => 1,
            'uniquebytes' => 5 * self::MB], $result->categories['backup']);
        $this->assertSame(5 * self::MB, $result->categories['recyclebin']['logicalbytes']);
        $this->assertSame(['filecount' => 3, 'logicalbytes' => 30 * self::MB, 'uniquecount' => 1,
            'uniquebytes' => 10 * self::MB], $result->categories['material']);
        $this->assertTrue(indicator_calculator::invariants_hold($result));
    }

    /**
     * Main categories (T055): a row only for categories with records, the sum of A_c is A, the unique content of
     * each category comes from Q2, and a content shared by two categories counts in both (sum of B_c above B).
     */
    public function test_main_categories(): void {
        $rows = [
            self::row(10, 'course', 'summary', 1, self::MB),
            self::row(20, 'mod_resource', 'content', 2, 4 * self::MB),
            self::row(20, 'assignsubmission_file', 'submission_files', 3, 6 * self::MB),
            self::row(20, 'local_fake', 'stuff', 1, 2 * self::MB),
            self::row(20, 'mod_folder', 'temp', 2, 9 * self::MB),
            self::row(10, 'backup', 'course', 1, 40 * self::MB),
        ];
        $groups = [
            indicator_calculator::GROUP_MAIN => (object)['contents' => 5, 'bytes' => 9 * self::MB],
            indicator_calculator::GROUP_BACKUP => (object)['contents' => 1, 'bytes' => 40 * self::MB],
        ];
        $uniques = [
            'material' => (object)['contents' => 3, 'bytes' => 5 * self::MB],
            'submission' => (object)['contents' => 2, 'bytes' => 4 * self::MB],
            'unclassified' => (object)['contents' => 1, 'bytes' => 2 * self::MB],
            'backup' => (object)['contents' => 1, 'bytes' => 40 * self::MB],
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows, $groups, $uniques);

        $this->assertSame(['material', 'submission', 'unclassified', 'backup'], array_keys($result->categories));
        $this->assertArrayNotHasKey('other', $result->categories);
        $this->assertSame(['filecount' => 3, 'logicalbytes' => 5 * self::MB, 'uniquecount' => 3,
            'uniquebytes' => 5 * self::MB], $result->categories['material']);
        $this->assertSame(['filecount' => 3, 'logicalbytes' => 6 * self::MB, 'uniquecount' => 2,
            'uniquebytes' => 4 * self::MB], $result->categories['submission']);
        $this->assertSame(2 * self::MB, $result->categories['unclassified']['logicalbytes']);

        $main = array_intersect_key($result->categories, array_flip(['material', 'submission', 'other', 'unclassified']));
        $this->assertSame($result->summary['logicalbytes'], array_sum(array_column($main, 'logicalbytes')));
        $this->assertSame($result->summary['filecount'], array_sum(array_column($main, 'filecount')));
        $this->assertGreaterThan($result->summary['uniquebytes'], array_sum(array_column($main, 'uniquebytes')));
        $this->assertSame(1, $result->summary['unclassifiedcount']);
        $this->assertSame(2 * self::MB, $result->summary['unclassifiedbytes']);
        $this->assertSame(1, $result->summary['partial']);
        $this->assertTrue(indicator_calculator::invariants_hold($result));
    }

    /**
     * References are counted by type; only internal and external sizes are summed; undetermined marks partial.
     */
    public function test_references_by_type(): void {
        $rows = [
            self::row(20, 'mod_resource', 'content', 1, self::MB),
            self::row(20, 'mod_resource', 'content', 2, 3 * self::MB, 0, 0, indicator_calculator::REF_INTERNAL),
            self::row(20, 'mod_resource', 'content', 1, 4 * self::MB, 0, 0, indicator_calculator::REF_EXTERNAL),
            self::row(20, 'mod_resource', 'content', 1, 9 * self::MB, 0, 0, indicator_calculator::REF_UNDETERMINED),
        ];
        $result = (new indicator_calculator(50 * self::MB))->calculate(self::contexts(), $rows, [], []);
        $this->assertSame(self::MB, $result->summary['logicalbytes']);
        $this->assertSame(2, $result->summary['refinternalcount']);
        $this->assertSame(3 * self::MB, $result->summary['refinternalbytes']);
        $this->assertSame(1, $result->summary['refexternalcount']);
        $this->assertSame(4 * self::MB, $result->summary['refexternalbytes']);
        $this->assertSame(1, $result->summary['refundeterminedcount']);
        $this->assertSame(1, $result->summary['partial']);
        $this->assertSame(0, $result->summary['uniquebytes']);
    }

    /**
     * The invariants detect impossible results.
     */
    public function test_invariants(): void {
        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary = ['filecount' => 2, 'logicalbytes' => 100, 'largecount' => 1, 'largebytes' => 60] + $summary;
        $this->assertTrue(indicator_calculator::invariants_hold(new diagnostic_result($summary)));

        $broken = ['largebytes' => 160] + $summary;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($broken)));

        $broken = ['uniquebytes' => 101] + $summary;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($broken)));

        $broken = ['logicalbytes' => -1, 'largebytes' => null] + $summary;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($broken)));

        $broken = ['brbytes' => 10, 'bruniquebytes' => 11] + $summary;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($broken)));

        $category = ['filecount' => 1, 'logicalbytes' => 5, 'uniquecount' => 1, 'uniquebytes' => 6];
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, ['backup' => $category])));

        // The main categories must add up to A and to the record count.
        $material = ['filecount' => 2, 'logicalbytes' => 100, 'uniquecount' => 1, 'uniquebytes' => 50];
        $this->assertTrue(indicator_calculator::invariants_hold(new diagnostic_result($summary, ['material' => $material])));
        $short = ['logicalbytes' => 90] + $material;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, ['material' => $short])));
        $fewer = ['filecount' => 1] + $material;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, ['material' => $fewer])));
    }

    /**
     * The result object rejects missing or non-integer values.
     */
    public function test_result_requires_integers(): void {
        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary['logicalbytes'] = '100';
        $this->expectException(\coding_exception::class);
        new diagnostic_result($summary);
    }

    /**
     * Build a Q4 row (one large file record).
     *
     * @param int $id File id.
     * @param int $contextid Context.
     * @param string $component Component.
     * @param string $filearea Area.
     * @param string $contenthash Content hash.
     * @param int $filesize Size.
     * @return \stdClass
     */
    private static function large(
        int $id,
        int $contextid,
        string $component,
        string $filearea,
        string $contenthash,
        int $filesize
    ): \stdClass {
        return (object)compact('id', 'contextid', 'component', 'filearea', 'contenthash', 'filesize');
    }

    /**
     * Contexts with two activities, a block inside the first one and a block of the course.
     *
     * @return array
     */
    private static function activity_contexts(): array {
        return self::contexts() + [
            21 => (object)['id' => 21, 'contextlevel' => CONTEXT_MODULE, 'instanceid' => 8, 'path' => '/1/3/10/21'],
            30 => (object)['id' => 30, 'contextlevel' => CONTEXT_BLOCK, 'instanceid' => 4, 'path' => '/1/3/10/20/30'],
            40 => (object)['id' => 40, 'contextlevel' => CONTEXT_BLOCK, 'instanceid' => 5, 'path' => '/1/3/10/40'],
        ];
    }

    /**
     * T059: each context goes to the activity it belongs to by its path; the course and its blocks go to 0.
     */
    public function test_cmid_map(): void {
        $this->assertSame(
            [10 => 0, 20 => 7, 21 => 8, 30 => 7, 40 => 0],
            indicator_calculator::cmid_map(self::activity_contexts())
        );
    }

    /**
     * T059: rows by activity with volume by category, A as the sum of the activities, and D unique overall and by
     * activity; only large material files get a link, the largest first.
     */
    public function test_activities_and_large_files(): void {
        $mb = self::MB;
        $rows = [
            self::row(10, 'course', 'summary', 1, $mb),
            self::row(40, 'block_html', 'content', 1, 2 * $mb),
            self::row(20, 'mod_resource', 'content', 2, 120 * $mb, 2, 120 * $mb),
            self::row(30, 'block_html', 'content', 1, 3 * $mb),
            self::row(21, 'assignsubmission_file', 'submission_files', 2, 130 * $mb, 2, 130 * $mb),
            self::row(21, 'assignfeedback_file', 'feedback_files', 1, 4 * $mb),
            self::row(10, 'backup', 'course', 1, 400 * $mb, 1, 400 * $mb),
        ];
        $large = [
            self::large(101, 20, 'mod_resource', 'content', 'h1', 60 * $mb),
            self::large(102, 20, 'mod_resource', 'content', 'h1', 60 * $mb),
            self::large(103, 21, 'assignsubmission_file', 'submission_files', 'h2', 60 * $mb),
            self::large(104, 21, 'assignsubmission_file', 'submission_files', 'h3', 70 * $mb),
            self::large(105, 10, 'backup', 'course', 'h4', 400 * $mb),
        ];
        $result = (new indicator_calculator(50 * $mb))->calculate(self::activity_contexts(), $rows, [], [], $large);

        $this->assertSame([0, 7, 8], array_keys($result->activities));
        $this->assertSame([
            'filecount' => 2, 'logicalbytes' => 3 * $mb, 'materialbytes' => 3 * $mb, 'submissionbytes' => 0,
            'otherbytes' => 0, 'unclassifiedbytes' => 0, 'largecount' => 0, 'largebytes' => 0,
            'largeuniquecount' => 0, 'largeuniquebytes' => 0,
        ], $result->activities[0]);
        $this->assertSame([
            'filecount' => 3, 'logicalbytes' => 123 * $mb, 'materialbytes' => 123 * $mb, 'submissionbytes' => 0,
            'otherbytes' => 0, 'unclassifiedbytes' => 0, 'largecount' => 2, 'largebytes' => 120 * $mb,
            'largeuniquecount' => 1, 'largeuniquebytes' => 60 * $mb,
        ], $result->activities[7]);
        $this->assertSame([
            'filecount' => 3, 'logicalbytes' => 134 * $mb, 'materialbytes' => 0, 'submissionbytes' => 130 * $mb,
            'otherbytes' => 4 * $mb, 'unclassifiedbytes' => 0, 'largecount' => 2, 'largebytes' => 130 * $mb,
            'largeuniquecount' => 2, 'largeuniquebytes' => 130 * $mb,
        ], $result->activities[8]);
        $this->assertSame($result->summary['logicalbytes'], array_sum(array_column($result->activities, 'logicalbytes')));

        $this->assertSame(4, $result->summary['largecount']);
        $this->assertSame(250 * $mb, $result->summary['largebytes']);
        $this->assertSame(3, $result->summary['largeuniquecount']);
        $this->assertSame(190 * $mb, $result->summary['largeuniquebytes']);
        $this->assertSame(0, $result->summary['largelinkstruncated']);
        $this->assertSame([
            ['fileid' => 101, 'cmid' => 7, 'filesize' => 60 * $mb],
            ['fileid' => 102, 'cmid' => 7, 'filesize' => 60 * $mb],
        ], $result->largelinks);
        $this->assertTrue(indicator_calculator::invariants_hold($result));
    }

    /**
     * T059: above 1,000 large material files, only the 1,000 largest get a link; the totals keep every file.
     */
    public function test_large_links_truncated(): void {
        $mb = self::MB;
        $large = [];
        $bytes = 0;
        for ($i = 1; $i <= indicator_calculator::MAX_LARGE_LINKS + 1; $i++) {
            $large[] = self::large($i, 20, 'mod_resource', 'content', "h{$i}", 50 * $mb + $i);
            $bytes += 50 * $mb + $i;
        }
        $count = count($large);
        $rows = [self::row(20, 'mod_resource', 'content', $count, $bytes, $count, $bytes)];
        $result = (new indicator_calculator(50 * $mb))->calculate(self::contexts(), $rows, [], [], $large);

        $this->assertCount(indicator_calculator::MAX_LARGE_LINKS, $result->largelinks);
        $this->assertSame(1, $result->summary['largelinkstruncated']);
        $this->assertSame($count, $result->summary['largecount']);
        $this->assertSame($bytes, $result->summary['largebytes']);
        $this->assertSame($count, $result->summary['largeuniquecount']);
        $this->assertSame($count, $result->largelinks[0]['fileid']);
        $this->assertNotContains(1, array_column($result->largelinks, 'fileid'));
        $this->assertTrue(indicator_calculator::invariants_hold($result));
    }

    /**
     * T059: the activities must add up to A and the large-file uniques cannot exceed the large files.
     */
    public function test_activity_invariants(): void {
        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary = ['filecount' => 2, 'logicalbytes' => 100, 'largecount' => 1, 'largebytes' => 60] + $summary;
        $activity = array_fill_keys(diagnostic_result::ACTIVITY_FIELDS, 0);
        $activity = ['filecount' => 2, 'logicalbytes' => 100, 'materialbytes' => 100, 'largecount' => 1,
            'largebytes' => 60] + $activity;
        $this->assertTrue(indicator_calculator::invariants_hold(new diagnostic_result($summary, [], [5 => $activity])));

        $short = ['logicalbytes' => 90] + $activity;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, [], [5 => $short])));
        $nolarge = ['largecount' => 0, 'largebytes' => 0] + $activity;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, [], [5 => $nolarge])));
        $unique = ['largeuniquecount' => 2] + $activity;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($summary, [], [5 => $unique])));
        $broken = ['largeuniquecount' => 2] + $summary;
        $this->assertFalse(indicator_calculator::invariants_hold(new diagnostic_result($broken)));
    }
}
