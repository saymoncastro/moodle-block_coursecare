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

namespace block_coursecare\local\collector;

/**
 * Selection of the courses eligible for the diagnostic: courses with at least one CourseCare block instance.
 *
 * This is the only selection policy of Feature 002 (FR-003, FR-003c). It is kept apart from the analysis so that
 * another policy can be adopted later without changing it. A disabled block type or a hidden instance does not
 * change eligibility (FR-003a): the block_instances rows remain.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eligibility {
    /** @var string Block name in block_instances. */
    public const BLOCKNAME = 'coursecare';

    /**
     * Whether a course is eligible: it exists, it is not the site course and it has a CourseCare instance.
     *
     * @param int $courseid Course id.
     * @return bool
     */
    public function is_eligible(int $courseid): bool {
        global $DB;

        if ($courseid <= 0 || $courseid == SITEID) {
            return false;
        }
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }
        return $DB->record_exists('block_instances', ['blockname' => self::BLOCKNAME, 'parentcontextid' => $context->id]);
    }

    /**
     * Number of CourseCare instances whose parent is a given context, optionally ignoring one instance.
     *
     * @param int $parentcontextid Parent context id.
     * @param int $excludeinstanceid Instance to ignore (0 = none).
     * @return int
     */
    public function count_instances(int $parentcontextid, int $excludeinstanceid = 0): int {
        global $DB;

        return $DB->count_records_select(
            'block_instances',
            'blockname = :blockname AND parentcontextid = :parentcontextid AND id <> :excludeid',
            ['blockname' => self::BLOCKNAME, 'parentcontextid' => $parentcontextid, 'excludeid' => $excludeinstanceid]
        );
    }

    /**
     * SQL selecting the ids of the eligible courses, as a subquery returning one column "courseid".
     *
     * Uses the blockname index of block_instances and the primary key of context.
     *
     * @param string $prefix Parameter prefix, unique in the statement where the subquery is used.
     * @return array{0: string, 1: array} SQL and named parameters.
     */
    public function eligible_courses_sql(string $prefix = 'elig'): array {
        $sql = "SELECT DISTINCT ectx.instanceid AS courseid
                  FROM {block_instances} ebi
                  JOIN {context} ectx ON ectx.id = ebi.parentcontextid AND ectx.contextlevel = :{$prefix}level
                 WHERE ebi.blockname = :{$prefix}name AND ectx.instanceid <> :{$prefix}site";
        return [$sql, ["{$prefix}level" => CONTEXT_COURSE, "{$prefix}name" => self::BLOCKNAME, "{$prefix}site" => SITEID]];
    }
}
