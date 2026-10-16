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
     * @param \context_course $context Course context.
     * @param int $threshold Large-file threshold in bytes.
     * @return \stdClass[] Rows with contextid, component, filearea, reftype, filecount, bytes, largecount, largebytes.
     */
    public function get_area_aggregates(\context_course $context, int $threshold): array {
        global $DB;

        [$where, $params] = $this->scope_condition($context, 'ctx', 'q1');
        $params['q1threshold'] = $threshold;
        $params['q1dot'] = '.';
        $params['q1refnone'] = 'none';
        $params['q1refany'] = 'reference';

        $sql = "SELECT a.contextid, a.component, a.filearea, a.reftype, COUNT(1) AS filecount,
                       SUM(a.filesize) AS bytes, SUM(a.islarge) AS largecount, SUM(a.islarge * a.filesize) AS largebytes
                  FROM (
                        SELECT f.contextid, f.component, f.filearea, f.filesize,
                               CASE WHEN f.referencefileid IS NULL THEN :q1refnone ELSE :q1refany END AS reftype,
                               CASE WHEN f.filesize >= :q1threshold THEN 1 ELSE 0 END AS islarge
                          FROM {files} f
                          JOIN {context} ctx ON ctx.id = f.contextid
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
