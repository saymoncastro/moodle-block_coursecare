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

/**
 * CourseCare block definition.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_coursecare\local\analysis\state_resolver;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\collector\eligibility;
use block_coursecare\output\diagnostic_line;
use block_coursecare\task\analyse_course;

/**
 * CourseCare block: educational guidance on the conscious use of course resources.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursecare extends block_base {
    /**
     * Set the block title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_coursecare');
    }

    /**
     * The block has site settings (settings.php): large-file threshold, reanalysis interval and out-of-date limit.
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * The block can only be added to the course main page, never to section pages.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['course-view' => true, 'course-view-section' => false];
    }

    /**
     * Only one CourseCare block per page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Editing controls are only rendered on the course main page.
     *
     * Without content and without controls, the block manager does not render the block at all, so
     * no empty header appears on section or activity pages, even in editing mode.
     *
     * @return bool
     */
    public function instance_can_be_edited() {
        return $this->is_main_page();
    }

    /**
     * Get the block content.
     *
     * The content is generated only on the course main page and only for users with the view capability
     * in the block context.
     *
     * @return stdClass
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!$this->is_main_page() || !has_capability('block/coursecare:view', $this->context)) {
            return $this->content;
        }

        $this->content->text = $OUTPUT->render_from_template('block_coursecare/content', [
            'diagnostic' => $this->diagnostic_line(),
        ]);

        return $this->content;
    }

    /**
     * Request the first analysis of the course when the block is added (FR-036). Light: no file is read here.
     *
     * @return bool
     */
    public function instance_create() {
        $courseid = $this->course_id();
        if ($courseid !== null) {
            analyse_course::request($courseid, time());
        }
        return true;
    }

    /**
     * Delete the course diagnostic when the last CourseCare instance of the course is removed (FR-044b).
     *
     * Also called for every instance when the course is deleted or the plugin uninstalled. Light: index reads and
     * deletes only. The row of this instance still exists during the call, so it is excluded from the count.
     *
     * @return bool
     */
    public function instance_delete() {
        $courseid = $this->course_id();
        if (
            $courseid !== null
                && (new eligibility())->count_instances((int)$this->instance->parentcontextid, (int)$this->instance->id) === 0
        ) {
            (new diagnostic_store())->delete_for_course($courseid);
        }
        return true;
    }

    /**
     * Data of the file overview line for the course of this block, read from the stored result only (FR-034).
     *
     * @return array|null Template data, or null if the block is not in a course.
     */
    private function diagnostic_line(): ?array {
        $courseid = $this->course_id();
        if ($courseid === null) {
            return null;
        }
        $staleness = (int)get_config('block_coursecare', 'stalenessthreshold');
        $state = state_resolver::resolve(
            (new diagnostic_store())->get_summary($courseid),
            time(),
            $staleness > 0 ? $staleness : 3 * DAYSECS
        );
        $overviewurl = new moodle_url('/blocks/coursecare/overview.php', ['id' => $this->instance->id]);
        return (new diagnostic_line($state, $overviewurl))->export_for_template($this->page->get_renderer('core'));
    }

    /**
     * The course of this block: always taken from the block's own parent context, never from a request (FR-041).
     *
     * @return int|null Course id, or null if the block is not in a course (or is on the site course).
     */
    private function course_id(): ?int {
        $parent = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING);
        if (!$parent instanceof context_course || $parent->instanceid == SITEID) {
            return null;
        }
        return (int)$parent->instanceid;
    }

    /**
     * Whether the current page is one where the block may be added: the course main page, not section pages.
     *
     * Uses the core resolution of applicable_formats(), so the display rule and the add rule share one definition.
     *
     * @return bool
     */
    private function is_main_page(): bool {
        return blocks_name_allowed_in_format($this->name(), $this->page->pagetype);
    }
}
