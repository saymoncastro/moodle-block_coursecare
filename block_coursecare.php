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
     * The block has no site-level settings.
     *
     * @return bool
     */
    public function has_config() {
        return false;
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

        $this->content->text = $OUTPUT->render_from_template('block_coursecare/content', []);

        return $this->content;
    }

    /**
     * Whether the current page is the course main page (course/view.php), excluding section pages.
     *
     * @return bool
     */
    private function is_main_page(): bool {
        $pagetype = $this->page->pagetype;

        return strpos($pagetype, 'course-view-') === 0 && strpos($pagetype, 'course-view-section-') !== 0;
    }
}
