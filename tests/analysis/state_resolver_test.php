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
use block_coursecare\local\analysis\state_resolver;

/**
 * Pure tests of the displayed state of a diagnostic (FR-045 to FR-048, data-model.md), with a fixed clock.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\state_resolver
 * @covers     \block_coursecare\local\analysis\diagnostic_state
 */
final class state_resolver_test extends \basic_testcase {
    /** @var int Fixed current time. */
    private const NOW = 2000000000;

    /** @var int Out-of-date limit (72 hours). */
    private const STALE = 259200;

    /**
     * A diagnostic record with defaults for an existing completed result.
     *
     * @param array $values Values to override.
     * @return \stdClass
     */
    private static function record(array $values = []): \stdClass {
        return (object)($values + [
            'requeststatus' => 0, 'timerequested' => self::NOW - 7200, 'timecompleted' => self::NOW - 3600,
            'lastattemptfailed' => 0, 'failurecount' => 0, 'filecount' => 3, 'logicalbytes' => 6291456, 'partial' => 0,
        ]);
    }

    /**
     * Resolve with the fixed clock.
     *
     * @param \stdClass|null $record Record.
     * @return diagnostic_state
     */
    private static function resolve(?\stdClass $record): diagnostic_state {
        return state_resolver::resolve($record, self::NOW, self::STALE);
    }

    /**
     * A: no record at all, or a record without result and without request.
     */
    public function test_not_analysed(): void {
        $this->assertSame(diagnostic_state::NOT_ANALYSED, self::resolve(null)->state);
        $record = self::record(['timecompleted' => null, 'filecount' => null, 'logicalbytes' => null]);
        $state = self::resolve($record);
        $this->assertSame(diagnostic_state::NOT_ANALYSED, $state->state);
        $this->assertFalse($state->hasresult);
        $this->assertNull($state->logicalbytes);
    }

    /**
     * B and C: queued or running, without a previous result.
     */
    public function test_queued_and_running_without_result(): void {
        $base = ['timecompleted' => null, 'filecount' => null, 'logicalbytes' => null];
        $this->assertSame(diagnostic_state::QUEUED, self::resolve(self::record(['requeststatus' => 1] + $base))->state);
        $this->assertSame(diagnostic_state::RUNNING, self::resolve(self::record(['requeststatus' => 2] + $base))->state);
    }

    /**
     * D: a completed result within the out-of-date limit, with its value and date.
     */
    public function test_complete(): void {
        $state = self::resolve(self::record());
        $this->assertSame(diagnostic_state::COMPLETE, $state->state);
        $this->assertTrue($state->hasresult);
        $this->assertSame(6291456, $state->logicalbytes);
        $this->assertSame(self::NOW - 3600, $state->timecompleted);
        $this->assertFalse($state->partial);
    }

    /**
     * G: a completed analysis that found no file; zero only appears here.
     */
    public function test_no_files(): void {
        $state = self::resolve(self::record(['filecount' => 0, 'logicalbytes' => 0]));
        $this->assertSame(diagnostic_state::NO_FILES, $state->state);
        $this->assertSame(0, $state->logicalbytes);
    }

    /**
     * FR-046: a pending new analysis keeps showing the existing result.
     */
    public function test_pending_request_keeps_result(): void {
        $this->assertSame(diagnostic_state::COMPLETE, self::resolve(self::record(['requeststatus' => 1]))->state);
        $this->assertSame(diagnostic_state::COMPLETE, self::resolve(self::record(['requeststatus' => 2]))->state);
    }

    /**
     * H: the partial marker is reported on top of the state.
     */
    public function test_partial_marker(): void {
        $state = self::resolve(self::record(['partial' => 1]));
        $this->assertSame(diagnostic_state::COMPLETE, $state->state);
        $this->assertTrue($state->partial);
    }

    /**
     * E: a result older than the out-of-date limit keeps its value and date (CT-15, CT-23).
     */
    public function test_outdated(): void {
        $state = self::resolve(self::record(['timecompleted' => self::NOW - self::STALE - 1]));
        $this->assertSame(diagnostic_state::OUTDATED, $state->state);
        $this->assertSame(6291456, $state->logicalbytes);
        $this->assertSame(self::NOW - self::STALE - 1, $state->timecompleted);
        $atlimit = self::resolve(self::record(['timecompleted' => self::NOW - self::STALE]));
        $this->assertSame(diagnostic_state::COMPLETE, $atlimit->state);
        // An outdated analysis without files is outdated too; the line still says that no file was found.
        $state = self::resolve(self::record(['timecompleted' => self::NOW - self::STALE - 1, 'filecount' => 0,
            'logicalbytes' => 0]));
        $this->assertSame(diagnostic_state::OUTDATED, $state->state);
        $this->assertSame(0, $state->filecount);
    }

    /**
     * F: a failed last attempt with a previous result keeps the result; without result it reads as A.
     */
    public function test_failed(): void {
        $state = self::resolve(self::record(['lastattemptfailed' => 1, 'timeattempted' => self::NOW]));
        $this->assertSame(diagnostic_state::FAILED, $state->state);
        $this->assertSame(6291456, $state->logicalbytes);

        $state = self::resolve(self::record(['lastattemptfailed' => 1, 'timecompleted' => null, 'filecount' => null,
            'logicalbytes' => null]));
        $this->assertSame(diagnostic_state::NOT_ANALYSED, $state->state);
        $this->assertFalse($state->hasresult);
    }

    /**
     * Precedence with a result: F before E before G or D.
     */
    public function test_precedence(): void {
        $old = self::NOW - self::STALE - 1;
        $this->assertSame(
            diagnostic_state::FAILED,
            self::resolve(self::record(['lastattemptfailed' => 1, 'timecompleted' => $old]))->state
        );
        $this->assertSame(
            diagnostic_state::OUTDATED,
            self::resolve(self::record(['timecompleted' => $old, 'filecount' => 0, 'logicalbytes' => 0]))->state
        );
        $this->assertSame(
            diagnostic_state::NO_FILES,
            self::resolve(self::record(['filecount' => 0, 'logicalbytes' => 0]))->state
        );
    }
}
