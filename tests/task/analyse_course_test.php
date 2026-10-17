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

use block_coursecare\local\analysis\diagnostic_state;
use block_coursecare\local\analysis\state_resolver;
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
     * Load the task fixture that injects a failure.
     */
    public static function setUpBeforeClass(): void {
        require_once(__DIR__ . '/../fixtures/analyse_course_with_failure.php');
        parent::setUpBeforeClass();
    }

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
     * The custom data of the task holds only the course id.
     */
    public function test_custom_data_holds_only_the_course(): void {
        $task = analyse_course::instance(123);
        $this->assertEquals((object)['courseid' => 123], $task->get_custom_data());
    }

    /**
     * Create a course with a block and one material file, analyse it, and make the result one hour old.
     *
     * @param int $bytes Size of the file.
     * @return array{0: \stdClass, 1: \stdClass} The course and its diagnostic record.
     */
    private function analysed_course(int $bytes): array {
        global $DB;
        $course = $this->requested_course();
        get_file_storage()->create_file_from_string(['contextid' => \context_course::instance($course->id)->id,
            'component' => 'course', 'filearea' => 'summary', 'itemid' => 0, 'filepath' => '/',
            'filename' => "material-{$course->id}.bin"], str_repeat('m', $bytes));
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id, true));
        $id = $DB->get_field('block_coursecare_diag', 'id', ['courseid' => $course->id]);
        $DB->set_field('block_coursecare_diag', 'timecompleted', time() - HOURSECS, ['id' => $id]);
        $DB->set_field('block_coursecare_diag', 'timeattempted', time() - HOURSECS, ['id' => $id]);
        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assertSame($bytes, (int)$record->logicalbytes);
        return [$course, $record];
    }

    /**
     * Run the queued ad hoc tasks with the core cron runner, which catches what a task throws, as the cron does.
     *
     * The runner refuses a task that ends inside a transaction, so the test must not run inside the PHPUnit
     * rollback transaction (preventResetByRollback()).
     *
     * @return string Cron output.
     */
    private function run_cron(): string {
        ob_start();
        \core\cron::run_adhoc_tasks(time(), 0, false);
        return ob_get_clean();
    }

    /**
     * Assert that the stored result of a course is still the given one.
     *
     * @param \stdClass $previous Previous record.
     * @param \stdClass $record Current record.
     */
    private function assert_result_kept(\stdClass $previous, \stdClass $record): void {
        foreach (['timecompleted', 'filecount', 'logicalbytes', 'uniquecount', 'uniquebytes', 'methodversion'] as $field) {
            $this->assertSame((int)$previous->$field, (int)$record->$field, $field);
        }
    }

    /**
     * CT-14 and FR-039a: in one cron run, a failing analysis keeps the previous result, records the failure without
     * a message and ends its task normally; the next course of the same run is analysed.
     */
    public function test_failure_keeps_previous_result(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        [$failing, $previous] = $this->analysed_course(6000);
        [$other, $otherprevious] = $this->analysed_course(4000);

        // The failing course is queued first, so it runs first in the same cron run.
        analyse_course_with_failure::$failure = analyse_course_with_failure::COLLECTION;
        $this->assertTrue(analyse_course_with_failure::request($failing->id, time()));
        $this->assertTrue(analyse_course::request($other->id, time()));
        $output = $this->run_cron();

        $failed = "course {$failing->id}, outcome failed (dml_read_exception)";
        $done = "course {$other->id}, outcome done";
        $this->assertStringContainsString($failed, $output);
        $this->assertStringContainsString($done, $output);
        $this->assertLessThan(strpos($output, $done), strpos($output, $failed));
        $this->assertStringContainsString('Adhoc task complete: ' . analyse_course_with_failure::class, $output);
        // Only the plugin's tasks matter here; the runner may also run tasks that the core generators queued.
        $this->assertStringNotContainsString('Adhoc task failed: block_coursecare', $output);
        $this->assertStringNotContainsString('private detail', $output);
        $this->assertSame(0, $DB->count_records('task_adhoc', ['component' => 'block_coursecare']));

        $record = (new diagnostic_store())->get_summary($failing->id);
        $this->assert_result_kept($previous, $record);
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$record->requeststatus);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame(1, (int)$record->failurecount);
        $this->assertSame('dml_read_exception', $record->failurereason);
        $state = state_resolver::resolve($record, time(), DAYSECS);
        $this->assertSame(diagnostic_state::FAILED, $state->state);
        $this->assertSame(6000, $state->logicalbytes);

        $record = (new diagnostic_store())->get_summary($other->id);
        $this->assertGreaterThan((int)$otherprevious->timecompleted, (int)$record->timecompleted);
        $this->assertSame(4000, (int)$record->logicalbytes);
        $this->assertSame(0, (int)$record->lastattemptfailed);
    }

    /**
     * A database failure before the analysis is left to the core: the task fails and stays queued for a retry,
     * the previous result is untouched and the course lock is released.
     */
    public function test_failure_before_analysis_is_left_to_core(): void {
        global $DB;
        $this->resetAfterTest();
        $this->preventResetByRollback();
        [$course, $previous] = $this->analysed_course(6000);

        analyse_course_with_failure::$failure = analyse_course_with_failure::BEFORE_ANALYSIS;
        $this->assertTrue(analyse_course_with_failure::request($course->id, time()));
        $output = $this->run_cron();

        $this->assertStringContainsString('Adhoc task failed: ' . analyse_course_with_failure::class, $output);
        $task = $DB->get_record('task_adhoc', ['component' => 'block_coursecare'], '*', MUST_EXIST);
        $this->assertGreaterThan(0, (int)$task->faildelay);

        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assert_result_kept($previous, $record);
        $this->assertSame(0, (int)$record->lastattemptfailed);
        $this->assertSame(diagnostic_store::STATUS_QUEUED, (int)$record->requeststatus);

        // The lock was released: the next attempt runs.
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id));
    }

    /**
     * A failure that cannot be recorded is thrown; the previous result stays and the lock is released.
     */
    public function test_failure_that_cannot_be_recorded_is_thrown(): void {
        $this->resetAfterTest();
        [$course, $previous] = $this->analysed_course(6000);

        $collector = new class extends \block_coursecare\local\collector\course_files {
            /**
             * Fail as a broken database would.
             *
             * @param \context_course $context Course context.
             * @return array
             */
            public function get_contexts(\context_course $context): array {
                throw new \dml_read_exception('Simulated read failure');
            }
        };
        $store = new class extends diagnostic_store {
            /**
             * Fail as a broken database would.
             *
             * @param int $courseid Course id.
             * @param string $reason Failure code.
             * @param int $now Current time.
             */
            public function save_failure(int $courseid, string $reason, int $now): void {
                throw new \dml_write_exception('Simulated write failure');
            }
        };
        $thrown = false;
        try {
            (new course_analysis($collector, $store))->run($course->id, true);
        } catch (\dml_write_exception $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assert_result_kept($previous, $record);
        $this->assertSame(0, (int)$record->lastattemptfailed);
        // Still marked as running: the scheduled task turns it into an "interrupted" failure after two hours.
        $this->assertSame(diagnostic_store::STATUS_RUNNING, (int)$record->requeststatus);
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id, true));
    }

    /**
     * A collector whose unique-content query returns an impossible value (more unique bytes than the logical
     * storage) a given number of times, then the real value; it counts its calls.
     *
     * @param int $impossible Number of calls that return the impossible value.
     * @return \block_coursecare\local\collector\course_files
     */
    private static function inconsistent_collector(int $impossible): \block_coursecare\local\collector\course_files {
        return new class ($impossible) extends \block_coursecare\local\collector\course_files {
            /** @var int Calls to get_group_uniques(). */
            public int $calls = 0;

            /** @var int Number of calls that return the impossible value. */
            private int $impossible;

            /**
             * Constructor.
             *
             * @param int $impossible Number of calls that return the impossible value.
             */
            public function __construct(int $impossible) {
                $this->impossible = $impossible;
            }

            /**
             * The impossible value first, then the real one.
             *
             * @param \context_course $context Course context.
             * @return array
             */
            public function get_group_uniques(\context_course $context): array {
                $this->calls++;
                if ($this->calls <= $this->impossible) {
                    return ['main' => (object)['contents' => 1, 'bytes' => PHP_INT_MAX]];
                }
                return parent::get_group_uniques($context);
            }
        };
    }

    /**
     * Research R11: invariants that fail twice record "inconsistent" and keep the previous result.
     */
    public function test_inconsistent_result(): void {
        $this->resetAfterTest();
        [$course, $previous] = $this->analysed_course(6000);
        $this->assertSame(1, (int)$previous->uniquecount);
        $this->assertSame(6000, (int)$previous->uniquebytes);

        $collector = self::inconsistent_collector(2);
        $analysis = new course_analysis($collector);
        $this->assertSame(course_analysis::FAILED, $analysis->run($course->id, true));
        $this->assertSame(2, $collector->calls);
        $this->assertSame(course_analysis::REASON_INCONSISTENT, $analysis->get_last_failure());

        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assert_result_kept($previous, $record);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame('inconsistent', $record->failurereason);
        $this->assertSame(diagnostic_state::FAILED, state_resolver::resolve($record, time(), DAYSECS)->state);
    }

    /**
     * Research R11: invariants that fail once are collected again, and the second, consistent result is stored.
     */
    public function test_inconsistent_once_is_collected_again(): void {
        $this->resetAfterTest();
        [$course, $previous] = $this->analysed_course(6000);

        $collector = self::inconsistent_collector(1);
        $this->assertSame(course_analysis::DONE, (new course_analysis($collector))->run($course->id, true));
        $this->assertSame(2, $collector->calls);

        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assertGreaterThan((int)$previous->timecompleted, (int)$record->timecompleted);
        $this->assertSame(6000, (int)$record->uniquebytes);
        $this->assertSame(0, (int)$record->lastattemptfailed);
    }

    /**
     * Two requests accepted at the same moment queue two tasks (diagnostic_store::request()); only the first one
     * analyses the course, the second ends as fresh without collecting.
     */
    public function test_duplicate_tasks_analyse_once(): void {
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $course = $this->requested_course();
        \core\task\manager::queue_adhoc_task(analyse_course::instance($course->id));
        \core\task\manager::queue_adhoc_task(analyse_course::instance($course->id));
        $output = $this->run_cron();

        $this->assertSame(1, substr_count($output, "course {$course->id}, outcome done"));
        $this->assertSame(1, substr_count($output, "course {$course->id}, outcome fresh"));
    }
}
