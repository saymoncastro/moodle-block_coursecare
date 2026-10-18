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

namespace block_coursecare;

use context_block;
use context_course;
use moodle_page;
use stdClass;

/**
 * Tests for the CourseCare block definition, permissions, display rules and initial content.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare
 */
final class block_coursecare_test extends \advanced_testcase {
    /**
     * Load the block library.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        require_once($CFG->libdir . '/blocklib.php');
        parent::setUpBeforeClass();
    }

    /**
     * Create a CourseCare block in a course and load it as it would be on a page of the given type.
     *
     * @param stdClass $course The course.
     * @param string $pagetype The page type the block is rendered on.
     * @param stdClass|null $record Existing block instance record, or null to create one.
     * @return \block_coursecare
     */
    private function get_block(stdClass $course, string $pagetype, ?stdClass $record = null): \block_coursecare {
        if ($record === null) {
            $record = $this->getDataGenerator()->create_block('coursecare', [
                'parentcontextid' => context_course::instance($course->id)->id,
                'pagetypepattern' => 'course-view-*',
            ]);
        }

        $page = new moodle_page();
        $page->set_course($course);
        $page->set_pagetype($pagetype);

        $block = block_instance('coursecare', $record, $page);
        $this->assertInstanceOf(\block_coursecare::class, $block);

        return $block;
    }

    /**
     * The block can only be added to the course main page, never to section pages.
     */
    public function test_applicable_formats(): void {
        $block = block_instance('coursecare');

        $this->assertSame(['course-view' => true, 'course-view-section' => false], $block->applicable_formats());
        $this->assertTrue(blocks_name_allowed_in_format('coursecare', 'course-view-topics'));
        $this->assertTrue(blocks_name_allowed_in_format('coursecare', 'course-view-weeks'));
        $this->assertFalse(blocks_name_allowed_in_format('coursecare', 'course-view-section-topics'));
        $this->assertFalse(blocks_name_allowed_in_format('coursecare', 'my-index'));
        $this->assertFalse(blocks_name_allowed_in_format('coursecare', 'site-index'));
        $this->assertFalse(blocks_name_allowed_in_format('coursecare', 'mod-page-view'));
    }

    /**
     * The block allows a single instance per page, has site settings (Feature 002) and no per-instance settings.
     */
    public function test_single_instance_and_settings(): void {
        $block = block_instance('coursecare');

        $this->assertFalse($block->instance_allow_multiple());
        $this->assertTrue($block->has_config());
        $this->assertFalse($block->instance_allow_config());
    }

    /**
     * Data provider for {@see test_view_capability_by_archetype}.
     *
     * @return array
     */
    public static function view_capability_by_archetype_provider(): array {
        return [
            'editing teacher' => ['editingteacher', true],
            'manager' => ['manager', true],
            'non-editing teacher' => ['teacher', false],
            'student' => ['student', false],
        ];
    }

