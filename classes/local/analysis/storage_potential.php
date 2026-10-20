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

namespace block_coursecare\local\analysis;

/**
 * Theoretical potential configured in an assignment (Feature 005, contracts/potential-calculation.md).
 *
 * Pure rule: it receives the settings read by the assign_settings collector and decides. Potential = submitting
 * units × attempts × files per submission × effective limit per file, following what Moodle 4.5 applies (spec,
 * "Metodologia validada"). It is a ceiling of the settings, never a forecast, and it is never added up.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class storage_potential {
    /** Levels of the limit per file, in the order that wins a tie. */
    private const LIMIT_ORDER = ['course', 'site', 'server'];

    /** Grade type with points (GRADE_TYPE_VALUE); with a scale, the activity is also gradable. */
    private const GRADE_VALUE = 1;

    /** Scale grade type (GRADE_TYPE_SCALE). */
    private const GRADE_SCALE = 2;

    /**
     * Potential of one assignment.
     *
     * @param array $settings Settings of the assignment: maxfiles (?int), activitybytes, maxattempts, reopenmethod,
     *     gradetype, gradepass and grademin of its grade item (null without one), teamsubmission, groupingid,
     *     preventnogroup and participants (user ids who can submit).
     * @param array $course Settings of the course: limits (server, site, course, default), memberships (userid =>
     *     group ids) and groupings (grouping id => group ids).
     * @return array status (potential, unlimited, nofiles, nobody or unknown), unit (participant or group), units,
     *     files, filebytes, limitsource (activity, default, course, site, server; null when no limit applies),
     *     attempts (null when unlimited), perattempt and potential (null when unlimited or without a number).
     */
    public static function evaluate(array $settings, array $course): array {
        $result = [
            'status' => 'potential',
            'unit' => empty($settings['teamsubmission']) ? 'participant' : 'group',
            'units' => 0,
            'files' => 0,
            'filebytes' => 0,
            'limitsource' => null,
            'attempts' => null,
            'perattempt' => 0,
            'potential' => null,
        ];

        // Moodle offers no file submission when the maximum number of files is missing or zero.
        $maxfiles = $settings['maxfiles'] ?? null;
        if ($maxfiles === null || (int)$maxfiles <= 0) {
            return ['status' => 'nofiles'] + $result;
        }
        $result['files'] = (int)$maxfiles;

        [$result['limitsource'], $result['filebytes']] = self::limit(
            (int)($settings['activitybytes'] ?? 0),
            $course['limits'] ?? []
        );

        // One attempt when Moodle never opens another: maximum of one, reopening "none" (before Moodle 4.4), or "until
        // pass" without a pass criterion, as assign::reopen_submission_if_required() and grade_grade::is_passed()
        // decide. Otherwise the maximum; -1 is unlimited.
        $maxattempts = (int)($settings['maxattempts'] ?? 1);
        $reopenmethod = (string)($settings['reopenmethod'] ?? '');
        $reopens = $reopenmethod !== 'none' && ($reopenmethod !== 'untilpass' || self::has_pass_criterion($settings));
        if ($maxattempts === 1 || !$reopens) {
            $result['attempts'] = 1;
        } else if ($maxattempts > 1) {
            $result['attempts'] = $maxattempts;
        }
        $knownattempts = $result['attempts'] !== null || $maxattempts === -1;

        $participants = array_map('intval', $settings['participants'] ?? []);
        $result['units'] = $result['unit'] === 'participant' ? count($participants) : self::groups(
            $participants,
            (int)($settings['groupingid'] ?? 0),
            !empty($settings['preventnogroup']),
            $course['memberships'] ?? [],
            $course['groupings'] ?? []
        );

        if ($result['filebytes'] === 0 || !$knownattempts) {
            return ['status' => 'unknown'] + $result;
        }
        if ($result['units'] === 0) {
            return ['status' => 'nobody'] + $result;
        }

        $result['perattempt'] = $result['units'] * $result['files'] * $result['filebytes'];
        if ($result['attempts'] === null) {
            $result['status'] = 'unlimited';
        } else {
            $result['potential'] = $result['perattempt'] * $result['attempts'];
        }
        return $result;
    }

    /**
     * Whether an assignment effectively allows new attempts without a maximum (Feature 009, research R1): file
     * submissions enabled and accepting at least one file, an unlimited maximum of attempts, a reopening method
     * that opens new attempts (as evaluate() resolves it, including "until pass" only with a pass criterion, the
     * rule of assign::reopen_submission_if_required()) and at least one participant or group who can submit.
     * It reads the result of evaluate() and does not depend on the limit in bytes.
     *
     * @param array $settings Settings of the assignment, as for evaluate(), with enabled.
     * @param array $evaluation Result of evaluate() for these settings.
     * @return bool
     */
    public static function allows_unlimited_attempts(array $settings, array $evaluation): bool {
        return !empty($settings['enabled'])
            && (int)($settings['maxattempts'] ?? 1) === -1
            && $evaluation['files'] > 0
            && $evaluation['attempts'] === null
            && $evaluation['units'] > 0;
    }

    /**
     * Whether "until pass" can reopen: the assignment is graded with points or a scale and its grade item has a grade
     * to pass (not empty, not the minimum grade, not zero on a scale).
     *
     * @param array $settings gradetype, gradepass and grademin of the grade item (null without one).
     * @return bool
     */
    private static function has_pass_criterion(array $settings): bool {
        $type = $settings['gradetype'] ?? null;
        $pass = $settings['gradepass'] ?? null;
        if (!in_array($type, [self::GRADE_VALUE, self::GRADE_SCALE], true) || $pass === null) {
            return false;
        }
        if ((float)$pass === (float)($settings['grademin'] ?? 0)) {
            return false;
        }
        return !($type === self::GRADE_SCALE && abs((float)$pass) < 0.00001);
    }

    /**
     * Effective limit per file: the smallest limit above zero, as the upload of Moodle applies it.
     *
     * @param int $activitybytes Size set in the assignment (0 = the default of the plugin).
     * @param array $limits server, site, course and default limits in bytes (0 = no limit at that level).
     * @return array [source, bytes]; [null, 0] when no level has a limit.
     */
    private static function limit(int $activitybytes, array $limits): array {
        $source = null;
        $bytes = 0;
        if ($activitybytes > 0) {
            [$source, $bytes] = ['activity', $activitybytes];
        } else if ((int)($limits['default'] ?? 0) > 0) {
            [$source, $bytes] = ['default', (int)$limits['default']];
        }
        foreach (self::LIMIT_ORDER as $level) {
            $value = (int)($limits[$level] ?? 0);
            if ($value > 0 && ($source === null || $value < $bytes)) {
                [$source, $bytes] = [$level, $value];
            }
        }
        return [$source, $bytes];
    }

    /**
     * Number of groups that can submit, as assign::count_teams() counts them.
     *
     * With a grouping, the groups of the grouping that have a participant; without one, the groups of the
     * participants who are in exactly one group. Plus the default group when some participant has no single group
     * and submitting outside a group is allowed.
     *
     * @param int[] $participants User ids who can submit.
     * @param int $groupingid Grouping of the submission groups (0 = none).
     * @param bool $preventnogroup Whether submitting outside a group is prevented.
     * @param array $memberships User id => group ids of the course.
     * @param array $groupings Grouping id => group ids.
     * @return int Number of groups.
     */
    private static function groups(
        array $participants,
        int $groupingid,
        bool $preventnogroup,
        array $memberships,
        array $groupings
    ): int {
        $allowed = $groupingid ? array_flip(array_map('intval', $groupings[$groupingid] ?? [])) : null;
        $groups = [];
        $default = false;
        foreach ($participants as $userid) {
            $mine = array_map('intval', $memberships[$userid] ?? []);
            if ($allowed !== null) {
                $mine = array_values(array_filter($mine, fn(int $groupid): bool => isset($allowed[$groupid])));
                foreach ($mine as $groupid) {
                    $groups[$groupid] = true;
                }
            } else if (count($mine) === 1) {
                $groups[$mine[0]] = true;
            }
            if (count($mine) !== 1) {
                $default = true;
            }
        }
        return count($groups) + ($default && !$preventnogroup ? 1 : 0);
    }
}
