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

use block_coursecare\local\analysis\indicator_calculator;
use block_coursecare\local\collector\diagnostic_store;

/**
 * Tests of the hourly scheduling task: due courses, limit per run, stuck requests, orphans, retries, restore.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\task\queue_analyses
 * @covers     \block_coursecare\local\collector\diagnostic_store
 * @covers     \block_coursecare\local\collector\eligibility
 */
final class queue_analyses_test extends \advanced_testcase {
    /** @var int Fixed current time. */
    private const NOW = 2000000000;

    /**
     * A course with a CourseCare block, created by the generator (no request, as after a restore).
     *
     * @return \stdClass
     */
    private function course_with_block(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        return $course;
    }

    /**
     * Write a diagnostic record directly, with the given values.
     *
     * @param int $courseid Course.
     * @param array $values Values.
     */
    private function set_record(int $courseid, array $values): void {
        global $DB;
        $DB->insert_record('block_coursecare_diag', (object)($values + [
            'courseid' => $courseid, 'requeststatus' => 0, 'lastattemptfailed' => 0, 'failurecount' => 0,
            'methodversion' => indicator_calculator::METHOD_VERSION, 'timemodified' => self::NOW,
        ]));
    }

    /**
     * Number of queued analysis tasks.
     *
     * @return int
     */
    private static function queued_tasks(): int {
        return count(\core\task\manager::get_adhoc_tasks(analyse_course::class));
    }

    /**
     * Courses without record, with an old result or an old method are queued; recent ones are not.
     */
    public function test_due_courses_are_queued(): void {
        $this->resetAfterTest();
        $new = $this->course_with_block();
        $recent = $this->course_with_block();
        $this->set_record($recent->id, ['timecompleted' => self::NOW - HOURSECS, 'timeattempted' => self::NOW - HOURSECS,
            'logicalbytes' => 1, 'filecount' => 1]);
        $old = $this->course_with_block();
        $this->set_record($old->id, ['timecompleted' => self::NOW - 2 * DAYSECS, 'timeattempted' => self::NOW - 2 * DAYSECS]);
        $oldmethod = $this->course_with_block();
        $this->set_record($oldmethod->id, ['timecompleted' => self::NOW - HOURSECS, 'timeattempted' => self::NOW - HOURSECS,
            'methodversion' => 0]);
        $withoutblock = $this->getDataGenerator()->create_course();

        $stats = (new queue_analyses())->run(self::NOW);

        $this->assertSame(3, $stats['queued']);
        $store = new diagnostic_store();
        foreach ([$new, $old, $oldmethod] as $course) {
            $this->assertSame(diagnostic_store::STATUS_QUEUED, (int)$store->get_summary($course->id)->requeststatus);
        }
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$store->get_summary($recent->id)->requeststatus);
        // CT-28: a course without the block never gets a record.
        $this->assertNull($store->get_summary($withoutblock->id));
        $this->assertSame(3, self::queued_tasks());

