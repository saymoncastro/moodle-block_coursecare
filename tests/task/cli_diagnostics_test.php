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

use block_coursecare\local\admin_cli;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\course_analysis;

/**
 * Tests of the administrator command line operations (contracts/admin-cli.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\admin_cli
 */
final class cli_diagnostics_test extends \advanced_testcase {
    /**
     * A course with a CourseCare block.
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
     * Option validation: one mode, a positive integer course, --all and --json only where allowed.
     */
    public function test_validate_options(): void {
        $base = ['queue' => false, 'run' => false, 'show' => false, 'all' => false, 'course' => false, 'json' => false];
        $this->assertSame(5, admin_cli::validate_options(['show' => true, 'course' => '5'] + $base));
        $this->assertSame(0, admin_cli::validate_options(['queue' => true, 'all' => true] + $base));
        $this->assertSame(7, admin_cli::validate_options(['run' => true, 'course' => '7', 'json' => true] + $base));
        $invalid = [
            $base,
            ['queue' => true, 'run' => true, 'course' => '5'] + $base,
            ['show' => true] + $base,
            ['show' => true, 'course' => 'abc'] + $base,
            ['show' => true, 'course' => '0'] + $base,
            ['show' => true, 'course' => '-3'] + $base,
            ['show' => true, 'course' => '5', 'all' => true] + $base,
            ['queue' => true, 'course' => '5', 'all' => true] + $base,
            ['queue' => true, 'course' => '5', 'json' => true] + $base,
        ];
        foreach ($invalid as $n => $options) {
            $this->assertNull(admin_cli::validate_options($options), "case {$n}");
        }
    }

    /**
     * --queue respects eligibility and does not duplicate a pending request.
     */
    public function test_queue(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $cli = new admin_cli();
        $this->assertSame(admin_cli::EXIT_OK, $cli->queue_course($course->id)[0]);
        $this->assertSame(admin_cli::EXIT_OK, $cli->queue_course($course->id)[0]);
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(analyse_course::class));
        $withoutblock = $this->getDataGenerator()->create_course();
        $this->assertSame(admin_cli::EXIT_INELIGIBLE, $cli->queue_course($withoutblock->id)[0]);

