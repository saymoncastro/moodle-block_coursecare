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

namespace block_coursecare\recommendation;

use block_coursecare\local\recommendation\large_materials;

/**
 * Rule of the suggestion S1, large course materials (Feature 004, contracts/recommendation-rule.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\recommendation\large_materials
 */
final class large_materials_test extends \basic_testcase {
    /** Large-file threshold of the result in the examples (bytes). */
    private const THRESHOLD = 1048576;

    /**
     * The "large" part of the overview view, as overview_builder builds it.
     *
     * @param array $materials Visible activities: [cmid, name, bytes, count].
     * @param array $groups Values of courselevel, others and removed: [count, bytes] or null.
     * @param array $summary Course summary count and bytes (all categories).
     * @return array
     */
    private static function large(array $materials, array $groups = [], array $summary = [0, 0]): array {
        $rows = [];
        foreach ($materials as [$cmid, $name, $bytes, $count]) {
            $rows[] = ['cmid' => $cmid, 'name' => $name, 'url' => "https://example.com/mod/page/view.php?id={$cmid}",
                'count' => $count, 'bytes' => $bytes];
        }
        $group = fn(?array $values): ?array => $values === null ? null : ['count' => $values[0], 'bytes' => $values[1]];
        return [
            'count' => $summary[0],
            'bytes' => $summary[1],
            'threshold' => self::THRESHOLD,
            'truncated' => false,
            'materials' => $rows,
            'courselevel' => $group($groups['courselevel'] ?? null),
            'others' => $group($groups['others'] ?? null),
            'removed' => $group($groups['removed'] ?? null),
        ];
    }

    /**
     * Names of the places, in order ("courselevel" for the course level).
     *
     * @param array $suggestion Result of evaluate().
     * @return array
     */
    private static function places(array $suggestion): array {
        return array_map(
            fn(array $place): string => $place['kind'] === 'courselevel' ? 'courselevel' : $place['name'],
            $suggestion['places']
        );
    }

    /**
     * No large material link, or no result: no suggestion. Large submissions in the course summary, without any
     * large material, do not make a suggestion either.
     */
    public function test_no_suggestion(): void {
        $this->assertNull(large_materials::evaluate(null));
        $this->assertNull(large_materials::evaluate(self::large([])));
        $this->assertNull(large_materials::evaluate(self::large([], [], [4, 9 * self::THRESHOLD])));
    }

    /**
     * Count and size add up every large material link, named or grouped, and never use the course summary.
     */
    public function test_totals(): void {
        $large = self::large(
            [[11, 'Lab video', 3000000, 2], [12, 'Slides', 2000000, 1]],
            ['courselevel' => [1, 1500000], 'others' => [2, 4000000], 'removed' => [1, 1100000]],
            [20, 999999999]
        );
        $suggestion = large_materials::evaluate($large);
        $this->assertSame(7, $suggestion['count']);
        $this->assertSame(11600000, $suggestion['bytes']);
        $this->assertSame(self::THRESHOLD, $suggestion['threshold']);
        $this->assertFalse($suggestion['truncated']);
    }

    /**
     * At most three named places, the largest first; ties between activities by name; the course level is a place
     * and comes after activities of the same size; hidden and removed activities only count in "more".
     */
    public function test_places(): void {
        $large = self::large(
            [[11, 'beta', 2000000, 1], [12, 'Alpha', 2000000, 1], [13, 'Small', 1100000, 1], [14, 'Big', 5000000, 1]],
            ['courselevel' => [1, 2000000], 'others' => [3, 9000000], 'removed' => [1, 8000000]]
        );
        $suggestion = large_materials::evaluate($large);
        $this->assertSame(['Big', 'Alpha', 'beta'], self::places($suggestion));
        // Not shown: the course level and Small (2 named places), plus the hidden and the removed groups.
        $this->assertSame(4, $suggestion['more']);
        $this->assertSame(['kind' => 'activity', 'cmid' => 14, 'name' => 'Big',
            'url' => 'https://example.com/mod/page/view.php?id=14', 'bytes' => 5000000], $suggestion['places'][0]);

        // The course level as a place, after the activities of the same size.
        $suggestion = large_materials::evaluate(self::large([[11, 'Notes', 2000000, 1]], ['courselevel' => [1, 2000000]]));
        $this->assertSame(['Notes', 'courselevel'], self::places($suggestion));
        $this->assertSame(['kind' => 'courselevel', 'bytes' => 2000000], $suggestion['places'][1]);
        $this->assertSame(0, $suggestion['more']);

        // Only hidden or removed activities: a suggestion without named places.
        $suggestion = large_materials::evaluate(self::large([], ['others' => [1, 2000000], 'removed' => [1, 3000000]]));
        $this->assertSame([], $suggestion['places']);
        $this->assertSame(2, $suggestion['more']);
        $this->assertSame(2, $suggestion['count']);
    }

    /**
     * The truncation of the links (1,000 largest materials) is passed on.
     */
    public function test_truncated(): void {
        $large = self::large([[11, 'Lab video', 3000000, 1]]);
        $large['truncated'] = true;
        $this->assertTrue(large_materials::evaluate($large)['truncated']);
    }
}
