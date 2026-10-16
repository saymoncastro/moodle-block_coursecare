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
}