        // CT-18: a second run does not duplicate requests or tasks.
        $this->assertSame(0, (new queue_analyses())->run(self::NOW)['queued']);
        $this->assertSame(3, self::queued_tasks());
    }

    /**
     * At most the batch size is queued per run, the courses never attempted first.
     */
    public function test_limit_per_run(): void {
        $this->resetAfterTest();
        $attempted = $this->course_with_block();
        $this->set_record($attempted->id, ['timecompleted' => self::NOW - 3 * DAYSECS, 'timeattempted' => self::NOW - 3 * DAYSECS]);
        $never1 = $this->course_with_block();
        $never2 = $this->course_with_block();

        $this->assertSame(2, (new queue_analyses())->run(self::NOW, 2)['queued']);
        $store = new diagnostic_store();
        $this->assertNotNull($store->get_summary($never1->id));
        $this->assertNotNull($store->get_summary($never2->id));
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$store->get_summary($attempted->id)->requeststatus);
        $this->assertSame(1, (new queue_analyses())->run(self::NOW, 2)['queued']);
    }

    /**
     * A running analysis older than 2 h becomes an interrupted failure; a request queued for more than 24 h is
     * released and queued again.
     */
    public function test_stuck_requests(): void {
        $this->resetAfterTest();
        $running = $this->course_with_block();
        $this->set_record($running->id, ['requeststatus' => 2, 'timestarted' => self::NOW - 3 * HOURSECS,
            'timecompleted' => self::NOW - DAYSECS + 10, 'timeattempted' => self::NOW - DAYSECS + 10, 'logicalbytes' => 5,
            'filecount' => 1]);
        $queued = $this->course_with_block();
        $this->set_record($queued->id, ['requeststatus' => 1, 'timerequested' => self::NOW - 2 * DAYSECS]);

        $stats = (new queue_analyses())->run(self::NOW);

        $this->assertSame(1, $stats['interrupted']);
        $this->assertSame(1, $stats['released']);
        $store = new diagnostic_store();
        $record = $store->get_summary($running->id);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame('interrupted', $record->failurereason);
        $this->assertSame(5, (int)$record->logicalbytes);
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$record->requeststatus);
        $record = $store->get_summary($queued->id);
        $this->assertSame(diagnostic_store::STATUS_QUEUED, (int)$record->requeststatus);
        $this->assertSame(self::NOW, (int)$record->timerequested);
    }

    /**
     * Diagnostics of courses without the block or deleted are removed.
     */
    public function test_orphans_removed(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $this->set_record($course->id, ['timecompleted' => self::NOW, 'timeattempted' => self::NOW]);
        $this->set_record(987654, ['timecompleted' => self::NOW, 'timeattempted' => self::NOW]);
        $DB->delete_records('block_instances', ['blockname' => 'coursecare']);

        $this->assertSame(2, (new queue_analyses())->run(self::NOW)['orphans']);
        $this->assertSame(0, $DB->count_records('block_coursecare_diag'));
    }

    /**
     * FR-039a: a failed course waits before a new attempt (1 h after one failure, 4 h after three, the interval
     * from the fifth on); a success resets the count.
     */
    public function test_bounded_retry(): void {
        $this->resetAfterTest();
        $cases = [
            [1, HOURSECS - 60, false], [1, HOURSECS + 60, true],
            [3, 4 * HOURSECS - 60, false], [3, 4 * HOURSECS + 60, true],
            [5, DAYSECS - 60, false], [5, DAYSECS + 60, true],
        ];
        $courses = [];
        foreach ($cases as [$failures, $ago, $expected]) {
            $course = $this->course_with_block();
            $this->set_record($course->id, ['lastattemptfailed' => 1, 'failurecount' => $failures,
                'timeattempted' => self::NOW - $ago]);
            $courses[] = [$course, $expected];
        }
        (new queue_analyses())->run(self::NOW);
        $store = new diagnostic_store();
        foreach ($courses as $n => [$course, $expected]) {
            $queued = (int)$store->get_summary($course->id)->requeststatus === diagnostic_store::STATUS_QUEUED;
            $this->assertSame($expected, $queued, "case {$n}");
        }

        // A success resets the count.
        $store->save_result($courses[1][0]->id, new \block_coursecare\local\analysis\diagnostic_result(
            ['methodversion' => 1, 'largethreshold' => 1, 'filecount' => 0, 'logicalbytes' => 0] +
            array_fill_keys(\block_coursecare\local\analysis\diagnostic_result::SUMMARY_FIELDS, null)
        ), self::NOW);
        $this->assertSame(0, (int)$store->get_summary($courses[1][0]->id)->failurecount);
    }

    /**
     * A course restored with the block (restore does not call instance_create) is queued by the next run.
     */
    public function test_restored_course_is_queued(): void {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->course_with_block();

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();
        $newcourseid = \restore_dbops::create_new_course('Restored', 'RESTORED', $course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        $store = new diagnostic_store();
        $this->assertNull($store->get_summary($newcourseid));
        (new queue_analyses())->run(time());
        $this->assertSame(diagnostic_store::STATUS_QUEUED, (int)$store->get_summary($newcourseid)->requeststatus);
    }
}
