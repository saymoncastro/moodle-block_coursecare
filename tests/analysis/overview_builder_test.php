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

use block_coursecare\local\analysis\diagnostic_state;
use block_coursecare\local\analysis\overview_builder;

/**
 * Pure tests of the course view shown on the overview page (data-model.md, contracts/overview-data.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\overview_builder
 */
final class overview_builder_test extends \basic_testcase {
    /** @var int Completion time of the results of these tests. */
    private const DONE = 1800000000;

    /**
     * A state with a result.
     *
     * @param string $state State constant.
     * @param int $total Logical storage.
     * @param bool $partial H marker.
     * @return diagnostic_state
     */
    private static function state(string $state, int $total = 1000, bool $partial = false): diagnostic_state {
        $hasresult = !in_array($state, [diagnostic_state::NOT_ANALYSED, diagnostic_state::QUEUED,
            diagnostic_state::RUNNING], true);
        return new diagnostic_state(
            $state,
            $hasresult,
            $partial,
            $hasresult ? $total : null,
            $hasresult ? self::DONE : null,
            $hasresult ? ($total > 0 ? 3 : 0) : null
        );
    }

    /**
     * A diagnostic record with the given summary values.
     *
     * @param array $values Values.
     * @return \stdClass
     */
    private static function record(array $values = []): \stdClass {
        return (object)($values + ['id' => 7, 'logicalbytes' => 1000, 'filecount' => 3, 'timecompleted' => self::DONE,
            'largethreshold' => 52428800, 'largecount' => 0, 'largebytes' => 0, 'largelinkstruncated' => 0,
            'uniquecount' => 3, 'uniquebytes' => 1000, 'unclassifiedcount' => 0, 'unclassifiedbytes' => 0, 'partial' => 0,
            'refinternalcount' => 0, 'refinternalbytes' => 0, 'refexternalcount' => 0, 'refexternalbytes' => 0,
            'refundeterminedcount' => 0, 'brcount' => 0, 'brbytes' => 0, 'bruniquecount' => 0, 'bruniquebytes' => 0]);
    }

    /**
     * share(): "less than 1%" for every positive share below 1%, the nearest whole number from 1% on.
     */
    public function test_share(): void {
        $this->assertSame(0, overview_builder::share(10, 0));
        $this->assertSame(0, overview_builder::share(0, 1000));
        $this->assertSame(overview_builder::LESS_THAN_ONE, overview_builder::share(1, 1000));
        $this->assertSame(overview_builder::LESS_THAN_ONE, overview_builder::share(5, 1000));
        $this->assertSame(overview_builder::LESS_THAN_ONE, overview_builder::share(99, 10000));
        $this->assertSame(1, overview_builder::share(100, 10000));
        $this->assertSame(1, overview_builder::share(140, 10000));
        $this->assertSame(2, overview_builder::share(150, 10000));
        $this->assertSame(38, overview_builder::share(380, 1000));
        $this->assertSame(100, overview_builder::share(1000, 1000));
    }

    /**
     * Without a result (A, B, C and F without a result), the view has no number and no section.
     */
    public function test_no_result_has_no_numbers(): void {
        foreach ([diagnostic_state::NOT_ANALYSED, diagnostic_state::QUEUED, diagnostic_state::RUNNING] as $name) {
            $view = (new overview_builder())->build(self::state($name), null, [], [], [], []);
            $this->assertSame($name, $view['state']);
            $this->assertFalse($view['hasresult']);
            foreach (['total', 'timecompleted', 'categories', 'activities', 'large', 'technical'] as $key) {
                $this->assertNull($view[$key], "{$name}: {$key}");
            }
        }
    }

