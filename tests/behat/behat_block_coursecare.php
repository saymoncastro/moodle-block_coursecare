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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps of the CourseCare block: named pages.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_coursecare extends behat_base {
    /**
     * Pages of an instance: "overview", identified by the short name of the course that has the block.
     *
     * Used as: I am on the "C1" "block_coursecare > overview" page.
     *
     * @param string $type Page type.
     * @param string $identifier Course short name.
     * @return moodle_url
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        global $DB;

        switch (strtolower($type)) {
            case 'overview':
                $courseid = $this->get_course_id($identifier);
                $instanceid = $DB->get_field('block_instances', 'id', ['blockname' => 'coursecare',
                    'parentcontextid' => context_course::instance($courseid)->id], MUST_EXIST);
                return new moodle_url('/blocks/coursecare/overview.php', ['id' => $instanceid]);
            default:
                throw new Exception('Unrecognised block_coursecare page type "' . $type . '."');
        }
    }
}