        $this->course_with_block();
        [$code, $message] = $cli->queue_all();
        $this->assertSame(admin_cli::EXIT_OK, $code);
        $this->assertSame(get_string('cli:queuedall', 'block_coursecare', 1), $message);
        $this->assertCount(2, \core\task\manager::get_adhoc_tasks(analyse_course::class));
    }

    /**
     * --run: exit 2 for an ineligible course, 3 while another process holds the lock, 0 otherwise.
     */
    public function test_run_exit_codes(): void {
        global $CFG;
        $this->resetAfterTest();
        // The database lock factory is exclusive within one process (see analyse_course_test).
        $CFG->lock_factory = '\\core\\lock\\db_record_lock_factory';
        $course = $this->course_with_block();
        $cli = new admin_cli();

        $this->assertSame(admin_cli::EXIT_INELIGIBLE, $cli->run_course(999999)[0]);
        $lock = \core\lock\lock_config::get_lock_factory('block_coursecare')->get_lock('analyse_course_' . $course->id, 0);
        try {
            $this->assertSame(admin_cli::EXIT_LOCKED, $cli->run_course($course->id)[0]);
        } finally {
            $lock->release();
        }
        [$code, $data] = $cli->run_course($course->id);
        $this->assertSame(admin_cli::EXIT_OK, $code);
        $this->assertSame(['courseid', 'outcome', 'failure', 'seconds', 'peakmemory'], array_keys($data));
    }

    /**
     * An admin_cli whose --run uses the given analysis.
     *
     * @param course_analysis $analysis Analysis to use.
     * @return admin_cli
     */
    private static function cli_with(course_analysis $analysis): admin_cli {
        return new class ($analysis) extends admin_cli {
            /** @var course_analysis Analysis to use. */
            private course_analysis $analysis;

            /**
             * Constructor.
             *
             * @param course_analysis $analysis Analysis to use.
             */
            public function __construct(course_analysis $analysis) {
                parent::__construct();
                $this->analysis = $analysis;
            }

            /**
             * The given analysis.
             *
             * @return course_analysis
             */
            protected function create_analysis(): course_analysis {
                return $this->analysis;
            }
        };
    }

    /**
     * A store whose mark_running() fails as a broken database would: a failure before the analysis, thrown.
     *
     * @return diagnostic_store
     */
    private static function store_failing_before_analysis(): diagnostic_store {
        return new class extends diagnostic_store {
            /**
             * Fail as a broken database would.
             *
             * @param int $courseid Course id.
             * @param int $now Current time.
             */
            public function mark_running(int $courseid, int $now): void {
                throw new \dml_write_exception('Simulated write failure');
            }
        };
    }

    /**
     * --run exits with 4 for any failure: an analysis failure, or a database failure that the analysis throws,
     * which is then recorded in the existing diagnostic (never a PHP error with the exit code 1 of invalid options).
     */
    public function test_run_failures_exit_4(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course_with_block();
        get_file_storage()->create_file_from_string(['contextid' => \context_course::instance($course->id)->id,
            'component' => 'course', 'filearea' => 'summary', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'material.bin'], str_repeat('m', 3000));
        $this->assertSame(admin_cli::EXIT_OK, (new admin_cli())->run_course($course->id)[0]);
        $DB->set_field('block_coursecare_diag', 'timecompleted', time() - HOURSECS, ['courseid' => $course->id]);
        $previous = (new diagnostic_store())->get_summary($course->id);

        // A failure of the analysis itself: recorded by the analysis.
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
        [$code, $data] = self::cli_with(new course_analysis($collector))->run_course($course->id);
        $this->assertSame(admin_cli::EXIT_FAILED, $code);
        $this->assertSame(course_analysis::FAILED, $data['outcome']);
        $this->assertSame('dml_read_exception', $data['failure']);

        // A database failure before the analysis, thrown by the analysis: reported and recorded by the CLI.
        [$code, $data] = self::cli_with(new course_analysis(null, self::store_failing_before_analysis()))
            ->run_course($course->id);
        $this->assertSame(admin_cli::EXIT_FAILED, $code);
        $this->assertSame(course_analysis::FAILED, $data['outcome']);
        $this->assertSame('dml_write_exception', $data['failure']);
        $record = (new diagnostic_store())->get_summary($course->id);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame(2, (int)$record->failurecount);
        $this->assertSame('dml_write_exception', $record->failurereason);
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$record->requeststatus);
        $this->assertSame((int)$previous->timecompleted, (int)$record->timecompleted);
        $this->assertSame(3000, (int)$record->logicalbytes);

        // The lock was released: the next run analyses again.
        $this->assertSame(admin_cli::EXIT_OK, (new admin_cli())->run_course($course->id)[0]);

        // A course without a diagnostic gets none from a thrown failure.
        $other = $this->course_with_block();
        $DB->delete_records('block_coursecare_diag', ['courseid' => $other->id]);
        [$code] = self::cli_with(new course_analysis(null, self::store_failing_before_analysis()))->run_course($other->id);
        $this->assertSame(admin_cli::EXIT_FAILED, $code);
        $this->assertNull((new diagnostic_store())->get_summary($other->id));
    }

    /**
     * --show returns the contract keys and never a file, activity or user name.
     */
    public function test_show_has_no_names(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->course_with_block();
        $resource = $this->getDataGenerator()->create_module('resource', ['course' => $course->id, 'name' => 'Secret activity']);
        get_file_storage()->create_file_from_string(['contextid' => \context_module::instance($resource->cmid)->id,
            'component' => 'mod_resource', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'private-name.pdf'], 'content');
        $cli = new admin_cli();
        $cli->run_course($course->id);

        [$code, $data] = $cli->show_course($course->id);
        $this->assertSame(admin_cli::EXIT_OK, $code);
        foreach (
            ['courseid', 'state', 'partial', 'requeststatus', 'timecompleted', 'timeattempted', 'lastattemptfailed',
                'failurecount', 'failurereason', 'summary', 'categories'] as $key
        ) {
            $this->assertArrayHasKey($key, $data);
        }
        $output = json_encode($data) . admin_cli::format_show($data);
        foreach (['private-name', 'Secret activity', fullname($USER), $USER->username] as $name) {
            $this->assertStringNotContainsString($name, $output);
        }
        $this->assertSame(admin_cli::EXIT_INELIGIBLE, $cli->show_course(999999)[0]);
    }
}
