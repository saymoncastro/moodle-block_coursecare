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

namespace block_coursecare\collector;

use block_coursecare\local\collector\course_structure;

/**
 * Tests of the course structure read for the overview page (contracts/overview-data.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\collector\course_structure
 */
final class course_structure_test extends \advanced_testcase {
    /**
     * Activities with their current name, link and visibility for the current user, without reading files.
     */
    public function test_get_activities(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id, 'name' => 'Reading <b>one</b>']);
        $label = $generator->create_module('label', ['course' => $course->id, 'intro' => 'Welcome']);
        $hidden = $generator->create_module('resource', ['course' => $course->id, 'name' => 'Hidden file',
            'visible' => 0]);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $student = $generator->create_and_enrol($course, 'student');

        $this->setUser($teacher);
        // The core rebuilds its course module cache after a change to the course, as the course page does, and that
        // rebuild reads the files of resources (their icon). Warm it first: the collector itself reads no file record.
        get_fast_modinfo($course->id);
        $DB->set_debug(true);
        ob_start();
        $activities = (new course_structure())->get_activities((int)$course->id);
        $queries = ob_get_clean();
        $DB->set_debug(false);
        $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($CFG->prefix, '/') . 'files\b/', $queries);

        $this->assertSame('Reading one', $activities[$page->cmid]->name);
        $this->assertStringContainsString('/mod/page/view.php?id=' . $page->cmid, $activities[$page->cmid]->url);
        $this->assertTrue($activities[$page->cmid]->uservisible);
        $this->assertNull($activities[$label->cmid]->url);
        $this->assertTrue($activities[$hidden->cmid]->uservisible);

        $this->setUser($student);
        $activities = (new course_structure())->get_activities((int)$course->id);
        $this->assertFalse($activities[$hidden->cmid]->uservisible);
        $this->assertTrue($activities[$page->cmid]->uservisible);
    }
}
