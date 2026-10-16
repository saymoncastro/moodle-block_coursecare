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
     * The block allows a single instance per page and has no own settings.
     */
    public function test_single_instance_and_no_settings(): void {
        $block = block_instance('coursecare');

        $this->assertFalse($block->instance_allow_multiple());
        $this->assertFalse($block->has_config());
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

        $tagline = get_string('tagline', 'block_coursecare');
        $intro = get_string('intro', 'block_coursecare');
        $this->assertNotFalse(strpos($html, $tagline));
        $this->assertNotFalse(strpos($html, $intro));
        $this->assertLessThan(strpos($html, $intro), strpos($html, $tagline));

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
        $this->assertSame('', trim(preg_replace('/<[^>]+>/', '', $markup)));
    }
}
