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
