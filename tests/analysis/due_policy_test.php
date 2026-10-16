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

use block_coursecare\local\analysis\due_policy;
use block_coursecare\local\analysis\indicator_calculator;

/**
 * Pure tests of the rule that decides when a course is due (FR-037, FR-039a), with a fixed clock.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\due_policy
 */
final class due_policy_test extends \basic_testcase {
    /** @var int Fixed current time. */
    private const NOW = 2000000000;

    /**
     * A record without pending request, completed one hour ago, current method.
     *
     * @param array $values Overrides.
     * @return \stdClass
     */
    private static function record(array $values = []): \stdClass {
        return (object)($values + [
            'requeststatus' => 0, 'timeattempted' => self::NOW - HOURSECS, 'timecompleted' => self::NOW - HOURSECS,
            'lastattemptfailed' => 0, 'failurecount' => 0, 'methodversion' => indicator_calculator::METHOD_VERSION,
        ]);
    }

    /**
     * Courses without record, with an old result or an old method are due; pending or recent ones are not.
     */
    public function test_due_without_failure(): void {
        $this->assertTrue(due_policy::is_due(null, self::NOW, DAYSECS));
        $this->assertFalse(due_policy::is_due(self::record(), self::NOW, DAYSECS));
        $old = self::NOW - DAYSECS - 1;
        $this->assertTrue(due_policy::is_due(self::record(['timecompleted' => $old]), self::NOW, DAYSECS));
        $this->assertTrue(due_policy::is_due(self::record(['methodversion' => 0]), self::NOW, DAYSECS));
        $this->assertTrue(due_policy::is_due(self::record(['timecompleted' => null]), self::NOW, DAYSECS));
        $this->assertFalse(due_policy::is_due(self::record(['requeststatus' => 1, 'timecompleted' => $old]), self::NOW, DAYSECS));
        $this->assertFalse(due_policy::is_due(self::record(['requeststatus' => 2, 'timecompleted' => $old]), self::NOW, DAYSECS));
    }

    /**
     * Bounded retry: 1 h after one failure, 4 h after three, the interval from the fifth on.
     */
    public function test_retry_wait_after_failures(): void {
        $failed = fn(int $failures, int $ago): \stdClass => self::record([
            'lastattemptfailed' => 1, 'failurecount' => $failures, 'timeattempted' => self::NOW - $ago,
        ]);
        $this->assertFalse(due_policy::is_due($failed(1, HOURSECS - 1), self::NOW, DAYSECS));
        $this->assertTrue(due_policy::is_due($failed(1, HOURSECS), self::NOW, DAYSECS));
        $this->assertFalse(due_policy::is_due($failed(3, 4 * HOURSECS - 1), self::NOW, DAYSECS));
        $this->assertTrue(due_policy::is_due($failed(3, 4 * HOURSECS), self::NOW, DAYSECS));
        $this->assertFalse(due_policy::is_due($failed(5, DAYSECS - 1), self::NOW, 2 * DAYSECS));
        $this->assertFalse(due_policy::is_due($failed(5, 2 * DAYSECS - 1), self::NOW, 2 * DAYSECS));
        $this->assertTrue(due_policy::is_due($failed(5, 2 * DAYSECS), self::NOW, 2 * DAYSECS));
        $this->assertSame(8 * HOURSECS, due_policy::retry_wait(4, 100 * DAYSECS));
    }
}