    /**
     * With a result, the summary comes from the state and the record.
     */
    public function test_complete_summary(): void {
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE, 1000),
            self::record(),
            [],
            [],
            [],
            []
        );
        $this->assertTrue($view['hasresult']);
        $this->assertSame(1000, $view['total']);
        $this->assertSame(self::DONE, $view['timecompleted']);
        $this->assertFalse($view['outdated']);
        $this->assertFalse($view['failed']);
        $this->assertFalse($view['partial']);
    }
    /**
     * Category rows as diagnostic_store::get_categories() returns them.
     *
     * @param array $bytes Logical bytes by category.
     * @return array
     */
    private static function categories(array $bytes): array {
        $rows = [];
        foreach ($bytes as $category => $value) {
            $rows[$category] = (object)['category' => $category, 'filecount' => 1, 'logicalbytes' => $value,
                'uniquecount' => 1, 'uniquebytes' => $value];
        }
        return $rows;
    }

    /**
     * Activity rows as diagnostic_store::get_activities() returns them, with the fields that FR-030 forbids filled
     * with distinct large values, so that any use of them would show in the result.
     *
     * @param array $material Materials bytes by cmid.
     * @return array
     */
    private static function activities(array $material): array {
        $rows = [];
        foreach ($material as $cmid => $value) {
            $rows[$cmid] = (object)['cmid' => $cmid, 'filecount' => 900 + $cmid, 'logicalbytes' => $value + 7000000,
                'materialbytes' => $value, 'submissionbytes' => 5000000, 'otherbytes' => 2000000,
                'unclassifiedbytes' => 1, 'largecount' => 50, 'largebytes' => 80000000, 'largeuniquecount' => 50,
                'largeuniquebytes' => 80000000];
        }
        return $rows;
    }

    /**
     * Course structure as course_structure::get_activities() returns it.
     *
     * @param array $names Names by cmid; a name starting with "-" is an activity the user cannot see.
     * @return array
     */
    private static function structure(array $names): array {
        $rows = [];
        foreach ($names as $cmid => $name) {
            $hidden = str_starts_with($name, '-');
            $rows[$cmid] = (object)['name' => ltrim($name, '-'), 'url' => "https://example.com/mod/view.php?id={$cmid}",
                'uservisible' => !$hidden];
        }
        return $rows;
    }

    /**
     * Categories of the course: fixed order, logical bytes, share of the total, zero without a row; the sum is the
     * total; backups and recycle bin never in the list (CT-04).
     */
    public function test_categories(): void {
        $rows = self::categories(['submission' => 300, 'material' => 600, 'other' => 100, 'backup' => 5000,
            'recyclebin' => 70]);
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE, 1000),
            self::record(),
            $rows,
            [],
            [],
            []
        );
        $this->assertSame(['material', 'submission', 'other', 'unclassified'], array_column($view['categories'], 'key'));
        $this->assertSame([600, 300, 100, 0], array_column($view['categories'], 'bytes'));
        $this->assertSame([60, 30, 10, 0], array_column($view['categories'], 'share'));
        $this->assertSame($view['total'], array_sum(array_column($view['categories'], 'bytes')));
    }

    /**
     * Materials per activity (FR-013 to FR-015, FR-030; CT-07 to CT-09, CT-14).
     */
    public function test_materials_by_activity(): void {
        // 12 visible activities with materials (cmid 11 to 22), one without materials (30), one the user cannot see
        // (40), one removed after the analysis (50) and the course level (0).
        $material = [0 => 40, 30 => 0, 40 => 25, 50 => 15];
        $names = [30 => 'Only submissions', 40 => '-Hidden reading'];
        for ($cmid = 11; $cmid <= 22; $cmid++) {
            $material[$cmid] = $cmid === 13 ? 100 : 10 * ($cmid - 10);
            $names[$cmid] = $cmid === 12 ? 'B reading' : "Activity {$cmid}";
        }
        $names[13] = 'A reading';
        $total = array_sum($material);
        $rows = self::categories(['material' => $total, 'submission' => 5000000]);
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE, $total + 5000000),
            self::record(['logicalbytes' => $total + 5000000]),
            $rows,
            self::activities($material),
            [],
            self::structure($names)
        );
        $list = $view['activities'];

        // Only activities with materials; the first 10 by materials volume, ties by name; the rest apart.
        $this->assertCount(10, $list['first']);
        $this->assertCount(2, $list['rest']);
        $first = array_column($list['first'], 'name');
        // Both "A reading" and "Activity 20" have 100 bytes of materials: the name breaks the tie.
        $this->assertSame(['Activity 22', 'Activity 21', 'A reading', 'Activity 20', 'Activity 19', 'Activity 18',
            'Activity 17', 'Activity 16', 'Activity 15', 'Activity 14'], $first);
        $this->assertSame(['B reading', 'Activity 11'], array_column($list['rest'], 'name'));
        $all = array_merge($list['first'], $list['rest']);
        $this->assertNotContains('Only submissions', array_column($all, 'name'));
        $this->assertSame(100, $list['first'][2]['bytes']);
        $this->assertSame(overview_builder::share(100, $total), $list['first'][2]['share']);
        $this->assertSame('https://example.com/mod/view.php?id=13', $list['first'][2]['url']);

        // FR-030: nothing per activity but the materials volume.
        foreach ($all as $item) {
            $this->assertSame(['cmid', 'name', 'url', 'bytes', 'share'], array_keys($item));
            $this->assertSame($material[$item['cmid']], $item['bytes']);
        }

        // Groups without names.
        $this->assertSame(['bytes' => 40, 'share' => overview_builder::share(40, $total)], $list['courselevel']);
        $this->assertSame(['bytes' => 25, 'share' => overview_builder::share(25, $total), 'count' => 1], $list['others']);
        $this->assertSame(['bytes' => 15, 'share' => overview_builder::share(15, $total), 'count' => 1], $list['removed']);

        // The materials of items and groups add up to the materials category.
        $sum = array_sum(array_column($all, 'bytes')) + $list['courselevel']['bytes'] + $list['others']['bytes']
            + $list['removed']['bytes'];
        $this->assertSame($total, $sum);
        $this->assertSame(12, $list['count']);
    }

    /**
     * An activity without a page (a text and media area) has no link; no group appears when it is empty.
     */
    public function test_activity_without_link_and_empty_groups(): void {
        $structure = self::structure([11 => 'Welcome']);
        $structure[11]->url = null;
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE, 50),
            self::record(),
            self::categories(['material' => 50]),
            self::activities([11 => 50]),
            [],
            $structure
        );
        $this->assertNull($view['activities']['first'][0]['url']);
        $this->assertNull($view['activities']['courselevel']);
        $this->assertNull($view['activities']['others']);
        $this->assertNull($view['activities']['removed']);
        $this->assertSame([], $view['activities']['rest']);
    }
    /**
     * The sections of each state (contracts/overview-page.md, "Estados"; FR-020, FR-021; CT-05).
     */
    public function test_states(): void {
        $rows = self::categories(['material' => 1000]);
        $build = fn(diagnostic_state $state, array $record = []): array => (new overview_builder())->build(
            $state,
            self::record($record),
            $rows,
            self::activities([11 => 1000]),
            [],
            self::structure([11 => 'Reading'])
        );

        foreach ([diagnostic_state::COMPLETE, diagnostic_state::OUTDATED, diagnostic_state::FAILED] as $name) {
            $view = $build(self::state($name));
            $this->assertNotNull($view['categories'], $name);
            $this->assertNotNull($view['activities'], $name);
            $this->assertNotNull($view['technical'], $name);
            $this->assertSame($name === diagnostic_state::OUTDATED, $view['outdated'], $name);
            $this->assertSame($name === diagnostic_state::FAILED, $view['failed'], $name);
        }

        // G: summary and technical details only.
        $view = $build(self::state(diagnostic_state::NO_FILES, 0), ['logicalbytes' => 0, 'filecount' => 0]);
        $this->assertSame(0, $view['total']);
        $this->assertNull($view['categories']);
        $this->assertNull($view['activities']);
        $this->assertNull($view['large']);
        $this->assertNotNull($view['technical']);

        // H: the partial marker and the share of what could not be classified.
        $view = $build(self::state(diagnostic_state::COMPLETE, 1000, true), ['partial' => 1, 'unclassifiedcount' => 2,
            'unclassifiedbytes' => 120]);
        $this->assertTrue($view['partial']);
        $this->assertSame(['count' => 2, 'bytes' => 120, 'share' => 12], $view['technical']['coverage']);

        // A pending new analysis keeps showing the result (the state resolver already gives D, E or F).
        $view = $build(self::state(diagnostic_state::COMPLETE), ['requeststatus' => 1]);
        $this->assertSame(1000, $view['total']);
        $this->assertNotNull($view['categories']);
    }

    /**
     * Technical details: unique content, references, backups and recycle bin, coverage and threshold, never added
     * to the total (FR-017, FR-018; CT-11).
     */
    public function test_technical(): void {
        $rows = self::categories(['material' => 1000, 'backup' => 4000, 'recyclebin' => 300]);
        $rows['backup']->filecount = 2;
        $rows['recyclebin']->filecount = 1;
        $record = self::record(['uniquecount' => 2, 'uniquebytes' => 800, 'refinternalcount' => 3,
            'refinternalbytes' => 900, 'refexternalcount' => 1, 'refexternalbytes' => 5000, 'refundeterminedcount' => 4,
            'bruniquebytes' => 4100, 'largethreshold' => 1048576]);
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE, 1000),
            $record,
            $rows,
            [],
            [],
            []
        );
        $technical = $view['technical'];
        $this->assertSame(['count' => 2, 'bytes' => 800], $technical['unique']);
        $this->assertSame(['count' => 3, 'bytes' => 900], $technical['references']['internal']);
        $this->assertSame(['count' => 1, 'bytes' => 5000], $technical['references']['external']);
        $this->assertSame(['count' => 4], $technical['references']['undetermined']);
        $this->assertSame(['count' => 2, 'bytes' => 4000], $technical['copies']['backup']);
        $this->assertSame(['count' => 1, 'bytes' => 300], $technical['copies']['recyclebin']);
        $this->assertSame(4100, $technical['copies']['uniquebytes']);
        $this->assertSame(1048576, $technical['threshold']);
        $this->assertSame(['count' => 0, 'bytes' => 0, 'share' => 0], $technical['coverage']);
        // Nothing of it enters the total or the categories.
        $this->assertSame(1000, $view['total']);
        $this->assertNotContains('backup', array_column($view['categories'], 'key'));
    }
    /**
     * Large files: totals of the course from the summary, materials per activity only from the links, ordered by
     * size, with hidden and removed activities grouped; never the large totals of an activity row (FR-033, FR-030;
     * CT-10, CT-22).
     */
    public function test_large(): void {
        $record = self::record(['largecount' => 5, 'largebytes' => 500, 'largethreshold' => 100,
            'largelinkstruncated' => 0]);
        $links = [
            11 => (object)['count' => 1, 'bytes' => 120],
            12 => (object)['count' => 2, 'bytes' => 260],
            40 => (object)['count' => 1, 'bytes' => 110],
            50 => (object)['count' => 1, 'bytes' => 101],
            0 => (object)['count' => 1, 'bytes' => 150],
        ];
        $structure = self::structure([11 => 'Video', 12 => 'Slides', 40 => '-Hidden', 13 => 'Quiet']);
        // Activity rows carry large totals that mix in submissions (activities()); they must not appear.
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE),
            $record,
            self::categories(['material' => 1000]),
            self::activities([11 => 300, 12 => 400, 13 => 300]),
            $links,
            $structure
        );
        $large = $view['large'];
        $this->assertSame(5, $large['count']);
        $this->assertSame(500, $large['bytes']);
        $this->assertSame(100, $large['threshold']);
        $this->assertFalse($large['truncated']);
        $this->assertSame(['Slides', 'Video'], array_column($large['materials'], 'name'));
        $this->assertSame(
            [['count' => 2, 'bytes' => 260], ['count' => 1, 'bytes' => 120]],
            array_map(
                fn(array $item): array => ['count' => $item['count'], 'bytes' => $item['bytes']],
                $large['materials']
            )
        );
        $this->assertSame(['count' => 1, 'bytes' => 150], $large['courselevel']);
        $this->assertSame(['count' => 1, 'bytes' => 110], $large['others']);
        $this->assertSame(['count' => 1, 'bytes' => 101], $large['removed']);
        foreach ($large['materials'] as $item) {
            $this->assertNotSame(50, $item['count']);
            $this->assertNotSame(80000000, $item['bytes']);
        }

        // Truncated links, and no large file at all.
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE),
            self::record(['largecount' => 2000, 'largebytes' => 9000, 'largelinkstruncated' => 1]),
            self::categories(['material' => 1000]),
            [],
            [],
            []
        );
        $this->assertTrue($view['large']['truncated']);
        $view = (new overview_builder())->build(
            self::state(diagnostic_state::COMPLETE),
            self::record(),
            self::categories(['material' => 1000]),
            [],
            [],
            []
        );
        $this->assertSame(0, $view['large']['count']);
        $this->assertSame([], $view['large']['materials']);
        $this->assertNull($view['large']['courselevel']);
    }
}
