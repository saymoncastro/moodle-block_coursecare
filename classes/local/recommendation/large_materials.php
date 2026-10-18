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

namespace block_coursecare\local\recommendation;

/**
 * Suggestion S1, large course materials (Feature 004, contracts/recommendation-rule.md).
 *
 * A pure rule over the large materials of the overview view: it shows only when the course has at least one large
 * course material, and counts only those materials. The course summary of large files includes submissions and is
 * never used, so a large submission alone never makes a suggestion.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class large_materials {
    /** Named places cited by the suggestion. */
    public const PLACES = 3;

    /**
     * Evaluate the suggestion.
     *
     * @param array|null $large The "large" part of the overview view (overview_builder::build()), or null without a
     *     result with files.
     * @return array|null Null when the rule does not apply; otherwise count, bytes, threshold, places (at most three:
     *     kind "activity" with cmid, name, url and bytes, or kind "courselevel" with bytes), more and truncated.
     */
    public static function evaluate(?array $large): ?array {
        if ($large === null) {
            return null;
        }
        $places = [];
        foreach ($large['materials'] as $item) {
            $places[] = ['kind' => 'activity', 'cmid' => (int)$item['cmid'], 'name' => (string)$item['name'],
                'url' => $item['url'], 'bytes' => (int)$item['bytes']];
        }
        $count = array_sum(array_column($large['materials'], 'count'));
        $bytes = array_sum(array_column($large['materials'], 'bytes'));
        $more = 0;
        foreach (['courselevel', 'others', 'removed'] as $group) {
            if ($large[$group] === null) {
                continue;
            }
            $count += (int)$large[$group]['count'];
            $bytes += (int)$large[$group]['bytes'];
            if ($group === 'courselevel') {
                $places[] = ['kind' => 'courselevel', 'bytes' => (int)$large[$group]['bytes']];
            } else {
                // Activities the teacher cannot see, or removed after the analysis: counted, never named.
                $more++;
            }
        }
        if ($count === 0) {
            return null;
        }
        // Largest first; activities of the same size by name, the course level after them.
        usort($places, fn(array $a, array $b): int => [$b['bytes'], self::order($a)] <=> [$a['bytes'], self::order($b)]);
        return [
            'count' => $count,
            'bytes' => $bytes,
            'threshold' => (int)$large['threshold'],
            'places' => array_slice($places, 0, self::PLACES),
            'more' => $more + max(0, count($places) - self::PLACES),
            'truncated' => (bool)$large['truncated'],
        ];
    }

    /**
     * Order of a place among places of the same size.
     *
     * @param array $place Place.
     * @return array Course level after activities; activities by name.
     */
    private static function order(array $place): array {
        return $place['kind'] === 'courselevel' ? [1, ''] : [0, \core_text::strtolower($place['name'])];
    }
}
