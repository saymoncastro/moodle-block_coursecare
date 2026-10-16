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

namespace block_coursecare\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Tests for the CourseCare privacy provider.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The provider declares that no personal data is stored, with an existing reason string that describes the
     * aggregated measurements kept by the diagnostic (Feature 002).
     */
    public function test_null_provider_reason(): void {
        $this->assertTrue(is_subclass_of(provider::class, null_provider::class));

        $reason = provider::get_reason();
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(get_string_manager()->string_exists($reason, 'block_coursecare'));
        $this->assertStringContainsString('aggregated', get_string($reason, 'block_coursecare'));
    }

    /**
     * SC-006: after analysing a course with student submissions and hidden or restricted activities, the plugin
     * tables have no user, file name or path column, their text values hold no file or activity name, and large-file
     * links only point to course materials (D10).
     */
    public function test_stored_data_has_no_personal_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'Essay task']);
        $hidden = $this->getDataGenerator()->create_module('resource', ['course' => $course->id, 'name' => 'Hidden reading',
            'visible' => 0]);
        $restricted = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'name' => 'Restricted page',
            'availability' => '{"op":"&","c":[{"type":"date","d":">=","t":4102444800}],"showc":[true]}']);
        $fs = get_file_storage();
        $fs->create_file_from_string(['contextid' => \context_module::instance($assign->cmid)->id,
            'component' => 'assignsubmission_file', 'filearea' => 'submission_files', 'itemid' => 1, 'filepath' => '/',
            'filename' => 'student-essay-private.pdf', 'userid' => $student->id], str_repeat('s', 60 * 1048576));
        $fs->create_file_from_string(['contextid' => \context_module::instance($restricted->cmid)->id,
            'component' => 'mod_page', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'restricted-image.png'], 'restricted');
        // A large course material uploaded by a teacher, in the hidden resource: the one file that gets a link.
        set_config('largefilethreshold', 1, 'block_coursecare');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $handout = $fs->create_file_from_string(['contextid' => \context_module::instance($hidden->cmid)->id,
            'component' => 'mod_resource', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'teacher-handout-confidential.pdf', 'userid' => $teacher->id], str_repeat('h', 2 * 1048576));

        $this->assertSame(
            \block_coursecare\local\course_analysis::DONE,
            (new \block_coursecare\local\course_analysis())->run($course->id, true)
        );

        $forbidden = ['userid', 'usermodified', 'filename', 'filepath', 'pathnamehash', 'contenthash', 'author', 'source'];
        $names = ['student-essay-private', 'restricted-image', 'teacher-handout-confidential', 'Essay task', 'Hidden reading',
            'Restricted page', fullname($student), $student->username, fullname($teacher), $teacher->username];
        $tables = ['block_coursecare_diag', 'block_coursecare_cat', 'block_coursecare_act', 'block_coursecare_large'];
        foreach ($tables as $table) {
            foreach (array_keys($DB->get_columns($table)) as $column) {
                $this->assertNotContains($column, $forbidden, "{$table}.{$column}");
            }
            foreach ($DB->get_records($table) as $record) {
                foreach ((array)$record as $value) {
                    foreach ($names as $name) {
                        $this->assertStringNotContainsString($name, (string)$value);
                    }
                }
            }
        }
        // Both files are large, but only the material has a link; the student's submission has none.
        $this->assertSame(2, (int)$DB->get_field('block_coursecare_diag', 'largecount', ['courseid' => $course->id]));
        $links = $DB->get_records('block_coursecare_large');
        $this->assertCount(1, $links);
        $link = reset($links);
        $this->assertSame(['id', 'diagid', 'fileid', 'cmid', 'filesize'], array_keys((array)$link));
        $this->assertSame((int)$handout->get_id(), (int)$link->fileid);
        $this->assertSame((int)$hidden->cmid, (int)$link->cmid);
        $this->assertSame(2 * 1048576, (int)$link->filesize);
    }
}
