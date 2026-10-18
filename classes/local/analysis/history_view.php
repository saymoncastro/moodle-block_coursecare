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
 * View of the history of a course (Feature 006, contracts/history-view.md).
 *
 * Pure rule: it receives the stored points and decides the change between the two latest measurements and the daily
 * series of the chart. A day without a measurement has no value: nothing is interpolated or estimated, and two
 * measurements of different calculation methods are not compared.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_view {
    /**
     * The view of a course's history.
     *
     * @param array $points Points of block_coursecare_hist (objects or arrays), oldest first.
     * @return array|null latest, previous (or null), change (first, increased, decreased, unchanged or
     *     methodchanged), delta (bytes, 0 when not compared), methodchange (the first point measured with the current
     *     method after a change within the points, or null), days (day offset from the first point => logical bytes
     *     or null) and points (latest first); null without points.
     */
    public static function build(array $points): ?array {
        $points = array_values(array_map(fn($point): array => (array)$point, $points));
        if (!$points) {
            return null;
        }
        $latest = $points[count($points) - 1];
        $previous = count($points) > 1 ? $points[count($points) - 2] : null;

        $change = 'first';
        $delta = 0;
        if ($previous !== null) {
            if ((int)$previous['methodversion'] !== (int)$latest['methodversion']) {
                $change = 'methodchanged';
            } else {
                $delta = (int)$latest['logicalbytes'] - (int)$previous['logicalbytes'];
                $change = $delta > 0 ? 'increased' : ($delta < 0 ? 'decreased' : 'unchanged');
                $delta = abs($delta);
            }
        }

        // The most recent change of method within the points: its first point, measured the new way.
        $methodchange = null;
        for ($i = count($points) - 1; $i > 0; $i--) {
            if ((int)$points[$i]['methodversion'] !== (int)$points[$i - 1]['methodversion']) {
                $methodchange = $points[$i];
                break;
            }
        }

        // One position per day from the first point to the latest; rounding the offset keeps it right across a
        // change of daylight saving time (days of 23 or 25 hours).
        $first = (int)$points[0]['timeday'];
        $days = [];
        foreach ($points as $point) {
            $days[(int)round(((int)$point['timeday'] - $first) / DAYSECS)] = (int)$point['logicalbytes'];
        }
        $series = array_fill(0, array_key_last($days) + 1, null);
        foreach ($days as $offset => $bytes) {
            $series[$offset] = $bytes;
        }

        return [
            'latest' => $latest,
            'previous' => $previous,
            'change' => $change,
            'delta' => $delta,
            'methodchange' => $methodchange,
            'days' => $series,
            'points' => array_reverse($points),
        ];
    }
}
