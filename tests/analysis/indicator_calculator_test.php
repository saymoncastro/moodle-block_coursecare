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
        $this->assertArrayNotHasKey('material', $result->categories);
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
}
