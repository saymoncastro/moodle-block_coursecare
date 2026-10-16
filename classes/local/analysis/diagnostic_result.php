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
 * Result of the analysis of one course: values ready to be stored (data-model.md).
 *
 * Every value is an integer. A summary field may be null when the indicator is not calculated by this version of
 * the analysis; null is never displayed as zero. It holds aggregates only: no user, file name or path.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_result {
    /** @var string[] Result fields of block_coursecare_diag. */
    public const SUMMARY_FIELDS = [
        'methodversion', 'largethreshold', 'filecount', 'logicalbytes', 'uniquecount', 'uniquebytes', 'largecount',
        'largebytes', 'largeuniquecount', 'largeuniquebytes', 'unclassifiedcount', 'unclassifiedbytes', 'refinternalcount',
        'refinternalbytes', 'refexternalcount', 'refexternalbytes', 'refundeterminedcount', 'brcount', 'brbytes',
        'bruniquecount', 'bruniquebytes', 'partial', 'largelinkstruncated',
    ];

    /** @var string[] Fields of a category row. */
    public const CATEGORY_FIELDS = ['filecount', 'logicalbytes', 'uniquecount', 'uniquebytes'];

    /** @var string[] Fields of an activity row. */
    public const ACTIVITY_FIELDS = [
        'filecount', 'logicalbytes', 'materialbytes', 'submissionbytes', 'otherbytes', 'unclassifiedbytes', 'largecount',
        'largebytes', 'largeuniquecount', 'largeuniquebytes',
    ];

    /** @var array<string, ?int> Result fields of block_coursecare_diag (null = not calculated). */
    public readonly array $summary;

    /** @var array<string, array<string, int>> Category rows, keyed by category. */
    public readonly array $categories;

    /** @var array<int, array<string, int>> Activity rows, keyed by course module id (0 = course level). */
    public readonly array $activities;

    /** @var array<int, array{fileid: int, cmid: int, filesize: int}> Links to large course material files. */
    public readonly array $largelinks;

    /**
     * Build a result, checking that every expected field is present and is an integer.
     *
     * @param array $summary Values for SUMMARY_FIELDS.
     * @param array $categories Category rows with CATEGORY_FIELDS.
     * @param array $activities Activity rows with ACTIVITY_FIELDS.
     * @param array $largelinks Large-file links.
     */
    public function __construct(array $summary, array $categories = [], array $activities = [], array $largelinks = []) {
        $this->summary = self::integers($summary, self::SUMMARY_FIELDS, true);
        $rows = [];
        foreach ($categories as $category => $row) {
            $rows[(string)$category] = self::integers($row, self::CATEGORY_FIELDS);
        }
        $this->categories = $rows;
        $rows = [];
        foreach ($activities as $cmid => $row) {
            $rows[(int)$cmid] = self::integers($row, self::ACTIVITY_FIELDS);
        }
        $this->activities = $rows;
        $links = [];
        foreach ($largelinks as $link) {
            $links[] = self::integers($link, ['fileid', 'cmid', 'filesize']);
        }
        $this->largelinks = $links;
    }

    /**
     * Keep only the expected fields, all present and integer.
     *
     * @param array $values Values.
     * @param string[] $fields Expected fields.
     * @param bool $nullable Whether null (not calculated) is accepted.
     * @return array<string, ?int>
     */
    private static function integers(array $values, array $fields, bool $nullable = false): array {
        $result = [];
        foreach ($fields as $field) {
            $ok = array_key_exists($field, $values) && (is_int($values[$field]) || ($nullable && $values[$field] === null));
            if (!$ok) {
                throw new \coding_exception("Missing or non-integer result field: {$field}");
            }
            $result[$field] = $values[$field];
        }
        return $result;
    }
}
