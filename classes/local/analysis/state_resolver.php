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
 * Resolves the displayed state of a diagnostic from its record, the clock and the out-of-date limit.
 *
 * Pure: the state is computed when it is read, so no write is needed for a result to become out of date
 * (data-model.md, "Estados"). Missing data is never shown as zero (FR-047).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_resolver {
    /**
     * Resolve the state.
     *
     * @param \stdClass|null $record Diagnostic record, or null if the course has none.
     * @param int $now Current time.
     * @param int $stalenessseconds Out-of-date limit in seconds.
     * @return diagnostic_state
     */
    public static function resolve(?\stdClass $record, int $now, int $stalenessseconds): diagnostic_state {
        if ($record === null) {
            return new diagnostic_state(diagnostic_state::NOT_ANALYSED, false, false, null, null);
        }
        $hasresult = $record->timecompleted !== null && $record->logicalbytes !== null;
        if (!$hasresult) {
            $state = match ((int)$record->requeststatus) {
                diagnostic_state::REQUEST_QUEUED => diagnostic_state::QUEUED,
                diagnostic_state::REQUEST_RUNNING => diagnostic_state::RUNNING,
                default => diagnostic_state::NOT_ANALYSED,
            };
            return new diagnostic_state($state, false, false, null, null);
        }

        $state = (int)$record->filecount === 0 ? diagnostic_state::NO_FILES : diagnostic_state::COMPLETE;
        return new diagnostic_state(
            $state,
            true,
            (int)($record->partial ?? 0) === 1,
            (int)$record->logicalbytes,
            (int)$record->timecompleted
        );
    }
}
