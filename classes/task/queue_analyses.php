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

namespace block_coursecare\task;

use block_coursecare\local\analysis\due_policy;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\collector\eligibility;

/**
 * Hourly task: removes orphan diagnostics, recovers stuck requests and queues the courses that are due.
 *
 * It never analyses a course itself: it queues at most BATCH ad hoc tasks per run, so the work is spread over
 * the day and over the cron runners (research R8). The selection is date-based, so a late cron only delays it.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_analyses extends \core\task\scheduled_task {
    /** @var int Maximum courses queued, and orphan diagnostics removed, per run. */
    public const BATCH = 500;

    /** @var int Candidates read per run, to skip courses still waiting after a failure. */
    public const CANDIDATES = 2000;

    /** @var int Seconds after which a running analysis is considered interrupted. */
    public const RUNNING_TIMEOUT = 2 * HOURSECS;

    /** @var int Seconds after which a queued request is considered lost. */
    public const QUEUED_TIMEOUT = DAYSECS;

    /** @var string Failure code of an interrupted analysis. */
    public const REASON_INTERRUPTED = 'interrupted';

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:queueanalyses', 'block_coursecare');
    }

    /**
     * Run the task.
     */
    public function execute() {
        $stats = $this->run(time());
        mtrace(sprintf(
            'block_coursecare: %d orphan diagnostic(s) removed, %d interrupted, %d released, %d queued',
            $stats['orphans'],
            $stats['interrupted'],
            $stats['released'],
            $stats['queued']
        ));
    }

    /**
     * Do the work for a given time (testable without the cron).
     *
     * @param int $now Current time.
     * @param int $batch Maximum courses queued and orphans removed in this run.
     * @return array{orphans: int, interrupted: int, released: int, queued: int}
     */
    public function run(int $now, int $batch = self::BATCH): array {
        $store = new diagnostic_store();
        $eligibility = new eligibility();

        $orphans = $store->get_orphan_courseids($eligibility, $batch);
        foreach ($orphans as $courseid) {
            $store->delete_for_course($courseid);
        }

        $stuck = $store->recover_stuck($now, self::RUNNING_TIMEOUT, self::QUEUED_TIMEOUT, self::REASON_INTERRUPTED);

        $interval = (int)get_config('block_coursecare', 'reanalysisinterval');
        $interval = $interval > 0 ? $interval : DAYSECS;
        $queued = 0;
        foreach ($store->get_request_candidates($eligibility, self::CANDIDATES) as $candidate) {
            if ($queued >= $batch) {
                break;
            }
            $record = $candidate->diagid === null ? null : $candidate;
            if (due_policy::is_due($record, $now, $interval) && analyse_course::request((int)$candidate->courseid, $now)) {
                $queued++;
            }
        }

        return ['orphans' => count($orphans)] + $stuck + ['queued' => $queued];
    }
}
