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

namespace block_coursecare\local\collector;

use block_coursecare\local\analysis\classification_rules;
use block_coursecare\local\analysis\indicator_calculator;

/**
 * Reads the file metadata of a course with aggregate queries (contracts/indicators.md, research R1, R2, R12).
 *
 * Only metadata columns of the files table are read, with the DML API: no stored_file object is built, no file
 * content is read and no reference is synchronised. The user, file name, path, author and source columns are
 * never selected. Every named parameter appears once per statement (the DML rejects repeated placeholders).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_files {
    /** @var string Reference type of records that are not references. */
    public const REF_NONE = indicator_calculator::REF_NONE;
    /** @var string Reference to a file stored in Moodle. */
    public const REF_INTERNAL = indicator_calculator::REF_INTERNAL;
    /** @var string Synchronised reference to an external repository. */
    public const REF_EXTERNAL = indicator_calculator::REF_EXTERNAL;
    /** @var string Reference whose source or size cannot be determined. */
    public const REF_UNDETERMINED = indicator_calculator::REF_UNDETERMINED;

    /** @var string[] Repository types whose references point to files stored in Moodle (has_moodle_files()). */
    public const INTERNAL_REPOSITORIES = ['contentbank', 'coursefiles', 'local', 'recent', 'user'];

    /** @var int files.status of a reference whose source is missing (file_storage::update_references()). */
    public const SOURCE_MISSING = 666;

    /** @var string Group of the main scope in get_group_uniques(). */
    public const GROUP_MAIN = indicator_calculator::GROUP_MAIN;
    /** @var string Group of the backup and recycle bin aggregate in get_group_uniques(). */
    public const GROUP_BACKUP = indicator_calculator::GROUP_BACKUP;

    /**
     * Contexts of the course: the course context and all its descendants (Q0).
     *
     * @param \context_course $context Course context.
     * @return array<int, \stdClass> Rows with id, contextlevel, instanceid and path, keyed by context id.
     */
    public function get_contexts(\context_course $context): array {
        global $DB;

        [$where, $params] = $this->scope_condition($context, 'ctx', 'q0');
        $records = $DB->get_records_sql(
            "SELECT ctx.id, ctx.contextlevel, ctx.instanceid, ctx.path
               FROM {context} ctx
              WHERE {$where}",
            $params
        );
        foreach ($records as $record) {
            $record->id = (int)$record->id;
            $record->contextlevel = (int)$record->contextlevel;
            $record->instanceid = (int)$record->instanceid;
        }
        return $records;
    }

    /**
     * Aggregates by context, component, file area and reference type (Q1). Directory entries are skipped.
     *
     * The reference type comes from the repository type of the reference, read by metadata joins only
     * (research R4): internal for repositories that point to Moodle files, external for any other type, and
     * undetermined when the source is missing, the repository is gone or an external reference was never
     * synchronised.
     *
     * @param \context_course $context Course context.
     * @param int $threshold Large-file threshold in bytes.
     * @return \stdClass[] Rows with contextid, component, filearea, reftype, filecount, bytes, largecount, largebytes.
     */
    public function get_area_aggregates(\context_course $context, int $threshold): array {
        global $DB;

        [$where, $params] = $this->scope_condition($context, 'ctx', 'q1');
        [$insql, $inparams] = $DB->get_in_or_equal(self::INTERNAL_REPOSITORIES, SQL_PARAMS_NAMED, 'q1int');
        $params += $inparams + [
            'q1threshold' => $threshold,
            'q1dot' => '.',
            'q1missing' => self::SOURCE_MISSING,
            'q1none' => self::REF_NONE,
            'q1undet1' => self::REF_UNDETERMINED,
            'q1internal' => self::REF_INTERNAL,
            'q1undet2' => self::REF_UNDETERMINED,
            'q1external' => self::REF_EXTERNAL,
        ];

        $sql = "SELECT a.contextid, a.component, a.filearea, a.reftype, COUNT(1) AS filecount,
                       SUM(a.filesize) AS bytes, SUM(a.islarge) AS largecount, SUM(a.islarge * a.filesize) AS largebytes
                  FROM (
                        SELECT f.contextid, f.component, f.filearea, f.filesize,
                               CASE
                                   WHEN f.referencefileid IS NULL THEN :q1none
                                   WHEN f.status = :q1missing OR rp.id IS NULL THEN :q1undet1
                                   WHEN rp.type {$insql} THEN :q1internal
                                   WHEN r.lastsync IS NULL THEN :q1undet2
                                   ELSE :q1external
                               END AS reftype,
                               CASE WHEN f.filesize >= :q1threshold THEN 1 ELSE 0 END AS islarge
                          FROM {files} f
                          JOIN {context} ctx ON ctx.id = f.contextid
                     LEFT JOIN {files_reference} r ON r.id = f.referencefileid
                     LEFT JOIN {repository_instances} ri ON ri.id = r.repositoryid
                     LEFT JOIN {repository} rp ON rp.id = ri.typeid
                         WHERE {$where}
                           AND f.filename <> :q1dot
                       ) a
              GROUP BY a.contextid, a.component, a.filearea, a.reftype";

        $rows = [];
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $rows[] = (object)[
                'contextid' => (int)$record->contextid,
                'component' => (string)$record->component,
                'filearea' => (string)$record->filearea,
                'reftype' => (string)$record->reftype,
                'filecount' => (int)$record->filecount,
                'bytes' => (int)$record->bytes,
                'largecount' => (int)$record->largecount,
                'largebytes' => (int)$record->largebytes,
            ];
        }
        $recordset->close();
        return $rows;
    }

    /**
     * Unique content by category (Q2), including the backup and recycle bin categories; references are out.
     *
     * Each distinct content (contenthash) is counted once per category, with its recorded size.
     *
     * @param \context_course $context Course context.
     * @return array<string, \stdClass> Rows with contents and bytes, keyed by category.
     */
    public function get_category_uniques(\context_course $context): array {
        global $DB;

        [$where, $params] = $this->scope_condition($context, 'ctx', 'q2');
        [$case, $caseparams] = $this->category_case('q2');
        $params += $caseparams + ['q2dot' => '.'];

        $sql = "SELECT u.category, COUNT(1) AS contents, SUM(u.sz) AS bytes
                  FROM (
                        SELECT c.category, c.contenthash, MAX(c.filesize) AS sz
                          FROM (
                                SELECT {$case} AS category, f.contenthash, f.filesize
                                  FROM {files} f
                                  JOIN {context} ctx ON ctx.id = f.contextid
                                 WHERE {$where}
                                   AND f.filename <> :q2dot
                                   AND f.referencefileid IS NULL
                               ) c
                      GROUP BY c.category, c.contenthash
                       ) u
              GROUP BY u.category";
        return $this->unique_rows($DB->get_records_sql($sql, $params), 'category');
    }

    /**
     * Unique content of the main scope and of the backup and recycle bin aggregate (Q3); references are out.
     *
     * A content present in several categories of the same group is counted once in the group.
     *
     * @param \context_course $context Course context.
     * @return array<string, \stdClass> Rows with contents and bytes, keyed by group (GROUP_MAIN, GROUP_BACKUP).
     */
    public function get_group_uniques(\context_course $context): array {
        global $DB;

        [$where, $params] = $this->scope_condition($context, 'ctx', 'q3');
        [$case, $caseparams] = $this->category_case('q3');
        [$mainsql, $mainparams] = $DB->get_in_or_equal(classification_rules::MAIN_CATEGORIES, SQL_PARAMS_NAMED, 'q3m');
        [$brsql, $brparams] = $DB->get_in_or_equal(classification_rules::BACKUP_CATEGORIES, SQL_PARAMS_NAMED, 'q3b');
        $params += $caseparams + $mainparams + $brparams + [
            'q3dot' => '.',
            'q3main' => self::GROUP_MAIN,
            'q3br' => self::GROUP_BACKUP,
            'q3other' => 'excluded',
        ];

        $sql = "SELECT u.grp, COUNT(1) AS contents, SUM(u.sz) AS bytes
                  FROM (
                        SELECT g.grp, g.contenthash, MAX(g.filesize) AS sz
                          FROM (
                                SELECT CASE
                                           WHEN c.category {$mainsql} THEN :q3main
                                           WHEN c.category {$brsql} THEN :q3br
                                           ELSE :q3other
                                       END AS grp,
                                       c.contenthash, c.filesize
                                  FROM (
                                        SELECT {$case} AS category, f.contenthash, f.filesize
                                          FROM {files} f
                                          JOIN {context} ctx ON ctx.id = f.contextid
                                         WHERE {$where}
                                           AND f.filename <> :q3dot
                                           AND f.referencefileid IS NULL
                                       ) c
                               ) g
                      GROUP BY g.grp, g.contenthash
                       ) u
              GROUP BY u.grp";
        return $this->unique_rows($DB->get_records_sql($sql, $params), 'grp');
    }

    /**
     * SQL CASE expression that classifies file records with the rule table, plus its parameters.
     *
     * The expression expects the files table aliased as "f" and the context table as "ctx". Every parameter name
     * starts with the given prefix, so the expression can be used in different statements without collision.
     *
     * @param string $prefix Unique parameter prefix for this use (letters only, short).
     * @return array{0: string, 1: array} SQL and named parameters.
     */
    public function category_case(string $prefix): array {
        $whens = [];
        $params = [];
        foreach (classification_rules::rules() as $n => $rule) {
            $conditions = [];
            [$sql, $params] = $this->pattern_condition('f.component', $rule['component'], "{$prefix}c{$n}", $params);
            $conditions[] = $sql;
            if ($rule['contextlevels'] !== null) {
                $conditions[] = 'ctx.contextlevel IN (' . implode(', ', array_map('intval', $rule['contextlevels'])) . ')';
            }
            if ($rule['fileareas'] !== null) {
                $areas = [];
                foreach ($rule['fileareas'] as $a => $pattern) {
                    [$sql, $params] = $this->pattern_condition('f.filearea', $pattern, "{$prefix}a{$n}x{$a}", $params);
                    $areas[] = $sql;
                }
                $conditions[] = '(' . implode(' OR ', $areas) . ')';
            }
            $params["{$prefix}r{$n}"] = $rule['result'];
            $whens[] = 'WHEN ' . implode(' AND ', $conditions) . " THEN :{$prefix}r{$n}";
        }
        $params["{$prefix}none"] = classification_rules::UNCLASSIFIED;
        return ['CASE ' . implode(' ', $whens) . " ELSE :{$prefix}none END", $params];
    }

    /**
     * Normalise unique-content rows to integers, keyed by the given column.
     *
     * @param array $records Records from the database.
     * @param string $key Key column.
     * @return array<string, \stdClass>
     */
    private function unique_rows(array $records, string $key): array {
        $rows = [];
        foreach ($records as $record) {
            $rows[(string)$record->$key] = (object)['contents' => (int)$record->contents, 'bytes' => (int)$record->bytes];
        }
        return $rows;
    }

    /**
     * Condition selecting the course context and its descendants.
     *
     * @param \context_course $context Course context.
     * @param string $alias Alias of the context table.
     * @param string $prefix Parameter prefix.
     * @return array{0: string, 1: array}
     */
    private function scope_condition(\context_course $context, string $alias, string $prefix): array {
        return [
            "({$alias}.id = :{$prefix}ctxid OR {$alias}.path LIKE :{$prefix}path)",
            ["{$prefix}ctxid" => $context->id, "{$prefix}path" => $context->path . '/%'],
        ];
    }

    /**
     * Condition for an exact value or a prefix pattern ending with "*".
     *
     * @param string $field SQL field.
     * @param string $pattern Pattern.
     * @param string $name Parameter name.
     * @param array $params Parameters collected so far.
     * @return array{0: string, 1: array}
     */
    private function pattern_condition(string $field, string $pattern, string $name, array $params): array {
        global $DB;

        if (substr($pattern, -1) === '*') {
            $params[$name] = $DB->sql_like_escape(substr($pattern, 0, -1)) . '%';
            return [$DB->sql_like($field, ":{$name}"), $params];
        }
        $params[$name] = $pattern;
        return ["{$field} = :{$name}", $params];
    }
}
