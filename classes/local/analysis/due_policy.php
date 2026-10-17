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

namespace block_coursecare\local\analysis;

/**
 * Decides whether an eligible course is due for a new analysis (FR-037, FR-039a; contracts/diagnostic-api.md).
 *
 * Pure: it only reads the diagnostic record, the clock and the reanalysis interval.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class due_policy {
    /** @var int From this number of consecutive failures on, a course is retried only at the reanalysis interval. */
    public const FAILURES_BEFORE_INTERVAL = 5;

    /** @var int Longest wait after a failure, in hours, before the interval rule applies. */
    public const MAX_BACKOFF_HOURS = 24;

    /**
     * Whether a course is due.
     *
     * @param \stdClass|null $record Diagnostic record of the course, or null if it has none.
     * @param int $now Current time.
     * @param int $interval Reanalysis interval in seconds.
     * @return bool
     */
    public static function is_due(?\stdClass $record, int $now, int $interval): bool {
        if ($record === null) {
            return true;
        }
        if ((int)$record->requeststatus !== diagnostic_state::REQUEST_NONE) {
            return false;
        }
        if ((int)$record->lastattemptfailed === 1) {
            return (int)$record->timeattempted <= $now - self::retry_wait((int)$record->failurecount, $interval);
        }
        if ($record->timecompleted === null || (int)$record->methodversion !== indicator_calculator::METHOD_VERSION) {
            return true;
        }
        return (int)$record->timecompleted <= $now - $interval;
    }

    /**
     * Wait after consecutive failures: 2^(n-1) hours, at most 24 hours; from the 5th failure on, the interval.
     *
     * @param int $failures Consecutive failures (at least 1).
     * @param int $interval Reanalysis interval in seconds.
     * @return int Seconds.
     */
    public static function retry_wait(int $failures, int $interval): int {
        if ($failures >= self::FAILURES_BEFORE_INTERVAL) {
            return $interval;
        }
        $hours = min(2 ** max(0, $failures - 1), self::MAX_BACKOFF_HOURS);
        return $hours * HOURSECS;
    }
}
