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

namespace block_coursecare\local;

use block_coursecare\local\collector\eligibility;

/**
 * Access check of the overview page (contracts/overview-page.md, "Endereço e acesso"; FR-010).
 *
 * The course shown is always the parent course of the block instance. The checks run in the order of the contract
 * and before anything of the diagnostic is read; any failure ends with the standard Moodle exception.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview_access {
    /**
     * Require access to the overview page of a block instance.
     *
     * @param int $instanceid Block instance id (the "id" parameter of the page).
     * @return array{0: \stdClass, 1: \context_block} The course of the block and the block context.
     * @throws \moodle_exception When the instance, its course, the login or the capability is missing.
     */
    public static function require_access(int $instanceid): array {
        $instance = (new eligibility())->get_course_instance($instanceid);
        if ($instance === null) {
            throw new \dml_missing_record_exception('block_instances');
        }
        $parent = \context::instance_by_id((int)$instance->parentcontextid, IGNORE_MISSING);
        // SITEID holds the id as read from the database (a string): compare as integers.
        if (!$parent instanceof \context_course || (int)$parent->instanceid === (int)SITEID) {
            throw new \dml_missing_record_exception('block_instances');
        }
        $course = get_course((int)$parent->instanceid);
        require_login($course, false);
        $context = \context_block::instance($instanceid);
        require_capability('block/coursecare:view', $context);
        return [$course, $context];
    }
}
