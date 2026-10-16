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

    /**
     * Id of the instance of a repository type, creating the type if the test site does not have it.
     *
     * @param string $type Repository type.
     * @return int
     */
    private function repository_id(string $type): int {
        global $DB;
        if (!$DB->record_exists('repository', ['type' => $type])) {
            $this->getDataGenerator()->create_repository_type($type);
        }
        return (int)$this->getDataGenerator()->create_repository($type)->id;
    }

    /**
     * Create an alias (reference) in a real area, as the file picker does.
     *
     * @param \context $context Context of the alias.
     * @param int $repositoryid Repository instance.
     * @param string $reference Reference.
     * @param string|null $contenthash Content of the source, for internal references.
     * @return \stored_file
     */
    private function add_alias(\context $context, int $repositoryid, string $reference, ?string $contenthash = null): \stored_file {
        $this->filecounter++;
        $record = [
            'contextid' => $context->id, 'component' => 'mod_resource', 'filearea' => 'content', 'itemid' => 0,
            'filepath' => '/', 'filename' => "alias{$this->filecounter}.bin",
        ];
        if ($contenthash !== null) {
            $record['contenthash'] = $contenthash;
        }
        return get_file_storage()->create_file_from_reference($record, $repositoryid, $reference);
    }

    /**
     * Packed reference to a stored file, as internal repositories build it.
     *
     * @param \stored_file $file Source.
     * @return string
     */
    private static function reference_to(\stored_file $file): string {
        return \file_storage::pack_reference([
            'contextid' => $file->get_contextid(), 'component' => $file->get_component(),
            'filearea' => $file->get_filearea(), 'itemid' => $file->get_itemid(),
            'filepath' => $file->get_filepath(), 'filename' => $file->get_filename(),
        ]);
    }

    /**
     * Sum of the recorded sizes of a component in the course subtree, read directly from the files table.
     *
     * @param \stdClass $course Course.
     * @param string $component Component.
     * @return int
     */
    private function recorded_bytes(\stdClass $course, string $component): int {
        global $DB;
        $context = \context_course::instance($course->id);
        return (int)$DB->get_field_sql(
            "SELECT COALESCE(SUM(f.filesize), 0)
               FROM {files} f
               JOIN {context} ctx ON ctx.id = f.contextid
              WHERE (ctx.id = :ctxid OR ctx.path LIKE :path) AND f.component = :component AND f.filename <> '.'",
            ['ctxid' => $context->id, 'path' => $context->path . '/%', 'component' => $component]
        );
    }

    /**
     * CT-04: the same content in three resources counts three times in A and once in B.
     */
    public function test_same_content_in_three_resources(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $content = self::content(10 * self::MB, 'shared');
        foreach ([1, 2, 3] as $n) {
            $this->add_file($this->module('resource', $course), 'mod_resource', 'content', $content);
        }
        $record = $this->analyse($course);
        $this->assertSame(30 * self::MB, (int)$record->logicalbytes);
        $this->assertSame(10 * self::MB, (int)$record->uniquebytes);
        $this->assertSame(1, (int)$record->uniquecount);
    }

    /**
     * CT-05: the same content in two courses is unique content of each of them.
     */
    public function test_same_content_in_two_courses(): void {
        $this->resetAfterTest();
        $content = self::content(10 * self::MB, 'twocourses');
        $records = [];
        foreach (['x', 'y'] as $name) {
            $course = $this->course_with_block();
            $this->add_file($this->module('resource', $course), 'mod_resource', 'content', $content);
            $records[$name] = $this->analyse($course);
        }
        $this->assertSame(10 * self::MB, (int)$records['x']->uniquebytes);
        $this->assertSame(10 * self::MB, (int)$records['y']->uniquebytes);
    }

    /**
     * CT-12 and CT-13: a file added or removed after an analysis appears in the next analysis only.
     */
    public function test_file_added_and_removed_after_analysis(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $context = $this->module('resource', $course);
        $this->add_file($context, 'mod_resource', 'content', self::content(self::MB, 'base'));
        $first = $this->analyse($course);
        $this->assertSame(self::MB, (int)$first->logicalbytes);

        $added = $this->add_file($context, 'mod_resource', 'content', self::content(5 * self::MB, 'added'));
        $store = new diagnostic_store();
        $this->assertSame(self::MB, (int)$store->get_summary($course->id)->logicalbytes);
        $this->assertSame(6 * self::MB, (int)$this->analyse($course)->logicalbytes);

        $added->delete();
        $this->assertSame(6 * self::MB, (int)$store->get_summary($course->id)->logicalbytes);
        $this->assertSame(self::MB, (int)$this->analyse($course)->logicalbytes);
    }

    /**
     * CT-09, CT-22 and CT-32: references are counted apart by type, never in A or B, without any synchronisation.
     */
    public function test_references(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $context = $this->module('resource', $course);
        $fs = get_file_storage();

        // CT-32: a resource of 10 MB and an internal alias to it (server files repository).
        $source = $this->add_file($context, 'mod_resource', 'content', self::content(10 * self::MB, 'source'));
        $this->add_alias($context, $this->repository_id('local'), self::reference_to($source), $source->get_contenthash());

        // CT-09: an internal alias to a private file of 3 MB of the teacher (private files repository).
        $private = $this->add_file(\context_user::instance($USER->id), 'user', 'private', self::content(3 * self::MB, 'p'));
        $this->add_alias($context, $this->repository_id('user'), self::reference_to($private), $private->get_contenthash());

        // CT-09: an external alias of 4 MB, synchronised by the repository earlier (recorded here without network).
        $dropbox = $this->repository_id('dropbox');
        $external = $this->add_alias($context, $dropbox, 'fake/folder/report.bin');
        $fs->update_references(
            $external->get_referencefileid(),
            time(),
            null,
            \file_storage::hash_from_string(''),
            4 * self::MB,
            0
        );

        // CT-22: an external alias never synchronised, and an internal alias whose source is missing.
        $this->add_alias($context, $dropbox, 'fake/folder/never-synced.bin');
        $missing = $this->add_file(\context_user::instance($USER->id), 'user', 'private', self::content(2 * self::MB, 'm'));
        $broken = $this->add_alias(
            $context,
            $this->repository_id('user'),
            self::reference_to($missing),
            $missing->get_contenthash()
        );
        $fs->update_references(
            $broken->get_referencefileid(),
            time(),
            null,
            $missing->get_contenthash(),
            2 * self::MB,
            \block_coursecare\local\collector\course_files::SOURCE_MISSING
        );

        // CT-22: an empty file counts as a record without volume.
        $this->add_file($context, 'mod_resource', 'content', '');

        $referencesbefore = $DB->get_records('files_reference', null, 'id');
        $record = $this->analyse($course);

        $this->assertSame(10 * self::MB, (int)$record->logicalbytes);
        $this->assertSame(10 * self::MB, (int)$record->uniquebytes);
        $this->assertSame(2, (int)$record->filecount);
        $this->assertSame(2, (int)$record->refinternalcount);
        $this->assertSame(13 * self::MB, (int)$record->refinternalbytes);
        $this->assertSame(1, (int)$record->refexternalcount);
        $this->assertSame(4 * self::MB, (int)$record->refexternalbytes);
        $this->assertSame(2, (int)$record->refundeterminedcount);
        $this->assertSame(1, (int)$record->partial);
        // No synchronisation was triggered by the analysis.
        $this->assertEquals($referencesbefore, $DB->get_records('files_reference', null, 'id'));
    }

    /**
     * CT-29: a real course backup and an activity kept in the recycle bin are measured apart, out of A and B.
     */
    public function test_backup_and_recycle_bin(): void {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/course/lib.php');
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $this->add_file($this->module('resource', $course), 'mod_resource', 'content', self::content(10 * self::MB, 'm'));

        // An activity deleted with the course recycle bin enabled is kept as an activity backup in the bin.
        set_config('coursebinenable', 1, 'tool_recyclebin');
        $deleted = $this->getDataGenerator()->create_module('resource', ['course' => $course->id]);
        course_delete_module($deleted->cmid);

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $bc->destroy();

        $backupbytes = $this->recorded_bytes($course, 'backup');
        $recyclebytes = $this->recorded_bytes($course, 'tool_recyclebin');
        $this->assertGreaterThan(0, $backupbytes);
        $this->assertGreaterThan(0, $recyclebytes);

        $record = $this->analyse($course);
        $this->assertSame(10 * self::MB, (int)$record->logicalbytes);
        $this->assertSame(10 * self::MB, (int)$record->uniquebytes);
        $this->assertSame($backupbytes + $recyclebytes, (int)$record->brbytes);
        $this->assertSame(2, (int)$record->brcount);

        $categories = (new diagnostic_store())->get_categories((int)$record->id);
        $this->assertSame($backupbytes, (int)$categories['backup']->logicalbytes);
        $this->assertSame($recyclebytes, (int)$categories['recyclebin']->logicalbytes);
    }

    /**
     * CT-30 and CT-31: content shared by the bin and a backup counts once in the aggregate; a user backup is out.
     */
    public function test_backup_aggregate_unique_and_user_backup(): void {
        global $USER;
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $coursecontext = \context_course::instance($course->id);
        $content = self::content(5 * self::MB, 'bincopy');
        $this->add_file($coursecontext, 'backup', 'course', $content);
        $this->add_file($coursecontext, 'tool_recyclebin', 'recyclebin_course', $content);
        $this->add_file(\context_user::instance($USER->id), 'user', 'backup', self::content(7 * self::MB, 'userbackup'));

        $record = $this->analyse($course);
        $this->assertSame(0, (int)$record->logicalbytes);
        $this->assertSame(10 * self::MB, (int)$record->brbytes);
        $this->assertSame(5 * self::MB, (int)$record->bruniquebytes);
        $this->assertSame(1, (int)$record->bruniquecount);

        $categories = (new diagnostic_store())->get_categories((int)$record->id);
        $this->assertSame(5 * self::MB, (int)$categories['backup']->uniquebytes);
        $this->assertSame(5 * self::MB, (int)$categories['recyclebin']->uniquebytes);
    }
}