    /**
     * Only the default archetypes that edit the course see the block content.
     *
     * @dataProvider view_capability_by_archetype_provider
     * @param string $role Role shortname.
     * @param bool $expected Whether the content is generated.
     */
    public function test_view_capability_by_archetype(string $role, bool $expected): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course, $role);
        $this->setUser($user);

        $block = $this->get_block($course, 'course-view-topics');

        $this->assertSame($expected, $block->get_content()->text !== '');
        $this->assertSame(!$expected, $block->is_empty());
    }

    /**
     * Guests do not see the block content.
     */
    public function test_guest_does_not_see_content(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setGuestUser();

        $block = $this->get_block($course, 'course-view-topics');

        $this->assertSame('', $block->get_content()->text);
    }

    /**
     * Permission overrides in the block context are respected without checking role names.
     */
    public function test_view_capability_overrides(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $editor = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $assistant = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $record = $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        $blockcontext = context_block::instance($record->id);

        $editingteacherrole = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $teacherrole = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        assign_capability('block/coursecare:view', CAP_PROHIBIT, $editingteacherrole, $blockcontext->id);
        assign_capability('block/coursecare:view', CAP_ALLOW, $teacherrole, $blockcontext->id);
        accesslib_clear_all_caches_for_unit_testing();

        $this->setUser($editor);
        $this->assertSame('', $this->get_block($course, 'course-view-topics', $record)->get_content()->text);

        $this->setUser($assistant);
        $this->assertNotSame('', $this->get_block($course, 'course-view-topics', $record)->get_content()->text);
    }

    /**
     * Data provider for {@see test_display_restricted_to_course_main_page}.
     *
     * @return array
     */
    public static function display_by_page_type_provider(): array {
        return [
            'course main page (topics)' => ['course-view-topics', true],
            'course main page (weeks)' => ['course-view-weeks', true],
            'section page' => ['course-view-section-topics', false],
            'activity page' => ['mod-page-view', false],
            'course settings page' => ['course-edit', false],
        ];
    }

    /**
     * Content and editing controls exist only on the course main page.
     *
     * @dataProvider display_by_page_type_provider
     * @param string $pagetype Page type.
     * @param bool $expected Whether the block shows content and can be edited there.
     */
    public function test_display_restricted_to_course_main_page(string $pagetype, bool $expected): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));

        $block = $this->get_block($course, $pagetype);

        $this->assertSame($expected, $block->get_content()->text !== '');
        $this->assertSame($expected, $block->instance_can_be_edited());
    }

    /**
     * The initial content shows the identity sentence and the message, and nothing that looks like an analysis.
     */
    public function test_initial_content(): void {
        global $CFG;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));

        $html = $this->get_block($course, 'course-view-topics')->get_content()->text;

        // Feature 003: the identity sentence stays; the message announcing upcoming guidance is gone.
        $tagline = get_string('tagline', 'block_coursecare');
        $this->assertNotFalse(strpos($html, $tagline));
        $this->assertFalse(get_string_manager()->string_exists('intro', 'block_coursecare'));
        $this->assertStringNotContainsString('Soon you will find guidance', $html);

        // No numbers, indicators, links or actions in the visible text.
        $this->assertDoesNotMatchRegularExpression('/\d/', strip_tags($html));
        foreach (['<a', '<button', '<form', '<input'] as $element) {
            $this->assertStringNotContainsString($element, $html);
        }

        // The decorative asset comes from the Pix Icon API and is hidden from assistive technology.
        $this->assertMatchesRegularExpression('/class="[^"]*\bicon\b[^"]*"/', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('sprout', $html);

        // No hand-written image markup or interface text in the template.
        $template = file_get_contents($CFG->dirroot . '/blocks/coursecare/templates/content.mustache');
        $markup = preg_replace('/\{\{!.*?\}\}/s', '', $template);
        $this->assertStringNotContainsString('<img', $markup);
        $markup = preg_replace('/\{\{#(str|pix)\}\}.*?\{\{\/\1\}\}/s', '', $markup);
        // Feature 002: the file overview line comes as already translated sentences, so only Mustache tags remain.
        $markup = preg_replace('/\{\{[^}]*\}\}/', '', $markup);
        $this->assertSame('', trim(preg_replace('/<[^>]+>/', '', $markup)));
    }

    /**
     * Add the block to a course page through the block manager, as the "Add a block" flow does.
     *
     * @param stdClass $course Course.
     * @return \block_base
     */
    private function add_block_through_manager(stdClass $course): \block_base {
        $page = new moodle_page();
        $page->set_course($course);
        $page->set_pagetype('course-view-topics');
        $page->blocks->add_region('side-pre');
        $page->blocks->add_block('coursecare', 'side-pre', 0, false, 'course-view-*');
        return block_instance('coursecare', $GLOBALS['DB']->get_record('block_instances', ['blockname' => 'coursecare',
            'parentcontextid' => context_course::instance($course->id)->id]), $page);
    }

    /**
     * FR-036: adding the block requests the first analysis and queues exactly one task; nothing is analysed now.
     */
    public function test_adding_the_block_queues_first_analysis(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();

        $this->add_block_through_manager($course);

        $tasks = \core\task\manager::get_adhoc_tasks(\block_coursecare\task\analyse_course::class);
        $this->assertCount(1, $tasks);
        $this->assertEquals((object)['courseid' => (int)$course->id], reset($tasks)->get_custom_data());
        $record = (new \block_coursecare\local\collector\diagnostic_store())->get_summary($course->id);
        $this->assertSame(\block_coursecare\local\collector\diagnostic_store::STATUS_QUEUED, (int)$record->requeststatus);
        $this->assertNull($record->timecompleted);
    }

    /**
     * After the task runs, the block shows the recorded volume and the date; before, it says it is being prepared.
     */
    public function test_block_shows_volume_after_analysis(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        get_file_storage()->create_file_from_string(['contextid' => \context_module::instance($page->cmid)->id,
            'component' => 'mod_page', 'filearea' => 'content', 'itemid' => 0, 'filepath' => '/',
            'filename' => 'a.bin'], str_repeat('a', 2048));

        $block = $this->add_block_through_manager($course);
        $html = $this->get_block($course, 'course-view-topics', $block->instance)->get_content()->text;
        $this->assertStringContainsString(get_string('diagnostic:preparing', 'block_coursecare'), $html);

        ob_start();
        $this->runAdhocTasks(\block_coursecare\task\analyse_course::class);
        ob_end_clean();

        $html = $this->get_block($course, 'course-view-topics', $block->instance)->get_content()->text;
        $this->assertStringContainsString(get_string('overview:files', 'block_coursecare'), $html);
        $this->assertStringContainsString(\block_coursecare\output\diagnostic_line::size(2048), $html);
        $this->assertStringContainsString(substr(get_string('diagnostic:calculatedon', 'block_coursecare', ''), 0, 10), $html);
    }

    /**
     * A block on the site course does not request any analysis.
     */
    public function test_site_course_block_queues_nothing(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $page = new moodle_page();
        $page->set_course(get_site());
        $page->set_pagetype('site-index');
        $page->blocks->add_region('side-pre');
        $page->blocks->add_block('coursecare', 'side-pre', 0, false, '*');
        $this->assertCount(0, \core\task\manager::get_adhoc_tasks(\block_coursecare\task\analyse_course::class));
    }

    /**
     * SC-002 and FR-034: in every state, rendering reads the stored result only, with no query to the files table.
     */
    public function test_render_reads_no_files(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $instance = $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);

        $now = time();
        $result = ['timecompleted' => $now - HOURSECS, 'timeattempted' => $now - HOURSECS, 'methodversion' => 1,
            'filecount' => 3, 'logicalbytes' => 6291456, 'partial' => 0];
        $failure = ['lastattemptfailed' => 1, 'failurecount' => 1, 'failurereason' => 'dml_read_exception'];
        $volume = \block_coursecare\output\diagnostic_line::size(6291456);
        $states = [
            'A not analysed' => [null, get_string('diagnostic:notavailable', 'block_coursecare')],
            'B queued' => [['requeststatus' => 1, 'timerequested' => $now],
                get_string('diagnostic:preparing', 'block_coursecare')],
            'C running' => [['requeststatus' => 2, 'timerequested' => $now, 'timestarted' => $now],
                get_string('diagnostic:preparing', 'block_coursecare')],
            'D complete' => [$result, $volume],
            'E outdated' => [['timecompleted' => $now - 4 * DAYSECS] + $result,
                get_string('diagnostic:mayoutdated', 'block_coursecare')],
            'F failed with result' => [$failure + $result, get_string('diagnostic:updatefailed', 'block_coursecare')],
            'F failed without result' => [$failure, get_string('diagnostic:notavailable', 'block_coursecare')],
            'G no files' => [['filecount' => 0, 'logicalbytes' => 0] + $result,
                get_string('diagnostic:nofiles', 'block_coursecare')],
            'H partial' => [['partial' => 1] + $result, $volume],
        ];

        // Feature 003: the link to the overview page appears only when a result exists.
        $withlink = ['D complete', 'E outdated', 'F failed with result', 'G no files', 'H partial'];
        $link = s(get_string('overview:link', 'block_coursecare'));
        $url = s((new \moodle_url('/blocks/coursecare/overview.php', ['id' => $instance->id]))->out(false));

        // One rendering first, so that string, configuration and template caches are warm in every measured state.
        $this->get_block($course, 'course-view-topics', $instance)->get_content();
        $reads = [];
        foreach ($states as $name => [$values, $expected]) {
            $DB->delete_records('block_coursecare_diag', ['courseid' => $course->id]);
            if ($values !== null) {
                $DB->insert_record('block_coursecare_diag', (object)($values + ['courseid' => $course->id,
                    'requeststatus' => 0, 'lastattemptfailed' => 0, 'failurecount' => 0, 'timemodified' => $now]));
            }
            $block = $this->get_block($course, 'course-view-topics', $instance);
            $before = $DB->perf_get_reads();
            $DB->set_debug(true);
            ob_start();
            $html = $block->get_content()->text;
            $queries = ob_get_clean();
            $DB->set_debug(false);
            $reads[$name] = $DB->perf_get_reads() - $before;

            $this->assertStringContainsString(s($expected), $html, $name);
            if (in_array($name, $withlink, true)) {
                $this->assertStringContainsString($link, $html, $name);
                $this->assertStringContainsString('href="' . $url . '"', $html, $name);
            } else {
                $this->assertStringNotContainsString($link, $html, $name);
                $this->assertStringNotContainsString('overview.php', $html, $name);
            }
            $this->assertStringContainsString($CFG->prefix . 'block_coursecare_diag', $queries, $name);
            $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($CFG->prefix, '/') . 'files\b/', $queries, $name);
        }
        // The same light reads in every state: the state never adds a query.
        $this->assertCount(1, array_unique($reads), json_encode($reads));
    }

    /**
     * Create a course with a block, a diagnostic and a pending analysis task.
     *
     * @return array{0: stdClass, 1: \block_base}
     */
    private function analysed_course_with_pending_task(): array {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $block = $this->add_block_through_manager($course);
        ob_start();
        $this->runAdhocTasks(\block_coursecare\task\analyse_course::class);
        ob_end_clean();
        $store = new \block_coursecare\local\collector\diagnostic_store();
        $this->assertNotNull($store->get_summary($course->id)->timecompleted);
        \block_coursecare\task\analyse_course::request($course->id, time());
        return [$course, $block];
    }

    /**
     * CT-26: removing the only instance deletes the diagnostic; the pending task then ends without writing.
     */
    public function test_removing_the_block_deletes_the_diagnostic(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $block] = $this->analysed_course_with_pending_task();
        $diagid = $DB->get_field('block_coursecare_diag', 'id', ['courseid' => $course->id]);
        $DB->insert_record('block_coursecare_cat', ['diagid' => $diagid, 'category' => 'material', 'filecount' => 0,
            'logicalbytes' => 0, 'uniquecount' => 0, 'uniquebytes' => 0]);

        blocks_delete_instance($block->instance);

        $this->assertFalse($DB->record_exists('block_coursecare_diag', ['courseid' => $course->id]));
        $this->assertFalse($DB->record_exists('block_coursecare_cat', ['diagid' => $diagid]));
        ob_start();
        $this->runAdhocTasks(\block_coursecare\task\analyse_course::class);
        ob_end_clean();
        $this->assertFalse($DB->record_exists('block_coursecare_diag', ['courseid' => $course->id]));
    }

    /**
     * With two instances in the same course (possible only by code), removing one keeps the diagnostic.
     */
    public function test_removing_one_of_two_instances_keeps_the_diagnostic(): void {
        $this->resetAfterTest();
        [$course, $block] = $this->analysed_course_with_pending_task();
        $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        blocks_delete_instance($block->instance);
        $this->assertNotNull((new \block_coursecare\local\collector\diagnostic_store())->get_summary($course->id));
    }

    /**
     * CT-20: deleting a course with a diagnostic and a pending task removes the data; the task ends without error.
     */
    public function test_deleting_the_course(): void {
        global $DB;
        $this->resetAfterTest();
        [$course] = $this->analysed_course_with_pending_task();
        ob_start();
        delete_course($course->id, false);
        $this->runAdhocTasks(\block_coursecare\task\analyse_course::class);
        ob_end_clean();
        $this->assertSame(0, $DB->count_records('block_coursecare_diag'));
        $this->assertCount(0, \core\task\manager::get_adhoc_tasks(\block_coursecare\task\analyse_course::class));
    }

    /**
     * CT-27: disabling and enabling the block type keeps the diagnostic and the course eligible.
     */
    public function test_disabling_the_block_type_keeps_the_diagnostic(): void {
        $this->resetAfterTest();
        [$course] = $this->analysed_course_with_pending_task();
        \core\plugininfo\block::enable_plugin('coursecare', 0);
        $store = new \block_coursecare\local\collector\diagnostic_store();
        $this->assertNotNull($store->get_summary($course->id));
        $this->assertTrue((new \block_coursecare\local\collector\eligibility())->is_eligible($course->id));
        \core\plugininfo\block::enable_plugin('coursecare', 1);
        $this->assertNotNull($store->get_summary($course->id)->timecompleted);
    }

    /**
     * Edge case of the spec: a block removed and added again shows no data from before the removal.
     */
    public function test_block_added_again_starts_clean(): void {
        $this->resetAfterTest();
        [$course, $block] = $this->analysed_course_with_pending_task();
        blocks_delete_instance($block->instance);
        $this->add_block_through_manager($course);
        $record = (new \block_coursecare\local\collector\diagnostic_store())->get_summary($course->id);
        $this->assertSame(\block_coursecare\local\collector\diagnostic_store::STATUS_QUEUED, (int)$record->requeststatus);
        $this->assertNull($record->timecompleted);
        $this->assertNull($record->logicalbytes);
    }

    /**
     * Uninstall: every plugin table is declared in install.xml, so the core uninstall drops it with the plugin.
     */
    public function test_tables_are_declared_for_uninstall(): void {
        global $CFG;
        $xmldb = new \xmldb_file($CFG->dirroot . '/blocks/coursecare/db/install.xml');
        $this->assertTrue($xmldb->loadXMLStructure());
        $names = array_map(fn($table) => $table->getName(), $xmldb->getStructure()->getTables());
        $this->assertSame(
            ['block_coursecare_diag', 'block_coursecare_cat', 'block_coursecare_act', 'block_coursecare_large'],
            $names
        );
    }
}
