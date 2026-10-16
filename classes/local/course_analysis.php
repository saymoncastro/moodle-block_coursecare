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

namespace block_coursecare\local;

use block_coursecare\local\analysis\indicator_calculator;
use block_coursecare\local\collector\course_files;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\collector\eligibility;

/**
 * Runs the analysis of one course: lock, eligibility, freshness, collection, calculation and atomic storage.
 *
 * Orchestration only: the business rules live in the analysis layer and every database access in the collector.
 * A failure never removes the previous result and is never thrown to the caller (contracts/diagnostic-api.md).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_analysis {
    /** @var string Outcome: result stored. */
    public const DONE = 'done';
    /** @var string Outcome: another process holds the course lock. */
    public const LOCKED = 'locked';
    /** @var string Outcome: the course is missing or has no block; its diagnostic was removed. */
    public const INELIGIBLE = 'ineligible';
    /** @var string Outcome: a result newer than the last request already exists. */
    public const FRESH = 'fresh';
    /** @var string Outcome: the attempt failed; the previous result was kept. */
    public const FAILED = 'failed';

    /** @var string Failure code when the invariants fail twice. */
    public const REASON_INCONSISTENT = 'inconsistent';

    /** @var int Lifetime of the course lock, in seconds. */
    public const LOCK_LIFETIME = 2 * HOURSECS;

    /** @var int Bytes in one MB of the large-file threshold setting. */
    public const MB = 1048576;

    /** @var course_files Metadata collector. */
    private course_files $collector;

    /** @var diagnostic_store Result store. */
    private diagnostic_store $store;

    /** @var eligibility Eligibility policy. */
    private eligibility $eligibility;

    /** @var string|null Short failure code of the last run, if it failed. */
    private ?string $lastfailure = null;

    /**
     * Constructor. Dependencies can be replaced in tests (for example, a collector that fails).
     *
     * @param course_files|null $collector Metadata collector.
     * @param diagnostic_store|null $store Result store.
     * @param eligibility|null $eligibility Eligibility policy.
     */
    public function __construct(
        ?course_files $collector = null,
        ?diagnostic_store $store = null,
        ?eligibility $eligibility = null
    ) {
        $this->collector = $collector ?? new course_files();
        $this->store = $store ?? new diagnostic_store();
        $this->eligibility = $eligibility ?? new eligibility();
    }

    /**
     * Analyse a course.
     *
     * @param int $courseid Course id.
     * @param bool $force Analyse even if the result is already fresh (administrator request from the CLI).
     * @return string One of the outcome constants.
     */
    public function run(int $courseid, bool $force = false): string {
        $this->lastfailure = null;
        $lock = \core\lock\lock_config::get_lock_factory('block_coursecare')
            ->get_lock('analyse_course_' . $courseid, 0, self::LOCK_LIFETIME);
        if (!$lock) {
            return self::LOCKED;
        }
        try {
            if (!$this->eligibility->is_eligible($courseid)) {
                $this->store->delete_for_course($courseid);
                return self::INELIGIBLE;
            }
            if (!$force && $this->is_fresh($this->store->get_summary($courseid))) {
                $this->store->clear_request($courseid, time());
                return self::FRESH;
            }
            $this->store->mark_running($courseid, time());
            try {
                $result = $this->analyse($courseid);
                if (!indicator_calculator::invariants_hold($result)) {
                    // Files may have changed between two queries: collect once more (research R11).
                    $result = $this->analyse($courseid);
                }
                if (!indicator_calculator::invariants_hold($result)) {
                    $this->fail($courseid, self::REASON_INCONSISTENT);
                    return self::FAILED;
                }
                $this->store->save_result($courseid, $result, time());
                return self::DONE;
            } catch (\Throwable $e) {
                $this->fail($courseid, (new \ReflectionClass($e))->getShortName());
                return self::FAILED;
            }
        } finally {
            $lock->release();
        }
    }

    /**
     * Short failure code of the last run, or null if it did not fail.
     *
     * @return string|null
     */
    public function get_last_failure(): ?string {
        return $this->lastfailure;
    }

    /**
     * Collect and calculate the indicators of a course.
     *
     * @param int $courseid Course id.
     * @return \block_coursecare\local\analysis\diagnostic_result
     */
    private function analyse(int $courseid): \block_coursecare\local\analysis\diagnostic_result {
        $context = \context_course::instance($courseid);
        $threshold = self::large_file_threshold();
        $contexts = $this->collector->get_contexts($context);
        $arearows = $this->collector->get_area_aggregates($context, $threshold);
        $groupuniques = $this->collector->get_group_uniques($context);
        $categoryuniques = $this->collector->get_category_uniques($context);
        return (new indicator_calculator($threshold))->calculate($contexts, $arearows, $groupuniques, $categoryuniques);
    }

    /**
     * Large-file threshold in bytes, from the site setting in MB (default 50 MB).
     *
     * @return int
     */
    public static function large_file_threshold(): int {
        $mb = (int)get_config('block_coursecare', 'largefilethreshold');
        return ($mb > 0 ? $mb : 50) * self::MB;
    }

    /**
     * Whether the current result is newer than the last request and no request is pending: a duplicate task.
     *
     * @param \stdClass|null $record Diagnostic record.
     * @return bool
     */
    private function is_fresh(?\stdClass $record): bool {
        return $record !== null
            && (int)$record->requeststatus === diagnostic_store::STATUS_NONE
            && $record->timecompleted !== null
            && (int)$record->timecompleted >= (int)$record->timerequested;
    }

    /**
     * Record a failure; the previous result stays.
     *
     * @param int $courseid Course id.
     * @param string $reason Short code.
     */
    private function fail(int $courseid, string $reason): void {
        $this->lastfailure = $reason;
        $this->store->save_failure($courseid, $reason, time());
    }
}
