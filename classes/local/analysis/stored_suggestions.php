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
 * The suggestions found by the analysis and stored with the course diagnostic (Feature 009, research R4, B2).
 *
 * Format v1 of the "suggestions" field: {"v":1,"items":[{"kind":"attempts","cmid":123}]}. Null (or a value that
 * cannot be read) means "not calculated yet", never "no suggestion"; an empty list means calculated, none. The block
 * and the page decide whether a stored suggestion applies now with the same filter, applicable(): the activity
 * still exists, is an assignment and is visible to students. The large materials suggestion of Feature 004 is not
 * stored here: it comes from the large file links.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class stored_suggestions {
    /** @var int Version of the stored format. */
    public const VERSION = 1;

    /** @var string Kind: an assignment that effectively allows new attempts without a maximum. */
    public const ATTEMPTS = 'attempts';

    /**
     * The value to store for the given items.
     *
     * @param array $items Items with kind and cmid.
     * @return string
     */
    public static function encode(array $items): string {
        $list = [];
        foreach ($items as $item) {
            $list[] = ['kind' => (string)$item['kind'], 'cmid' => (int)$item['cmid']];
        }
        return json_encode(['v' => self::VERSION, 'items' => $list]);
    }

    /**
     * The items of a stored value, or null when they were not calculated (null, empty or unreadable value, or an
     * unknown version).
     *
     * @param string|null $value Stored value.
     * @return array|null Items with kind (string) and cmid (int).
     */
    public static function decode(?string $value): ?array {
        if ($value === null || $value === '') {
            return null;
        }
        $data = json_decode($value, true);
        if (!is_array($data) || ($data['v'] ?? null) !== self::VERSION || !is_array($data['items'] ?? null)) {
            return null;
        }
        $items = [];
        foreach ($data['items'] as $item) {
            if (!is_array($item) || !is_string($item['kind'] ?? null) || !is_int($item['cmid'] ?? null)) {
                return null;
            }
            $items[] = ['kind' => $item['kind'], 'cmid' => $item['cmid']];
        }
        return $items;
    }

    /**
     * The suggestions about assignments that apply now for a course: the one reference shared by the block and the
     * page. Without a result there is none; otherwise the stored value, decoded and filtered by applicable().
     *
     * @param \stdClass|null $record Diagnostic record of the course (diagnostic_store::get_summary()).
     * @param bool $hasresult Whether the resolved state has a result.
     * @param int $courseid Course id.
     * @return array|null As applicable(); null when not calculated.
     */
    public static function current(?\stdClass $record, bool $hasresult, int $courseid): ?array {
        if ($record === null || !$hasresult) {
            return [];
        }
        $items = self::decode($record->suggestions ?? null);
        // The course information is only needed to filter stored items (on the course page it is already cached).
        return $items ? self::applicable($items, get_fast_modinfo($courseid)) : $items;
    }

    /**
     * The stored items that apply now, in the order of the course: the activity still exists, is an assignment, is
     * not being deleted and is visible to students (hiding it, or its section, hides the suggestion at once).
     *
     * @param array|null $items Decoded items (null when not calculated).
     * @param \course_modinfo $modinfo Course information (cached by Moodle).
     * @return array|null Items with kind, cmid and cm (cm_info), or null when not calculated.
     */
    public static function applicable(?array $items, \course_modinfo $modinfo): ?array {
        if ($items === null) {
            return null;
        }
        $bycmid = [];
        foreach ($items as $item) {
            $bycmid[$item['cmid']][] = $item['kind'];
        }
        $result = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!isset($bycmid[(int)$cm->id]) || $cm->modname !== 'assign' || !$cm->visible || $cm->deletioninprogress) {
                continue;
            }
            foreach ($bycmid[(int)$cm->id] as $kind) {
                $result[] = ['kind' => $kind, 'cmid' => (int)$cm->id, 'cm' => $cm];
            }
        }
        return $result;
    }
}
