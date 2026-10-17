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
 * Explicit classification rules for the file areas of a course (coverage matrix of Feature 002).
 *
 * The rule table is the single source of the classification: the collector builds the SQL CASE expression from
 * the same table, so PHP and SQL always agree. A pattern ending with "*" is a prefix. The first matching rule wins;
 * no match means "unclassified". Classification never uses the user, the file name or the extension.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class classification_rules {
    /** @var string Course materials published in the course. */
    public const MATERIAL = 'material';
    /** @var string Files submitted in activities. */
    public const SUBMISSION = 'submission';
    /** @var string Other associated files. */
    public const OTHER = 'other';
    /** @var string Files whose purpose cannot be established. */
    public const UNCLASSIFIED = 'unclassified';
    /** @var string Transient component areas, outside the diagnostic. */
    public const EXCLUDED = 'excluded';
    /** @var string Course backups, measured apart. */
    public const BACKUP = 'backup';
    /** @var string Course recycle bin, measured apart. */
    public const RECYCLEBIN = 'recyclebin';

    /** @var string[] Categories of the main scope (indicators A to E). */
    public const MAIN_CATEGORIES = [self::MATERIAL, self::SUBMISSION, self::OTHER, self::UNCLASSIFIED];

    /** @var string[] Categories of the separate backup and recycle bin aggregate. */
    public const BACKUP_CATEGORIES = [self::BACKUP, self::RECYCLEBIN];

    /**
     * The rules, in evaluation order (contracts/coverage-matrix.md, rules 1 to 30).
     *
     * Each rule has a component pattern, the file area patterns (null = any area), the context levels where it
     * applies (null = any context of the course) and the result.
     *
     * @return array<int, array{component: string, fileareas: ?string[], contextlevels: ?int[], result: string}>
     */
    public static function rules(): array {
        $crs = [CONTEXT_COURSE];
        $mod = [CONTEXT_MODULE];
        return [
            // Transient areas, excluded (M4).
            self::rule('mod_folder', ['temp'], $mod, self::EXCLUDED),
            self::rule('mod_book', ['importhtmltemp'], $mod, self::EXCLUDED),
            self::rule('assignfeedback_file', ['feedback_files_batch'], $mod, self::EXCLUDED),
            self::rule('assignfeedback_file', ['feedback_files_import'], $mod, self::EXCLUDED),
            self::rule('assignfeedback_editpdf', ['tmp_jpg_to_pdf'], $mod, self::EXCLUDED),
            self::rule('assignfeedback_editpdf', ['tmp_rotated_jpg'], $mod, self::EXCLUDED),
            self::rule('assignfeedback_editpdf', ['importhtml'], $mod, self::EXCLUDED),
            // Backups and recycle bin, measured apart (M12, M13).
            self::rule('backup', ['course', 'section', 'automated'], $crs, self::BACKUP),
            self::rule('backup', ['activity'], $mod, self::BACKUP),
            self::rule('tool_recyclebin', ['recyclebin_course'], $crs, self::RECYCLEBIN),
            // Course materials (M5, M6, M7, M15).
            self::rule('course', ['summary', 'overviewfiles', 'section'], $crs, self::MATERIAL),
            self::rule('mod_resource', ['content'], $mod, self::MATERIAL),
            self::rule('mod_folder', ['content'], $mod, self::MATERIAL),
            self::rule('mod_page', ['content'], $mod, self::MATERIAL),
            self::rule('mod_book', ['chapter'], $mod, self::MATERIAL),
            self::rule('mod_assign', ['introattachment', 'activityattachment'], $mod, self::MATERIAL),
            self::rule('mod_*', ['intro'], $mod, self::MATERIAL),
            self::rule('block_html', ['content'], [CONTEXT_BLOCK], self::MATERIAL),
            // Activity submissions (M8).
            self::rule('assignsubmission_file', ['submission_files'], $mod, self::SUBMISSION),
            self::rule('assignsubmission_onlinetext', ['submissions_onlinetext'], $mod, self::SUBMISSION),
            self::rule('question', ['response_*'], $mod, self::SUBMISSION),
            // Other associated files (M9, M10, M11, M14).
            self::rule('assignfeedback_file', ['feedback_files'], $mod, self::OTHER),
            self::rule('assignfeedback_comments', ['feedback'], $mod, self::OTHER),
            self::rule('assignfeedback_editpdf', ['download', 'readonlypages', 'stamps'], $mod, self::OTHER),
            self::rule('assignfeedback_editpdf', ['combined', 'partial', 'pages'], $mod, self::OTHER),
            self::rule('grade', ['feedback', 'historyfeedback'], [CONTEXT_MODULE, CONTEXT_COURSE], self::OTHER),
            self::rule('mod_forum', ['attachment', 'post'], $mod, self::OTHER),
            self::rule('question', null, [CONTEXT_COURSE, CONTEXT_MODULE], self::OTHER),
            self::rule('qtype_*', null, [CONTEXT_COURSE, CONTEXT_MODULE], self::OTHER),
            self::rule('contentbank', ['public'], $crs, self::OTHER),
        ];
    }

    /**
     * Classify a group of file records.
     *
     * @param string $component Component of the file area.
     * @param string $filearea File area name.
     * @param int $contextlevel Context level of the records.
     * @return string One of the category constants, EXCLUDED, BACKUP or RECYCLEBIN.
     */
    public static function classify(string $component, string $filearea, int $contextlevel): string {
        foreach (self::rules() as $rule) {
            if (self::rule_matches($rule, $component, $filearea, $contextlevel)) {
                return $rule['result'];
            }
        }
        return self::UNCLASSIFIED;
    }

    /**
     * Whether a pattern matches a value; a pattern ending with "*" is a prefix.
     *
     * @param string $pattern Pattern.
     * @param string $value Value.
     * @return bool
     */
    public static function pattern_matches(string $pattern, string $value): bool {
        if (substr($pattern, -1) === '*') {
            return strpos($value, substr($pattern, 0, -1)) === 0;
        }
        return $pattern === $value;
    }

    /**
     * Whether a rule matches a group of records.
     *
     * @param array $rule Rule from rules().
     * @param string $component Component.
     * @param string $filearea File area.
     * @param int $contextlevel Context level.
     * @return bool
     */
    private static function rule_matches(array $rule, string $component, string $filearea, int $contextlevel): bool {
        if (!self::pattern_matches($rule['component'], $component)) {
            return false;
        }
        if ($rule['contextlevels'] !== null && !in_array($contextlevel, $rule['contextlevels'], true)) {
            return false;
        }
        if ($rule['fileareas'] === null) {
            return true;
        }
        foreach ($rule['fileareas'] as $pattern) {
            if (self::pattern_matches($pattern, $filearea)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build one rule.
     *
     * @param string $component Component pattern.
     * @param string[]|null $fileareas File area patterns, or null for any area.
     * @param int[]|null $contextlevels Context levels, or null for any.
     * @param string $result Result of the rule.
     * @return array
     */
    private static function rule(string $component, ?array $fileareas, ?array $contextlevels, string $result): array {
        return ['component' => $component, 'fileareas' => $fileareas, 'contextlevels' => $contextlevels, 'result' => $result];
    }
}
