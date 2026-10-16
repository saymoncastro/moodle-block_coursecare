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

use block_coursecare\local\analysis\classification_rules;
use block_coursecare\local\collector\course_files;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\course_analysis;

/**
 * Tests of the collection and the indicators with the real File API (CT-01 to CT-03, CT-10, SC-010).
 *
 * Files are created with file_storage in the real areas of real modules made by the core generators.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\collector\course_files
 * @covers     \block_coursecare\local\course_analysis
 */
final class course_files_test extends \advanced_testcase {
    /** @var int One MB. */
    private const MB = 1048576;

    /** @var int Counter for unique file names. */
    private int $filecounter = 0;

    /**
     * Create a course with a CourseCare block.
     *
     * @return \stdClass
     */
    private function course_with_block(): \stdClass {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        return $course;
    }

    /**
     * Create a module and remove the files that its generator creates, so that the test controls every file.
     *
     * @param string $modname Module name.
     * @param \stdClass $course Course.
     * @return \context_module
     */
    private function module(string $modname, \stdClass $course): \context_module {
        $module = $this->getDataGenerator()->create_module($modname, ['course' => $course->id]);
        $context = \context_module::instance($module->cmid);
        get_file_storage()->delete_area_files($context->id);
        return $context;
    }

    /**
     * Content of a given size, distinct for each seed.
     *
     * @param int $bytes Size.
     * @param string $seed Seed.
     * @return string
     */
    private static function content(int $bytes, string $seed): string {
        $block = hash('sha256', $seed);
        return substr(str_repeat($block, intdiv($bytes, strlen($block)) + 1), 0, $bytes);
    }

    /**
     * Add a file in a real area.
     *
     * @param \context $context Context.
     * @param string $component Component.
     * @param string $filearea Area.
     * @param string $content Content.
     * @param string $filepath Path.
     * @return \stored_file
     */
    private function add_file(
        \context $context,
        string $component,
        string $filearea,
        string $content,
        string $filepath = '/'
    ): \stored_file {
        $this->filecounter++;
        return get_file_storage()->create_file_from_string([
            'contextid' => $context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => 0,
            'filepath' => $filepath, 'filename' => "file{$this->filecounter}.bin",
        ], $content);
    }

    /**
     * Analyse a course and return its stored record.
     *
     * @param \stdClass $course Course.
     * @return \stdClass
     */
    private function analyse(\stdClass $course): \stdClass {
        $this->assertSame(course_analysis::DONE, (new course_analysis())->run($course->id, true));
        return (new diagnostic_store())->get_summary($course->id);
    }

    /**
     * CT-01: a course without files is analysed as zero, with a date.
     */
    public function test_empty_course(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $record = $this->analyse($course);
        $this->assertSame(0, (int)$record->logicalbytes);
        $this->assertSame(0, (int)$record->filecount);
        $this->assertNotNull($record->timecompleted);
    }

    /**
     * CT-02: one file of 1 MB in a file resource.
     */
    public function test_one_file(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $this->add_file($this->module('resource', $course), 'mod_resource', 'content', self::content(self::MB, 'a'));
        $record = $this->analyse($course);
        $this->assertSame(self::MB, (int)$record->logicalbytes);
        $this->assertSame(1, (int)$record->filecount);
    }

    /**
     * CT-03: files of 1, 2 and 3 MB in different resources; a file of another course is not counted.
     */
    public function test_several_files(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        foreach ([1, 2, 3] as $size) {
            $this->add_file(
                $this->module('resource', $course),
                'mod_resource',
                'content',
                self::content($size * self::MB, "f{$size}")
            );
        }
        $other = $this->getDataGenerator()->create_course();
        $this->add_file($this->module('resource', $other), 'mod_resource', 'content', self::content(self::MB, 'other'));

        $record = $this->analyse($course);
        $this->assertSame(6 * self::MB, (int)$record->logicalbytes);
        $this->assertSame(3, (int)$record->filecount);
    }

    /**
     * CT-10: directories, drafts, private files and transient areas are not counted.
     */
    public function test_excluded_records(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = \context_user::instance($user->id);
        $folder = $this->module('folder', $course);

        // A file in a sub-folder also creates directory records ("." entries).
        $this->add_file($folder, 'mod_folder', 'content', self::content(1000, 'sub'), '/a/b/');
        $this->add_file($folder, 'mod_folder', 'temp', self::content(5000, 'temp'));
        $this->add_file($usercontext, 'user', 'draft', self::content(7000, 'draft'));
        $this->add_file($usercontext, 'user', 'private', self::content(9000, 'private'));

        $record = $this->analyse($course);
        $this->assertSame(1000, (int)$record->logicalbytes);
        $this->assertSame(1, (int)$record->filecount);
    }

    /**
     * The SQL CASE expression classifies every rule exactly like the PHP rule table (single source).
     */
    public function test_sql_classification_matches_php(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->course_with_block();
        $coursecontext = \context_course::instance($course->id);
        $modcontext = $this->module('assign', $course);
        $block = $this->getDataGenerator()->create_block('html', ['parentcontextid' => $coursecontext->id]);
        $blockcontext = \context_block::instance($block->id);
        $contexts = [CONTEXT_COURSE => $coursecontext, CONTEXT_MODULE => $modcontext, CONTEXT_BLOCK => $blockcontext];

        $samples = [];
        foreach (classification_rules::rules() as $rule) {
            $component = str_replace('*', 'lesson', $rule['component']);
            $component = $component === 'qtype_lesson' ? 'qtype_ddmarker' : $component;
            $area = $rule['fileareas'] === null ? 'questiontext' : str_replace('*', 'attachments', $rule['fileareas'][0]);
            $level = $rule['contextlevels'][0];
            $samples[] = [$contexts[$level], $component, $area];
        }
        $samples[] = [$modcontext, 'local_fake', 'stuff'];
        $samples[] = [$modcontext, 'mod_resource', 'weird'];
        $samples[] = [$coursecontext, 'mod_resource', 'content'];
        foreach ($samples as $n => [$context, $component, $area]) {
            $this->add_file($context, $component, $area, "sample {$n}");
        }

        [$case, $params] = (new course_files())->category_case('t');
        $params += ['ctxid' => $coursecontext->id, 'path' => $coursecontext->path . '/%', 'dot' => '.'];
        $rows = $DB->get_records_sql(
            "SELECT f.id, f.component, f.filearea, ctx.contextlevel, {$case} AS category
               FROM {files} f
               JOIN {context} ctx ON ctx.id = f.contextid
              WHERE (ctx.id = :ctxid OR ctx.path LIKE :path) AND f.filename <> :dot",
            $params
        );
        $this->assertCount(count($samples), $rows);
        foreach ($rows as $row) {
            $expected = classification_rules::classify($row->component, $row->filearea, (int)$row->contextlevel);
            $this->assertSame($expected, $row->category, "{$row->component}/{$row->filearea}");
        }
    }

    /**
     * SC-010 and FR-040: the analysis creates, changes and removes no file record.
     */
    public function test_files_untouched(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $this->add_file($this->module('resource', $course), 'mod_resource', 'content', self::content(3000, 'x'));
        $this->add_file($this->module('folder', $course), 'mod_folder', 'content', self::content(4000, 'y'), '/d/');

        $before = $DB->get_records('files', null, 'id');
        $this->analyse($course);
        $this->assertEquals($before, $DB->get_records('files', null, 'id'));
    }
}
