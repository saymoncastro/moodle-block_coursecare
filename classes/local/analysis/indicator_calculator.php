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
 * Computes the storage indicators of one course from aggregated file metadata (contracts/indicators.md).
 *
 * Pure: it receives the rows already read by the collector and never touches the database.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class indicator_calculator {
    /** @var int Version of the methodology; increment when the same data would produce different values. */
    public const METHOD_VERSION = 1;

    /** @var string Reference type of records that are not references. */
    public const REF_NONE = 'none';
    /** @var string Reference to a file stored in Moodle. */
    public const REF_INTERNAL = 'internal';
    /** @var string Synchronised reference to an external repository. */
    public const REF_EXTERNAL = 'external';
    /** @var string Reference whose source or size cannot be determined. */
    public const REF_UNDETERMINED = 'undetermined';

    /** @var string Unique-content group of the main scope. */
    public const GROUP_MAIN = 'main';
    /** @var string Unique-content group of the backup and recycle bin aggregate. */
    public const GROUP_BACKUP = 'br';

    /** @var int Maximum number of large-file links kept for one course (D10). */
    public const MAX_LARGE_LINKS = 1000;

    /** @var int Large-file threshold in bytes. */
    private int $threshold;

    /**
     * Constructor.
     *
     * @param int $threshold Large-file threshold in bytes.
     */
    public function __construct(int $threshold) {
        $this->threshold = $threshold;
    }

    /**
     * Calculate the indicators.
     *
     * The unique-content and large-file rows are optional: without them, the indicators that depend on them stay
     * null, and the category or activity rows absent (not calculated), never zero.
     *
     * @param array $contexts Context rows of the course (Q0), keyed by context id, each with a contextlevel.
     * @param array $arearows Aggregates by context, component, file area and reference type (Q1), each with
     *     contextid, component, filearea, reftype, filecount, bytes, largecount and largebytes.
     * @param array|null $groupuniques Unique content by group (Q3), keyed by GROUP_MAIN or GROUP_BACKUP, each with
     *     contents and bytes.
     * @param array|null $categoryuniques Unique content by category (Q2), keyed by category, each with contents and
     *     bytes.
     * @param array|null $largerows Large file records that are not references (Q4), each with id, contextid,
     *     component, filearea, contenthash and filesize.
     * @return diagnostic_result
     */
    public function calculate(
        array $contexts,
        array $arearows,
        ?array $groupuniques = null,
        ?array $categoryuniques = null,
        ?array $largerows = null
    ): diagnostic_result {
        $cmids = self::cmid_map($contexts);
        $activities = [];
        $main = ['filecount' => 0, 'bytes' => 0, 'largecount' => 0, 'largebytes' => 0];
        $unclassified = ['filecount' => 0, 'bytes' => 0];
        $refs = [
            self::REF_INTERNAL => ['filecount' => 0, 'bytes' => 0],
            self::REF_EXTERNAL => ['filecount' => 0, 'bytes' => 0],
            self::REF_UNDETERMINED => ['filecount' => 0, 'bytes' => 0],
        ];
        $maincategories = array_fill_keys(classification_rules::MAIN_CATEGORIES, ['filecount' => 0, 'bytes' => 0]);
        $backup = array_fill_keys(classification_rules::BACKUP_CATEGORIES, ['filecount' => 0, 'bytes' => 0]);

        foreach ($arearows as $row) {
            $reftype = (string)$row->reftype;
            if ($reftype !== self::REF_NONE) {
                // References are counted apart and never enter any indicator or the aggregate (FR-019e).
                $type = isset($refs[$reftype]) ? $reftype : self::REF_UNDETERMINED;
                $refs[$type]['filecount'] += (int)$row->filecount;
                $refs[$type]['bytes'] += (int)$row->bytes;
                continue;
            }
            $level = isset($contexts[$row->contextid]) ? (int)$contexts[$row->contextid]->contextlevel : 0;
            $result = classification_rules::classify((string)$row->component, (string)$row->filearea, $level);
            if (isset($backup[$result])) {
                $backup[$result]['filecount'] += (int)$row->filecount;
                $backup[$result]['bytes'] += (int)$row->bytes;
                continue;
            }
            if (!isset($maincategories[$result])) {
                // Excluded transient areas.
                continue;
            }
            $maincategories[$result]['filecount'] += (int)$row->filecount;
            $maincategories[$result]['bytes'] += (int)$row->bytes;
            $cmid = $cmids[$row->contextid] ?? 0;
            if (!isset($activities[$cmid])) {
                $activities[$cmid] = array_fill_keys(diagnostic_result::ACTIVITY_FIELDS, 0);
            }
            $activities[$cmid]['filecount'] += (int)$row->filecount;
            $activities[$cmid]['logicalbytes'] += (int)$row->bytes;
            $activities[$cmid][$result . 'bytes'] += (int)$row->bytes;
            $activities[$cmid]['largecount'] += (int)$row->largecount;
            $activities[$cmid]['largebytes'] += (int)$row->largebytes;
            $main['filecount'] += (int)$row->filecount;
            $main['bytes'] += (int)$row->bytes;
            $main['largecount'] += (int)$row->largecount;
            $main['largebytes'] += (int)$row->largebytes;
            if ($result === classification_rules::UNCLASSIFIED) {
                $unclassified['filecount'] += (int)$row->filecount;
                $unclassified['bytes'] += (int)$row->bytes;
            }
        }

        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary['methodversion'] = self::METHOD_VERSION;
        $summary['largethreshold'] = $this->threshold;
        $summary['filecount'] = $main['filecount'];
        $summary['logicalbytes'] = $main['bytes'];
        $summary['largecount'] = $main['largecount'];
        $summary['largebytes'] = $main['largebytes'];
        $summary['unclassifiedcount'] = $unclassified['filecount'];
        $summary['unclassifiedbytes'] = $unclassified['bytes'];
        $summary['refinternalcount'] = $refs[self::REF_INTERNAL]['filecount'];
        $summary['refinternalbytes'] = $refs[self::REF_INTERNAL]['bytes'];
        $summary['refexternalcount'] = $refs[self::REF_EXTERNAL]['filecount'];
        $summary['refexternalbytes'] = $refs[self::REF_EXTERNAL]['bytes'];
        $summary['refundeterminedcount'] = $refs[self::REF_UNDETERMINED]['filecount'];
        $summary['brcount'] = array_sum(array_column($backup, 'filecount'));
        $summary['brbytes'] = array_sum(array_column($backup, 'bytes'));
        $summary['partial'] = ($unclassified['filecount'] > 0 || $refs[self::REF_UNDETERMINED]['filecount'] > 0) ? 1 : 0;

        if ($groupuniques !== null) {
            $summary['uniquecount'] = (int)($groupuniques[self::GROUP_MAIN]->contents ?? 0);
            $summary['uniquebytes'] = (int)($groupuniques[self::GROUP_MAIN]->bytes ?? 0);
            $summary['bruniquecount'] = (int)($groupuniques[self::GROUP_BACKUP]->contents ?? 0);
            $summary['bruniquebytes'] = (int)($groupuniques[self::GROUP_BACKUP]->bytes ?? 0);
        }

        $categories = [];
        if ($categoryuniques !== null) {
            // Only categories with records get a row; the main scope first, then the backup aggregate.
            foreach ($maincategories + $backup as $category => $totals) {
                if ($totals['filecount'] > 0) {
                    $categories[$category] = [
                        'filecount' => $totals['filecount'],
                        'logicalbytes' => $totals['bytes'],
                        'uniquecount' => (int)($categoryuniques[$category]->contents ?? 0),
                        'uniquebytes' => (int)($categoryuniques[$category]->bytes ?? 0),
                    ];
                }
            }
        }

        $largelinks = [];
        if ($largerows === null) {
            $activities = [];
        } else {
            [$uniques, $largelinks, $truncated] = $this->large_files($contexts, $cmids, $largerows);
            $summary['largeuniquecount'] = count($uniques['all'] ?? []);
            $summary['largeuniquebytes'] = array_sum($uniques['all'] ?? []);
            $summary['largelinkstruncated'] = $truncated ? 1 : 0;
            foreach ($activities as $cmid => $row) {
                $activities[$cmid]['largeuniquecount'] = count($uniques[$cmid] ?? []);
                $activities[$cmid]['largeuniquebytes'] = array_sum($uniques[$cmid] ?? []);
            }
            ksort($activities);
        }

        return new diagnostic_result($summary, $categories, $activities, $largelinks);
    }

    /**
     * Map each context of the course to the course module it belongs to (FR-019).
     *
     * A module context maps to its own module; a context below a module (a block of an activity) to that module;
     * the course and its blocks to 0.
     *
     * @param array $contexts Context rows (Q0), keyed by context id, with contextlevel, instanceid and path.
     * @return array<int, int> Course module id by context id.
     */
    public static function cmid_map(array $contexts): array {
        $map = [];
        foreach ($contexts as $id => $context) {
            $map[(int)$id] = 0;
            foreach (array_reverse(explode('/', trim((string)$context->path, '/'))) as $ancestor) {
                $row = $contexts[(int)$ancestor] ?? null;
                if ($row !== null && (int)$row->contextlevel === CONTEXT_MODULE) {
                    $map[(int)$id] = (int)$row->instanceid;
                    break;
                }
            }
        }
        return $map;
    }

    /**
     * Unique large content (overall and by activity) and the links to the largest material files.
     *
     * Only records of the main scope are used; backups, recycle bin and excluded areas are skipped. The content
     * hashes stay in memory during the calculation and are never part of the result.
     *
     * @param array $contexts Context rows (Q0).
     * @param array $cmids Course module id by context id.
     * @param array $largerows Large file records (Q4).
     * @return array{0: array, 1: array, 2: bool} Sizes by content hash (key 'all' and each cmid), links, and whether
     *     the links were truncated.
     */
    private function large_files(array $contexts, array $cmids, array $largerows): array {
        $uniques = [];
        $links = [];
        foreach ($largerows as $row) {
            $level = isset($contexts[$row->contextid]) ? (int)$contexts[$row->contextid]->contextlevel : 0;
            $category = classification_rules::classify((string)$row->component, (string)$row->filearea, $level);
            if (!in_array($category, classification_rules::MAIN_CATEGORIES, true)) {
                continue;
            }
            $cmid = $cmids[$row->contextid] ?? 0;
            $size = (int)$row->filesize;
            $hash = (string)$row->contenthash;
            $uniques['all'][$hash] = max($uniques['all'][$hash] ?? 0, $size);
            $uniques[$cmid][$hash] = max($uniques[$cmid][$hash] ?? 0, $size);
            if ($category === classification_rules::MATERIAL) {
                // Only course materials get a link (D10); never a submission or another participant's file.
                $links[] = ['fileid' => (int)$row->id, 'cmid' => $cmid, 'filesize' => $size];
            }
        }
        usort($links, fn(array $a, array $b): int => [$b['filesize'], $a['fileid']] <=> [$a['filesize'], $b['fileid']]);
        $truncated = count($links) > self::MAX_LARGE_LINKS;
        return [$uniques, array_slice($links, 0, self::MAX_LARGE_LINKS), $truncated];
    }

    /**
     * Whether the invariants of a result hold (contracts/indicators.md). Values not calculated are not checked.
     *
     * @param diagnostic_result $result Result.
     * @return bool
     */
    public static function invariants_hold(diagnostic_result $result): bool {
        $s = $result->summary;
        $checks = [
            [$s['largecount'], $s['filecount']],
            [$s['largebytes'], $s['logicalbytes']],
            [$s['unclassifiedcount'], $s['filecount']],
            [$s['unclassifiedbytes'], $s['logicalbytes']],
            [$s['uniquebytes'], $s['logicalbytes']],
            [$s['uniquecount'], $s['filecount']],
            [$s['largeuniquebytes'], $s['largebytes']],
            [$s['largeuniquecount'], $s['largecount']],
            [$s['bruniquebytes'], $s['brbytes']],
        ];
        foreach ($checks as [$part, $whole]) {
            if ($part !== null && $whole !== null && $part > $whole) {
                return false;
            }
        }
        foreach ($s as $value) {
            if ($value !== null && $value < 0) {
                return false;
            }
        }
        $main = ['filecount' => 0, 'logicalbytes' => 0];
        $hasmain = false;
        foreach ($result->categories as $category => $row) {
            if ($row['uniquebytes'] > $row['logicalbytes'] || $row['uniquecount'] > $row['filecount']) {
                return false;
            }
            if (in_array($category, classification_rules::MAIN_CATEGORIES, true)) {
                $hasmain = true;
                $main['filecount'] += $row['filecount'];
                $main['logicalbytes'] += $row['logicalbytes'];
            }
        }
        // The main categories partition the main scope: their sums are A and the record count.
        if ($hasmain && ($main['filecount'] !== $s['filecount'] || $main['logicalbytes'] !== $s['logicalbytes'])) {
            return false;
        }
        // So do the activities, for the volume and for the large files.
        if ($result->activities) {
            $totals = array_fill_keys(['filecount', 'logicalbytes', 'largecount', 'largebytes'], 0);
            foreach ($result->activities as $row) {
                if ($row['largeuniquebytes'] > $row['largebytes'] || $row['largeuniquecount'] > $row['largecount']) {
                    return false;
                }
                foreach ($totals as $field => $total) {
                    $totals[$field] = $total + $row[$field];
                }
            }
            foreach ($totals as $field => $total) {
                if ($total !== $s[$field]) {
                    return false;
                }
            }
        }
        $links = count($result->largelinks);
        return $links <= self::MAX_LARGE_LINKS && ($s['largecount'] === null || $links <= $s['largecount']);
    }
}
