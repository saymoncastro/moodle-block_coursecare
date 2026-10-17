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

use block_coursecare\local\collector\course_files;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\course_analysis;

/**
 * Test fixture: the real analysis task of one course, with a database failure injected at a chosen point.
 *
 * It is queued and run by the core cron runner like the real task, so a test can check what the cron does with
 * each kind of failure.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analyse_course_with_failure extends analyse_course {
    /** @var string The collection fails: a failure of the analysis itself. */
    public const COLLECTION = 'collection';

    /** @var string Marking the request as running fails: a database failure before the analysis. */
    public const BEFORE_ANALYSIS = 'beforeanalysis';

    /** @var string Where the next tasks of this class fail. */
    public static string $failure = self::COLLECTION;

    /**
     * The real analysis, with the failing collector or store.
     *
     * @return course_analysis
     */
    protected function create_analysis(): course_analysis {
        if (self::$failure === self::BEFORE_ANALYSIS) {
            return new course_analysis(null, new class extends diagnostic_store {
                /**
                 * Fail as a broken database would.
                 *
                 * @param int $courseid Course id.
                 * @param int $now Current time.
                 */
                public function mark_running(int $courseid, int $now): void {
                    throw new \dml_write_exception('Simulated write failure');
                }
            });
        }
        return new course_analysis(new class extends course_files {
            /**
             * Fail as a broken database would.
             *
             * @param \context_course $context Course context.
             * @param int $threshold Large-file threshold in bytes.
             * @return array
             */
            public function get_area_aggregates(\context_course $context, int $threshold): array {
                throw new \dml_read_exception('Simulated failure with a private detail');
            }
        });
    }
}
