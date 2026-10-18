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
 * Builds the course view of the overview page from the stored diagnostic (data-model.md of Feature 003).
 *
 * Pure: it receives the rows already read by the collectors and never touches the database, the session or the
 * output. Privacy rule FR-030: per activity, only the materials volume and the large-material links are used; the
 * total, submission, other and unclassified volumes of an activity are never read here.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview_builder {
    /** @var int share() result for a positive share below 1%, shown as "less than 1%". */
    public const LESS_THAN_ONE = -1;

    /** @var int Activities shown before "Show all" (FR-014). */
    public const FIRST = 10;

    /** @var string[] Categories of the course, in the order of the page. */
    public const CATEGORIES = [
        classification_rules::MATERIAL, classification_rules::SUBMISSION, classification_rules::OTHER,
        classification_rules::UNCLASSIFIED,
    ];

    /**
     * Share of a part in a whole, in whole percent (data-model.md, "Função share").
     *
     * @param int $part Part.
     * @param int $whole Whole.
     * @return int 0 for an empty part or whole, LESS_THAN_ONE below 1%, else the nearest whole percent.
     */
    public static function share(int $part, int $whole): int {
        if ($part <= 0 || $whole <= 0) {
            return 0;
        }
        if ($part * 100 < $whole) {
            return self::LESS_THAN_ONE;
        }
        return (int)round(100 * $part / $whole);
    }

    /**
     * Build the view.
     *
     * @param diagnostic_state $state Resolved state.
     * @param \stdClass|null $record Diagnostic record, or null without one.
     * @param array $categories Category rows keyed by category (diagnostic_store::get_categories()).
     * @param array $activities Activity rows keyed by cmid (diagnostic_store::get_activities()).
     * @param array $largebyactivity Large-material links by cmid (diagnostic_store::get_large_materials_by_activity()).
     * @param array $structure Current activities by cmid (course_structure::get_activities()).
     * @return array The view: state, hasresult, outdated, failed, partial, timecompleted, total, filecount, categories,
     *     activities, large and technical (null when not shown).
     */
    public function build(
        diagnostic_state $state,
        ?\stdClass $record,
        array $categories,
        array $activities,
        array $largebyactivity,
        array $structure
    ): array {
        $view = [
            'state' => $state->state,
            'hasresult' => $state->hasresult,
            'outdated' => $state->state === diagnostic_state::OUTDATED,
            'failed' => $state->state === diagnostic_state::FAILED,
            'partial' => $state->partial,
            'timecompleted' => $state->hasresult ? $state->timecompleted : null,
            'total' => $state->hasresult ? $state->logicalbytes : null,
            'filecount' => $state->hasresult ? $state->filecount : null,
            'categories' => null,
            'activities' => null,
            'large' => null,
            'technical' => null,
        ];
        if (!$state->hasresult) {
            return $view;
        }
        $view['technical'] = $this->technical($record, $categories, (int)$view['total']);
        // A result without files shows the sections of G in every state, also when it is out of date or the last
        // update failed (the state resolver gives E and F precedence over G).
        if ($state->filecount === 0) {
            return $view;
        }
        $view['categories'] = $this->categories($categories, (int)$view['total']);
        $materials = (int)($categories[classification_rules::MATERIAL]->logicalbytes ?? 0);
        $view['activities'] = $this->activities($activities, $structure, $materials);
        $view['large'] = $this->large($record, $largebyactivity, $structure);
        return $view;
    }

    /**
     * Large files (data-model.md, "large_summary"): the totals of the course from the summary, and the large
     * materials per activity from the links only (FR-033). The large totals of an activity row include submissions
     * and are never used.
     *
     * @param \stdClass|null $record Diagnostic record.
     * @param array $largebyactivity Large-material links by cmid.
     * @param array $structure Current activities by cmid.
     * @return array count, bytes, threshold, truncated, materials, courselevel, others and removed.
     */
    private function large(?\stdClass $record, array $largebyactivity, array $structure): array {
        $materials = [];
        $groups = ['courselevel' => null, 'others' => null, 'removed' => null];
        $add = function (string $group, \stdClass $row) use (&$groups): void {
            $groups[$group] = [
                'count' => ($groups[$group]['count'] ?? 0) + (int)$row->count,
                'bytes' => ($groups[$group]['bytes'] ?? 0) + (int)$row->bytes,
            ];
        };
        foreach ($largebyactivity as $cmid => $row) {
            $cmid = (int)$cmid;
            if ($cmid === 0) {
                $add('courselevel', $row);
            } else if (!isset($structure[$cmid])) {
                $add('removed', $row);
            } else if (!$structure[$cmid]->uservisible) {
                $add('others', $row);
            } else {
                $materials[] = ['cmid' => $cmid, 'name' => (string)$structure[$cmid]->name,
                    'url' => $structure[$cmid]->url, 'count' => (int)$row->count, 'bytes' => (int)$row->bytes];
            }
        }
        usort($materials, fn(array $a, array $b): int => [$b['bytes'], \core_text::strtolower($a['name'])]
            <=> [$a['bytes'], \core_text::strtolower($b['name'])]);
        return [
            'count' => (int)($record->largecount ?? 0),
            'bytes' => (int)($record->largebytes ?? 0),
            'threshold' => (int)($record->largethreshold ?? 0),
            'truncated' => (int)($record->largelinkstruncated ?? 0) === 1,
            'materials' => $materials,
        ] + $groups;
    }

    /**
     * Technical details (data-model.md, "technical"): measured apart, never added to the total (FR-017, FR-018).
     *
     * @param \stdClass|null $record Diagnostic record.
     * @param array $categories Category rows keyed by category.
     * @param int $total Logical storage of the course.
     * @return array unique, references, copies, coverage and threshold.
     */
    private function technical(?\stdClass $record, array $categories, int $total): array {
        $value = fn(string $field): int => (int)($record->$field ?? 0);
        $copy = fn(string $category): array => [
            'count' => (int)($categories[$category]->filecount ?? 0),
            'bytes' => (int)($categories[$category]->logicalbytes ?? 0),
        ];
        return [
            'unique' => ['count' => $value('uniquecount'), 'bytes' => $value('uniquebytes')],
            'references' => [
                'internal' => ['count' => $value('refinternalcount'), 'bytes' => $value('refinternalbytes')],
                'external' => ['count' => $value('refexternalcount'), 'bytes' => $value('refexternalbytes')],
                'undetermined' => ['count' => $value('refundeterminedcount')],
            ],
            'copies' => [
                'backup' => $copy(classification_rules::BACKUP),
                'recyclebin' => $copy(classification_rules::RECYCLEBIN),
                'uniquebytes' => $value('bruniquebytes'),
            ],
            'coverage' => ['count' => $value('unclassifiedcount'), 'bytes' => $value('unclassifiedbytes'),
                'share' => self::share($value('unclassifiedbytes'), $total)],
            'threshold' => $value('largethreshold'),
        ];
    }

    /**
     * Categories of the course (data-model.md, "category"): logical bytes and share of the total.
     *
     * @param array $categories Category rows keyed by category.
     * @param int $total Logical storage of the course.
     * @return array List of rows with key, bytes and share.
     */
    private function categories(array $categories, int $total): array {
        $rows = [];
        foreach (self::CATEGORIES as $key) {
            $bytes = (int)($categories[$key]->logicalbytes ?? 0);
            $rows[] = ['key' => $key, 'bytes' => $bytes, 'share' => self::share($bytes, $total)];
        }
        return $rows;
    }

    /**
     * Materials per activity (data-model.md, "activity_list"; FR-013 to FR-015 and FR-030).
     *
     * Only materialbytes is read from an activity row: its total, submission, other and unclassified volumes and its
     * large-file totals mix in files of students and are never used, not even for sorting.
     *
     * @param array $activities Activity rows keyed by cmid.
     * @param array $structure Current activities by cmid.
     * @param int $materials Materials volume of the course.
     * @return array first, rest, courselevel, others, removed and count.
     */
    private function activities(array $activities, array $structure, int $materials): array {
        $items = [];
        $courselevel = 0;
        $others = ['bytes' => 0, 'count' => 0];
        $removed = ['bytes' => 0, 'count' => 0];
        if ($materials <= 0) {
            foreach ($activities as $row) {
                $materials += (int)$row->materialbytes;
            }
        }
        foreach ($activities as $cmid => $row) {
            $bytes = (int)$row->materialbytes;
            if ($bytes <= 0) {
                continue;
            }
            $cmid = (int)$cmid;
            if ($cmid === 0) {
                $courselevel = $bytes;
            } else if (!isset($structure[$cmid])) {
                $removed['bytes'] += $bytes;
                $removed['count']++;
            } else if (!$structure[$cmid]->uservisible) {
                $others['bytes'] += $bytes;
                $others['count']++;
            } else {
                $items[] = ['cmid' => $cmid, 'name' => (string)$structure[$cmid]->name, 'url' => $structure[$cmid]->url,
                    'bytes' => $bytes, 'share' => self::share($bytes, $materials)];
            }
        }
        // Larger materials first; equal volumes by name, ignoring case.
        usort($items, fn(array $a, array $b): int => [$b['bytes'], \core_text::strtolower($a['name'])]
            <=> [$a['bytes'], \core_text::strtolower($b['name'])]);
        $group = fn(array $values): ?array => $values['count'] > 0
            ? ['bytes' => $values['bytes'], 'share' => self::share($values['bytes'], $materials), 'count' => $values['count']]
            : null;
        return [
            'first' => array_slice($items, 0, self::FIRST),
            'rest' => array_slice($items, self::FIRST),
            'courselevel' => $courselevel > 0 ? ['bytes' => $courselevel, 'share' => self::share($courselevel, $materials)]
                : null,
            'others' => $group($others),
            'removed' => $group($removed),
            'count' => count($items),
        ];
    }
}
