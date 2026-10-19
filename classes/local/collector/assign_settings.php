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
 * Current settings of the file submissions of the assignments of a course (Feature 005,
 * contracts/potential-calculation.md).
 *
 * Reads settings and who can submit, never submissions, grades or file records (of the grade item, only its type and
 * the grade to pass, which decide whether "until pass" reopens). The participants are counted once in the course; an
 * assignment gets its own count only when its context has an override of the submit capability or a role assigned
 * in it, because otherwise the capability in the assignment is the one of the course (research R2).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_settings {
    /** Capability a participant needs to submit in an assignment. */
    private const SUBMIT = 'mod/assign:submit';

    /**
     * Assignments of the course whose stored maximum of attempts is unlimited (-1), in the order of the course: the
     * candidates of the Feature 009 suggestion. A necessary condition only, read in one indexed query; every other
     * condition is decided by storage_potential with the settings of for_course().
     *
     * @param int $courseid Course id.
     * @return int[] Course module ids.
     */
    public function unlimited_attempts(int $courseid): array {
        global $DB;
        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_instances_of('assign');
        if (!$cms) {
            return [];
        }
        $ids = array_flip(array_map('intval', $DB->get_fieldset_select(
            'assign',
            'id',
            'course = :course AND maxattempts = :unlimited',
            ['course' => $courseid, 'unlimited' => -1]
        )));
        $cmids = [];
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->modname === 'assign' && isset($ids[(int)$cm->instance])) {
                $cmids[] = (int)$cm->id;
            }
        }
        return $cmids;
    }

    /**
     * Settings of the given assignments and of their course.
     *
     * @param int $courseid Course id.
     * @param int[] $cmids Course module ids of assignments of the course.
     * @return array assigns (cmid => enabled, maxfiles, activitybytes, maxattempts, reopenmethod, gradetype,
     *     gradepass, grademin, teamsubmission, groupingid, preventnogroup, participants), limits (server, site, course,
     *     default), memberships (userid => group ids, only when an assignment has group submission) and groupings
     *     (grouping id => group ids). enabled is false when the file submission plugin is disabled on the site, as
     *     Moodle then offers no file submission; gradetype, gradepass and grademin are null without a grade item.
     */
    public function for_course(int $courseid, array $cmids): array {
        global $CFG, $DB;
        $modinfo = get_fast_modinfo($courseid);
        $result = [
            'assigns' => [],
            'limits' => [
                'server' => (int)get_max_upload_file_size(),
                'site' => (int)$CFG->maxbytes,
                'course' => (int)$modinfo->get_course()->maxbytes,
                'default' => (int)get_config('assignsubmission_file', 'maxbytes'),
            ],
            'memberships' => [],
            'groupings' => [],
        ];
        $instances = [];
        foreach ($cmids as $cmid) {
            $instances[(int)$modinfo->get_cm($cmid)->instance] = (int)$cmid;
        }
        if (!$instances) {
            return $result;
        }

        $assigns = $DB->get_records_list(
            'assign',
            'id',
            array_keys($instances),
            '',
            'id, maxattempts, attemptreopenmethod, teamsubmission, teamsubmissiongroupingid, preventsubmissionnotingroup'
        );
        $config = $this->file_config(array_keys($instances));
        $sitedisabled = (bool)get_config('assignsubmission_file', 'disabled');
        $gradeitems = $this->grade_items((int)$courseid, array_keys($instances));
        $participants = $this->participants(\context_course::instance($courseid));
        $own = $this->own_participants(array_values($instances));

        foreach ($assigns as $assign) {
            $cmid = $instances[(int)$assign->id];
            $values = $config[(int)$assign->id] ?? [];
            $item = $gradeitems[(int)$assign->id] ?? null;
            $result['assigns'][$cmid] = [
                'enabled' => !$sitedisabled && (int)($values['enabled'] ?? 0) === 1,
                'maxfiles' => isset($values['maxfilesubmissions']) ? (int)$values['maxfilesubmissions'] : null,
                'activitybytes' => (int)($values['maxsubmissionsizebytes'] ?? 0),
                'maxattempts' => (int)$assign->maxattempts,
                'reopenmethod' => (string)$assign->attemptreopenmethod,
                'gradetype' => $item ? (int)$item->gradetype : null,
                'gradepass' => $item && $item->gradepass !== null ? (float)$item->gradepass : null,
                'grademin' => $item ? (float)$item->grademin : null,
                'teamsubmission' => (bool)$assign->teamsubmission,
                'groupingid' => (int)$assign->teamsubmissiongroupingid,
                'preventnogroup' => (bool)$assign->preventsubmissionnotingroup,
                'participants' => $own[$cmid] ?? $participants,
            ];
        }

        $team = array_filter($result['assigns'], fn(array $assign): bool => $assign['teamsubmission']);
        if ($team) {
            $result['memberships'] = $this->memberships($courseid);
            $groupingids = array_filter(array_unique(array_column($team, 'groupingid')));
            if ($groupingids) {
                $rows = $DB->get_records_list('groupings_groups', 'groupingid', $groupingids, '', 'id, groupingid, groupid');
                foreach ($rows as $row) {
                    $result['groupings'][(int)$row->groupingid][] = (int)$row->groupid;
                }
            }
        }
        return $result;
    }

    /**
     * Settings of the file submission plugin of the assignments.
     *
     * @param int[] $assignids Assignment instance ids.
     * @return array assignment id => [name => value]
     */
    private function file_config(array $assignids): array {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($assignids, SQL_PARAMS_NAMED, 'assign');
        [$namesql, $nameparams] = $DB->get_in_or_equal(
            ['enabled', 'maxfilesubmissions', 'maxsubmissionsizebytes'],
            SQL_PARAMS_NAMED,
            'name'
        );
        $rows = $DB->get_records_select(
            'assign_plugin_config',
            "assignment $insql AND plugin = :plugin AND subtype = :subtype AND name $namesql",
            $params + $nameparams + ['plugin' => 'file', 'subtype' => 'assignsubmission'],
            '',
            'id, assignment, name, value'
        );
        $config = [];
        foreach ($rows as $row) {
            $config[(int)$row->assignment][$row->name] = $row->value;
        }
        return $config;
    }

    /**
     * The grade item of each assignment: its type, grade to pass and minimum grade (settings, never a grade).
     *
     * @param int $courseid Course id.
     * @param int[] $assignids Assignment instance ids.
     * @return array assignment id => row with gradetype, gradepass and grademin
     */
    private function grade_items(int $courseid, array $assignids): array {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($assignids, SQL_PARAMS_NAMED, 'assign');
        $rows = $DB->get_records_select(
            'grade_items',
            "courseid = :courseid AND itemtype = :itemtype AND itemmodule = :itemmodule AND itemnumber = 0
             AND iteminstance $insql",
            $params + ['courseid' => $courseid, 'itemtype' => 'mod', 'itemmodule' => 'assign'],
            '',
            'id, iteminstance, gradetype, gradepass, grademin'
        );
        $items = [];
        foreach ($rows as $row) {
            $items[(int)$row->iteminstance] = $row;
        }
        return $items;
    }

    /**
     * Users with an active enrolment who can submit in a context.
     *
     * @param \context $context Course or assignment context.
     * @return int[] User ids.
     */
    private function participants(\context $context): array {
        global $DB;
        [$sql, $params] = get_enrolled_sql($context, self::SUBMIT, 0, true);
        return array_map('intval', $DB->get_fieldset_sql($sql, $params));
    }

    /**
     * Participants of the assignments whose context defines something of its own: an override of the submit
     * capability or a role assigned in the assignment.
     *
     * @param int[] $cmids Course module ids.
     * @return array cmid => user ids, only for those assignments.
     */
    private function own_participants(array $cmids): array {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'cm');
        $sql = "SELECT ctx.instanceid
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :level
                   AND ctx.instanceid $insql
                   AND (EXISTS (SELECT 1 FROM {role_capabilities} rc
                                 WHERE rc.contextid = ctx.id AND rc.capability = :capability)
                        OR EXISTS (SELECT 1 FROM {role_assignments} ra WHERE ra.contextid = ctx.id))";
        $own = [];
        foreach ($DB->get_fieldset_sql($sql, $params + ['level' => CONTEXT_MODULE, 'capability' => self::SUBMIT]) as $cmid) {
            $own[(int)$cmid] = $this->participants(\context_module::instance((int)$cmid));
        }
        return $own;
    }

    /**
     * Groups of each member of the groups of the course.
     *
     * @param int $courseid Course id.
     * @return array userid => group ids
     */
    private function memberships(int $courseid): array {
        global $DB;
        $memberships = [];
        $rows = $DB->get_recordset_sql('SELECT gm.id, gm.userid, gm.groupid
                                          FROM {groups_members} gm
                                          JOIN {groups} g ON g.id = gm.groupid
                                         WHERE g.courseid = :courseid', ['courseid' => $courseid]);
        foreach ($rows as $row) {
            $memberships[(int)$row->userid][] = (int)$row->groupid;
        }
        $rows->close();
        return $memberships;
    }
}
