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

use block_coursecare\local\analysis\history_view;

/**
 * Pure tests of the view of the history (Feature 006, contracts/history-view.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\history_view
 */
final class history_view_test extends \advanced_testcase {
    /**
     * A point on a day of the site timezone.
     *
     * @param int $day Day of October 2026.
     * @param int $bytes Logical bytes.
     * @param int $method Method version.
     * @return \stdClass
     */
    private static function point(int $day, int $bytes, int $method = 1): \stdClass {
        $timeday = make_timestamp(2026, 10, $day, 0, 0, 0, 'America/Sao_Paulo');
        return (object)['timeday' => $timeday, 'timecompleted' => $timeday + 9 * HOURSECS, 'methodversion' => $method,
            'logicalbytes' => $bytes, 'partial' => 0];
    }

    /**
     * No point, one point, and two points with each kind of change.
     */
    public function test_change(): void {
        $this->assertNull(history_view::build([]));

        $view = history_view::build([self::point(1, 100)]);
        $this->assertSame('first', $view['change']);
        $this->assertNull($view['previous']);
        $this->assertSame([100], $view['days']);

        $cases = [[100, 150, 'increased', 50], [150, 140, 'decreased', 10], [140, 140, 'unchanged', 0]];
        foreach ($cases as [$before, $after, $change, $delta]) {
            $view = history_view::build([self::point(1, $before), self::point(2, $after)]);
            $this->assertSame($change, $view['change']);
            $this->assertSame($delta, $view['delta']);
            $this->assertSame($after, $view['latest']['logicalbytes']);
            $this->assertSame($before, $view['previous']['logicalbytes']);
        }
    }

    /**
     * Measurements of different methods are not compared.
     */
    public function test_method_changed(): void {
        $view = history_view::build([self::point(1, 100, 1), self::point(2, 900, 2)]);
        $this->assertSame('methodchanged', $view['change']);
        $this->assertSame(0, $view['delta']);
        $this->assertSame(900, $view['methodchange']['logicalbytes']);

        // An earlier change is still reported, while the two latest measurements are compared.
        $view = history_view::build([self::point(1, 100, 1), self::point(2, 900, 2), self::point(3, 950, 2)]);
        $this->assertSame('increased', $view['change']);
        $this->assertSame(900, $view['methodchange']['logicalbytes']);
        $this->assertNull(history_view::build([self::point(1, 100), self::point(2, 200)])['methodchange']);
    }

    /**
     * Days without a measurement have no value, also across the change of daylight saving time, and the points come
     * latest first.
     */
    public function test_days_and_order(): void {
        $view = history_view::build([self::point(1, 100), self::point(8, 200), self::point(9, 210)]);
        $this->assertSame([100, null, null, null, null, null, null, 200, 210], $view['days']);
        $this->assertSame([210, 200, 100], array_column($view['points'], 'logicalbytes'));

        // Daylight saving time began in São Paulo on 2018-11-04 (a day of 23 hours); offsets stay whole days.
        $before = make_timestamp(2018, 11, 3, 0, 0, 0, 'America/Sao_Paulo');
        $after = make_timestamp(2018, 11, 6, 0, 0, 0, 'America/Sao_Paulo');
        $view = history_view::build([
            ['timeday' => $before, 'methodversion' => 1, 'logicalbytes' => 1],
            ['timeday' => $after, 'methodversion' => 1, 'logicalbytes' => 2],
        ]);
        $this->assertSame([1, null, null, 2], $view['days']);
    }

    /**
     * Large values stay exact integers.
     */
    public function test_large_values(): void {
        $tb = 1099511627776;
        $view = history_view::build([self::point(1, 3 * $tb), self::point(2, 5 * $tb)]);
        $this->assertSame(2 * $tb, $view['delta']);
        $this->assertIsInt($view['delta']);
    }
}
