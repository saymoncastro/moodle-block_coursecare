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

    /** @var string Group of records that are not references. */
    public const REF_NONE = 'none';

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
     * @param array $contexts Context rows of the course (Q0), keyed by context id, each with a contextlevel.
     * @param array $arearows Aggregates by context, component, file area and reference type (Q1), each with
     *     contextid, component, filearea, reftype, filecount, bytes, largecount and largebytes.
     * @return diagnostic_result
     */
    public function calculate(array $contexts, array $arearows): diagnostic_result {
        $main = ['filecount' => 0, 'bytes' => 0, 'largecount' => 0, 'largebytes' => 0];
        $unclassified = ['filecount' => 0, 'bytes' => 0];

        foreach ($arearows as $row) {
            if ((string)$row->reftype !== self::REF_NONE) {
                // References are counted apart (FR-019e) and never enter the main scope.
                continue;
            }
            $level = isset($contexts[$row->contextid]) ? (int)$contexts[$row->contextid]->contextlevel : 0;
            $result = classification_rules::classify((string)$row->component, (string)$row->filearea, $level);
            if (!in_array($result, classification_rules::MAIN_CATEGORIES, true)) {
                // Excluded areas and the backup and recycle bin aggregate are not part of the main scope.
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
        $summary['partial'] = $unclassified['filecount'] > 0 ? 1 : 0;

        return new diagnostic_result($summary);
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
        return true;
    }
}
