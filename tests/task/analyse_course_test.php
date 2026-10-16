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

use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\course_analysis;

/**
 * Tests of the per-course analysis task: eligibility, freshness, lock and failure isolation.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\task\analyse_course
 * @covers     \block_coursecare\local\course_analysis
 */
final class analyse_course_test extends \advanced_testcase {
    /**
     * Create a course with a CourseCare block and a pending request.
     *
     * @return \stdClass
     */
    private function requested_course(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        (new diagnostic_store())->request($course->id, time());
        return $course;
    }

    /**
     * Run one analysis task for a course, silencing its trace output.
     *
     * @param int $courseid Course id.
     */
    private function run_task(int $courseid): void {
        $task = analyse_course::instance($courseid);
        ob_start();
        $task->execute();
        ob_end_clean();
    }

    /**
     * A normal run stores the result and clears the request.
     */
    public function test_run_stores_result(): void {
        $this->resetAfterTest();
        $course = $this->requested_course();
        $this->run_task($course->id);

        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$record->requeststatus);
        $this->assertNotNull($record->timecompleted);
        $this->assertSame(0, (int)$record->lastattemptfailed);
    }

    /**
     * FR-003b: a missing course or a course without the block gets no result and loses its diagnostic.
     */
    public function test_ineligible_course(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->requested_course();
        $DB->delete_records('block_instances', ['blockname' => 'coursecare']);
        $this->assertSame(course_analysis::INELIGIBLE, (new course_analysis())->run($course->id));
        $this->assertNull((new diagnostic_store())->get_summary($course->id));

        $this->assertSame(course_analysis::INELIGIBLE, (new course_analysis())->run(999999));
        $this->assertSame(course_analysis::INELIGIBLE, (new course_analysis())->run(SITEID));
    }

    /**
     * A duplicate task after a completed analysis does not analyse again; an administrator run does.
     */
    public function test_fresh_result_is_not_repeated(): void {
        $this->resetAfterTest();
        $course = $this->requested_course();
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id));
        $this->assertSame(course_analysis::FRESH, (new course_analysis())->run($course->id));
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id, true));
    }

    /**
     * CT-18: while another process holds the course lock, the analysis does not run.
     */
    public function test_locked_course(): void {
        global $CFG;
        $this->resetAfterTest();
        // In one PHP process the PostgreSQL advisory lock is re-entrant; the database lock factory is exclusive
        // even within a process, which simulates a second cron runner.
        $CFG->lock_factory = '\\core\\lock\\db_record_lock_factory';
        $course = $this->requested_course();
        $lock = \core\lock\lock_config::get_lock_factory('block_coursecare')->get_lock('analyse_course_' . $course->id, 0);
        $this->assertNotFalse($lock);
        try {
            $this->assertSame(course_analysis::LOCKED, (new course_analysis())->run($course->id));
            $record = (new diagnostic_store())->get_summary($course->id);
            $this->assertNull($record->timecompleted);
        } finally {
            $lock->release();
        }
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id));
    }

    /**
     * The task never throws, even if the analysis fails, and the custom data holds only the course id.
     */
    public function test_task_does_not_throw(): void {
        $this->resetAfterTest();
        $task = analyse_course::instance(123);
        $this->assertEquals((object)['courseid' => 123], $task->get_custom_data());
        ob_start();
        $task->execute();
        ob_end_clean();
        $this->assertNull((new diagnostic_store())->get_summary(123));
    }

    /**
     * CT-14: a failing collection keeps the previous result, records the failure without a message, and does not
     * stop the analysis of another course in the same cron run.
     */
    public function test_failure_keeps_previous_result(): void {
        $this->resetAfterTest();
        $failing = $this->requested_course();
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($failing->id, true));
        $previous = (new diagnostic_store())->get_summary($failing->id);

        $collector = new class extends \block_coursecare\local\collector\course_files {
            /**
             * Fail as a broken database would.
             *
             * @param \context_course $context Course context.
             * @return array
             */
            public function get_contexts(\context_course $context): array {
                throw new \dml_read_exception('Simulated failure with a private detail');
            }
        };
        $analysis = new course_analysis($collector);
        $this->assertSame(course_analysis::FAILED, $analysis->run($failing->id, true));
        $this->assertSame('dml_read_exception', $analysis->get_last_failure());

        $record = (new diagnostic_store())->get_summary($failing->id);
        $this->assertSame((int)$previous->timecompleted, (int)$record->timecompleted);
        $this->assertSame((int)$previous->logicalbytes, (int)$record->logicalbytes);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame(1, (int)$record->failurecount);
        $this->assertSame('dml_read_exception', $record->failurereason);

        // The next course of the same cron run is analysed normally.
        $other = $this->requested_course();
        \core\task\manager::queue_adhoc_task(analyse_course::instance($other->id));
        ob_start();
        $this->runAdhocTasks(analyse_course::class);
        ob_end_clean();
        $this->assertNotNull((new diagnostic_store())->get_summary($other->id)->timecompleted);
    }

    /**
     * Invariants that fail twice record "inconsistent" and keep the previous result (research R11).
     */
    public function test_inconsistent_result(): void {
        $this->resetAfterTest();
        $course = $this->requested_course();
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id, true));

        $collector = new class extends \block_coursecare\local\collector\course_files {
            /**
             * More unique content than logical storage: impossible.
             *
             * @param \context_course $context Course context.
             * @return array
             */
            public function get_group_uniques(\context_course $context): array {
                return ['main' => (object)['contents' => 1, 'bytes' => 999]];
            }
        };
        $analysis = new course_analysis($collector);
        $this->assertSame(course_analysis::FAILED, $analysis->run($course->id, true));
        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assertSame('inconsistent', $record->failurereason);
        $this->assertSame(0, (int)$record->logicalbytes);
    }
}
