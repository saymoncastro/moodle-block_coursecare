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
     * The unique-content rows are optional: without them, the unique indicators and the category rows stay null
     * or absent (not calculated), never zero.
     *
     * @param array $contexts Context rows of the course (Q0), keyed by context id, each with a contextlevel.
     * @param array $arearows Aggregates by context, component, file area and reference type (Q1), each with
     *     contextid, component, filearea, reftype, filecount, bytes, largecount and largebytes.
     * @param array|null $groupuniques Unique content by group (Q3), keyed by GROUP_MAIN or GROUP_BACKUP, each with
     *     contents and bytes.
     * @param array|null $categoryuniques Unique content by category (Q2), keyed by category, each with contents and
     *     bytes.
     * @return diagnostic_result
     */
    public function calculate(
        array $contexts,
        array $arearows,
        ?array $groupuniques = null,
        ?array $categoryuniques = null
    ): diagnostic_result {
        $main = ['filecount' => 0, 'bytes' => 0, 'largecount' => 0, 'largebytes' => 0];
        $unclassified = ['filecount' => 0, 'bytes' => 0];
        $refs = [
            self::REF_INTERNAL => ['filecount' => 0, 'bytes' => 0],
            self::REF_EXTERNAL => ['filecount' => 0, 'bytes' => 0],
            self::REF_UNDETERMINED => ['filecount' => 0, 'bytes' => 0],
        ];
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
            if (!in_array($result, classification_rules::MAIN_CATEGORIES, true)) {
                // Excluded transient areas.
                continue;
            }
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
            foreach ($backup as $category => $totals) {
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

        return new diagnostic_result($summary, $categories);
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
        foreach ($result->categories as $row) {
            if ($row['uniquebytes'] > $row['logicalbytes'] || $row['uniquecount'] > $row['filecount']) {
                return false;
            }
        }
        return true;
    }
}
