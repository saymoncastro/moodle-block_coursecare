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
     * @param array $record Extra settings of the module.
     * @return \context_module
     */
    private function module(string $modname, \stdClass $course, array $record = []): \context_module {
        $module = $this->getDataGenerator()->create_module($modname, ['course' => $course->id] + $record);
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

        // CT-03 by activity: one row per resource with its own volume, none at course level.
        $volumes = array_column($this->activities($record), 'logicalbytes');
        sort($volumes);
        $this->assertSame([self::MB, 2 * self::MB, 3 * self::MB], $volumes);
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

    /**
     * Expected totals of one category row.
     *
     * @param int $filecount Records.
     * @param int $logicalbytes Logical bytes.
     * @param int $uniquecount Distinct contents.
     * @param int $uniquebytes Unique bytes.
     * @return array<string, int>
     */
    private static function totals(int $filecount, int $logicalbytes, int $uniquecount, int $uniquebytes): array {
        return compact('filecount', 'logicalbytes', 'uniquecount', 'uniquebytes');
    }

    /**
     * Stored categories of a course result, with integer values.
     *
     * @param \stdClass $record Diagnostic record.
     * @return array<string, array<string, int>>
     */
    private function categories(\stdClass $record): array {
        $rows = [];
        foreach ((new diagnostic_store())->get_categories((int)$record->id) as $category => $row) {
            $rows[$category] = [
                'filecount' => (int)$row->filecount,
                'logicalbytes' => (int)$row->logicalbytes,
                'uniquecount' => (int)$row->uniquecount,
                'uniquebytes' => (int)$row->uniquebytes,
            ];
        }
        return $rows;
    }

    /**
     * Submit a file and an online text with an embedded image through the assignment API, as the student.
     *
     * @param \context_module $context Assignment context.
     * @param \stdClass $user Enrolled student.
     * @param string $file Content of the submitted file.
     * @param string $image Content of the image embedded in the online text.
     */
    private function submit(\context_module $context, \stdClass $user, string $file, string $image): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        [$course, $cm] = get_course_and_cm_from_cmid($context->instanceid, 'assign');
        $assign = new \assign($context, $cm, $course);

        $this->setUser($user);
        $usercontext = \context_user::instance($user->id);
        $fs = get_file_storage();
        $fileitem = file_get_unused_draft_itemid();
        $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $fileitem, 'filepath' => '/', 'filename' => 'work.bin'], $file);
        $textitem = file_get_unused_draft_itemid();
        $fs->create_file_from_string(['contextid' => $usercontext->id, 'component' => 'user', 'filearea' => 'draft',
            'itemid' => $textitem, 'filepath' => '/', 'filename' => 'image.png'], $image);

        // The editor references draft files by their draft URL; unreferenced drafts are discarded on save.
        $text = file_rewrite_pluginfile_urls(
            '<p><img src="@@PLUGINFILE@@/image.png" alt=""></p>',
            'draftfile.php',
            $usercontext->id,
            'user',
            'draft',
            $textitem
        );
        $data = (object)[
            'userid' => $user->id,
            'files_filemanager' => $fileitem,
            'onlinetext_editor' => ['itemid' => $textitem, 'text' => $text, 'format' => FORMAT_HTML],
        ];
        $notices = [];
        $this->assertTrue($assign->save_submission($data, $notices), implode(' ', $notices));
        $this->setAdminUser();
    }

    /**
     * CT-06, CT-07 and CT-08: each area of the matrix lands in its category; the categories add up to A.
     */
    public function test_categories(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $coursecontext = \context_course::instance($course->id);

        // CT-06: materials in the course summary, a section, File, Folder, Page, Book, Label and a text block.
        $this->add_file($coursecontext, 'course', 'summary', self::content(1000, 'summary'));
        $this->add_file($coursecontext, 'course', 'section', self::content(1100, 'section'));
        $this->add_file($this->module('resource', $course), 'mod_resource', 'content', self::content(1200, 'resource'));
        $this->add_file($this->module('folder', $course), 'mod_folder', 'content', self::content(1300, 'folder'));
        $this->add_file($this->module('page', $course), 'mod_page', 'content', self::content(1400, 'page'));
        $this->add_file($this->module('book', $course), 'mod_book', 'chapter', self::content(1500, 'book'));
        $this->add_file($this->module('label', $course), 'mod_label', 'intro', self::content(1600, 'label'));
        $html = $this->getDataGenerator()->create_block('html', ['parentcontextid' => $coursecontext->id]);
        $this->add_file(\context_block::instance($html->id), 'block_html', 'content', self::content(1700, 'html'));

        // CT-07: a file and an online text with an image submitted through the assignment, and an essay attachment
        // of a quiz attempt (rule 21).
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->module('assign', $course, [
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignsubmission_file_maxsizebytes' => self::MB,
            'assignsubmission_onlinetext_enabled' => 1,
        ]);
        $this->submit($assign, $student, self::content(2000, 'work'), self::content(2100, 'image'));
        $this->add_file($this->module('quiz', $course), 'question', 'response_attachments', self::content(2200, 'essay'));

        // CT-08: feedback file, feedback comment with a file, grade feedback and a forum attachment.
        $this->add_file($assign, 'assignfeedback_file', 'feedback_files', self::content(3000, 'feedback'));
        $this->add_file($assign, 'assignfeedback_comments', 'feedback', self::content(3100, 'comment'));
        $this->add_file($assign, 'grade', 'feedback', self::content(3200, 'grade'));
        $this->add_file($this->module('forum', $course), 'mod_forum', 'attachment', self::content(3300, 'forum'));

        $record = $this->analyse($course);
        $categories = $this->categories($record);

        $this->assertSame(['material', 'other', 'submission'], array_keys($categories));
        $this->assertSame(self::totals(8, 10800, 8, 10800), $categories['material']);
        $this->assertSame(self::totals(3, 6300, 3, 6300), $categories['submission']);
        $this->assertSame(self::totals(4, 12600, 4, 12600), $categories['other']);
        $this->assertSame(29700, (int)$record->logicalbytes);
        $this->assertSame(15, (int)$record->filecount);
        $this->assertSame(0, (int)$record->unclassifiedcount);
        $this->assertSame(0, (int)$record->partial);
    }

    /**
     * CT-11: a third-party area is unclassified, still counted in A and B, and marks the result as partial.
     */
    public function test_unclassified_component(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $page = $this->module('page', $course);
        $this->add_file($page, 'mod_page', 'content', self::content(1000, 'page'));
        $this->add_file($page, 'local_fake', 'stuff', self::content(4000, 'fake'));

        $record = $this->analyse($course);
        $categories = $this->categories($record);
        $this->assertSame(5000, (int)$record->logicalbytes);
        $this->assertSame(5000, (int)$record->uniquebytes);
        $this->assertSame(1, (int)$record->unclassifiedcount);
        $this->assertSame(4000, (int)$record->unclassifiedbytes);
        $this->assertSame(1, (int)$record->partial);
        $this->assertSame(self::totals(1, 4000, 1, 4000), $categories['unclassified']);
        $this->assertSame(1000, $categories['material']['logicalbytes']);
    }

    /**
     * CT-21: the same content in a material and a submission counts in both categories and once in B.
     */
    public function test_same_content_in_two_categories(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $content = self::content(10 * self::MB, 'shared');
        $this->add_file($this->module('resource', $course), 'mod_resource', 'content', $content);
        $this->add_file($this->module('assign', $course), 'assignsubmission_file', 'submission_files', $content);

        $record = $this->analyse($course);
        $categories = $this->categories($record);
        $this->assertSame(20 * self::MB, (int)$record->logicalbytes);
        $this->assertSame(10 * self::MB, (int)$record->uniquebytes);
        $this->assertSame(10 * self::MB, $categories['material']['uniquebytes']);
        $this->assertSame(10 * self::MB, $categories['submission']['uniquebytes']);
        $this->assertGreaterThan(
            (int)$record->uniquebytes,
            $categories['material']['uniquebytes'] + $categories['submission']['uniquebytes']
        );
    }

    /**
     * Stored activity rows of a course result, keyed by cmid, with integer values.
     *
     * @param \stdClass $record Diagnostic record.
     * @return array<int, array<string, int>>
     */
    private function activities(\stdClass $record): array {
        $rows = [];
        foreach ((new diagnostic_store())->get_activities((int)$record->id) as $cmid => $row) {
            foreach (\block_coursecare\local\analysis\diagnostic_result::ACTIVITY_FIELDS as $field) {
                $rows[$cmid][$field] = (int)$row->$field;
            }
        }
        return $rows;
    }

    /**
     * Stored large-file links of a course result, largest first.
     *
     * @param \stdClass $record Diagnostic record.
     * @return array
     */
    private function links(\stdClass $record): array {
        global $DB;
        return array_values($DB->get_records(
            'block_coursecare_large',
            ['diagid' => $record->id],
            'filesize DESC, fileid ASC',
            'id, fileid, cmid, filesize'
        ));
    }

    /**
     * Add a big file written in chunks, so that the test never holds its whole content in memory.
     *
     * @param \context $context Context.
     * @param string $component Component.
     * @param string $filearea Area.
     * @param int $bytes Size.
     * @param string $seed Seed of the content.
     * @return \stored_file
     */
    private function add_big_file(\context $context, string $component, string $filearea, int $bytes, string $seed): \stored_file {
        $path = make_request_directory() . '/big.bin';
        $handle = fopen($path, 'wb');
        $chunk = self::content(self::MB, $seed);
        for ($written = 0; $written < $bytes; $written += self::MB) {
            fwrite($handle, substr($chunk, 0, min(self::MB, $bytes - $written)));
        }
        fclose($handle);
        $this->filecounter++;
        return get_file_storage()->create_file_from_pathname([
            'contextid' => $context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => 0,
            'filepath' => '/', 'filename' => "file{$this->filecounter}.bin",
        ], $path);
    }

    /**
     * Add another record with the content of an existing file.
     *
     * @param \stored_file $file Existing file.
     * @param \context $context Context of the new record.
     * @param string $component Component.
     * @param string $filearea Area.
     * @return \stored_file
     */
    private function copy_file(\stored_file $file, \context $context, string $component, string $filearea): \stored_file {
        $this->filecounter++;
        return get_file_storage()->create_file_from_storedfile([
            'contextid' => $context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => 0,
            'filepath' => '/', 'filename' => "file{$this->filecounter}.bin",
        ], $file);
    }

    /**
     * US7 scenario 1 and FR-019: one row per activity; a block of an activity goes to the activity, the course
     * and its blocks go to cmid 0; the activities add up to A.
     */
    public function test_activity_rows(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $coursecontext = \context_course::instance($course->id);
        $contexts = [];
        foreach ([100, 10, 1] as $size) {
            $context = $this->module('resource', $course);
            $this->add_big_file($context, 'mod_resource', 'content', $size * self::MB, "r{$size}");
            $contexts[$size] = $context;
        }
        $inactivity = $this->getDataGenerator()->create_block('html', [
            'parentcontextid' => $contexts[1]->id,
            'pagetypepattern' => 'mod-resource-*',
        ]);
        $this->add_file(\context_block::instance($inactivity->id), 'block_html', 'content', self::content(3000, 'inmod'));
        $this->add_file($coursecontext, 'course', 'summary', self::content(1000, 'summary'));
        $incourse = $this->getDataGenerator()->create_block('html', ['parentcontextid' => $coursecontext->id]);
        $this->add_file(\context_block::instance($incourse->id), 'block_html', 'content', self::content(2000, 'incourse'));

        $record = $this->analyse($course);
        $activities = $this->activities($record);
        $this->assertCount(4, $activities);
        $this->assertSame(100 * self::MB, $activities[$contexts[100]->instanceid]['logicalbytes']);
        $this->assertSame(1, $activities[$contexts[100]->instanceid]['largecount']);
        $this->assertSame(10 * self::MB, $activities[$contexts[10]->instanceid]['logicalbytes']);
        $this->assertSame(self::MB + 3000, $activities[$contexts[1]->instanceid]['logicalbytes']);
        $this->assertSame(2, $activities[$contexts[1]->instanceid]['filecount']);
        $this->assertSame(3000, $activities[0]['logicalbytes']);
        $this->assertSame(3000, $activities[0]['materialbytes']);
        $this->assertSame((int)$record->logicalbytes, array_sum(array_column($activities, 'logicalbytes')));
        $this->assertSame(1, (int)$record->largecount);
        $this->assertCount(1, $this->links($record));
    }

    /**
     * US7 scenario 2: large submissions count in D and in the activity, but get no link (D10).
     */
    public function test_large_submissions_without_links(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $assign = $this->module('assign', $course);
        $this->add_big_file($assign, 'assignsubmission_file', 'submission_files', 60 * self::MB, 's60');
        $this->add_big_file($assign, 'assignsubmission_file', 'submission_files', 70 * self::MB, 's70');

        $record = $this->analyse($course);
        $this->assertSame(2, (int)$record->largecount);
        $this->assertSame(130 * self::MB, (int)$record->largebytes);
        $this->assertSame(2, (int)$record->largeuniquecount);
        $this->assertSame(130 * self::MB, (int)$record->largeuniquebytes);
        $this->assertSame(0, (int)$record->largelinkstruncated);
        $this->assertSame([], $this->links($record));
        $activity = $this->activities($record)[$assign->instanceid];
        $this->assertSame(2, $activity['largecount']);
        $this->assertSame(130 * self::MB, $activity['submissionbytes']);
    }

    /**
     * CT-33: the threshold is inclusive; one byte below it is not a large file.
     */
    public function test_large_threshold_boundary(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $resource = $this->module('resource', $course);
        $this->add_big_file($resource, 'mod_resource', 'content', 52428799, 'below');
        $file = $this->add_big_file($resource, 'mod_resource', 'content', 52428800, 'at');

        $record = $this->analyse($course);
        $this->assertSame(52428800, (int)$record->largethreshold);
        $this->assertSame(1, (int)$record->largecount);
        $this->assertSame(52428800, (int)$record->largebytes);
        $links = $this->links($record);
        $this->assertCount(1, $links);
        $this->assertSame((int)$file->get_id(), (int)$links[0]->fileid);
        $this->assertSame((int)$resource->instanceid, (int)$links[0]->cmid);
        $this->assertSame(52428800, (int)$links[0]->filesize);
    }

    /**
     * CT-34: the same 60 MB content in two resources is two large records and one large content.
     */
    public function test_large_same_content(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $file = $this->add_big_file($this->module('resource', $course), 'mod_resource', 'content', 60 * self::MB, 'same');
        $this->copy_file($file, $this->module('resource', $course), 'mod_resource', 'content');

        $record = $this->analyse($course);
        $this->assertSame(2, (int)$record->largecount);
        $this->assertSame(120 * self::MB, (int)$record->largebytes);
        $this->assertSame(1, (int)$record->largeuniquecount);
        $this->assertSame(60 * self::MB, (int)$record->largeuniquebytes);
        $this->assertCount(2, $this->links($record));
    }

    /**
     * CT-35: a new threshold does not change the current result; the next analysis uses it.
     */
    public function test_threshold_change(): void {
        $this->resetAfterTest();
        $course = $this->course_with_block();
        $this->add_big_file($this->module('resource', $course), 'mod_resource', 'content', 60 * self::MB, 'm60');

        $record = $this->analyse($course);
        $this->assertSame(50 * self::MB, (int)$record->largethreshold);
        $this->assertSame(1, (int)$record->largecount);

        set_config('largefilethreshold', 100, 'block_coursecare');
        $current = (new diagnostic_store())->get_summary($course->id);
        $this->assertSame(50 * self::MB, (int)$current->largethreshold);
        $this->assertSame(1, (int)$current->largecount);

        $record = $this->analyse($course);
        $this->assertSame(100 * self::MB, (int)$record->largethreshold);
        $this->assertSame(0, (int)$record->largecount);
        $this->assertSame([], $this->links($record));
    }

    /**
     * D10: above 1,000 large material files, 1,000 links are kept and the result says so; totals keep all files.
     */
    public function test_large_links_limit(): void {
        $this->resetAfterTest();
        set_config('largefilethreshold', 1, 'block_coursecare');
        $course = $this->course_with_block();
        $resource = $this->module('resource', $course);
        $file = $this->add_big_file($resource, 'mod_resource', 'content', self::MB, 'many');
        for ($i = 0; $i < \block_coursecare\local\analysis\indicator_calculator::MAX_LARGE_LINKS; $i++) {
            $this->copy_file($file, $resource, 'mod_resource', 'content');
        }

        $record = $this->analyse($course);
        $this->assertSame(1001, (int)$record->largecount);
        $this->assertSame(1001 * self::MB, (int)$record->largebytes);
        $this->assertSame(1, (int)$record->largeuniquecount);
        $this->assertSame(1, (int)$record->largelinkstruncated);
        $this->assertCount(1000, $this->links($record));
    }
}
