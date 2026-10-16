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

/**
 * Development tool (not packaged): time and plan of each collector query of one course (SC-003, benchmark.md).
 *
 * Runs Q0 to Q4 one by one, as the analysis does, and prints for each the duration, the database reads and the
 * number of rows. With --explain, it also prints the plan of Q1 to Q4 (EXPLAIN ANALYZE on PostgreSQL, ANALYZE on
 * MariaDB and MySQL). Nothing is written: the total duration and peak memory of a real analysis come from
 * cli/diagnostics.php --run --json, in its own process.
 *
 * Usage: php blocks/coursecare/tools/dev/bench/measure.php --course=ID [--explain]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use block_coursecare\local\collector\course_files;
use block_coursecare\local\course_analysis;

[$options, $unrecognised] = cli_get_params(['course' => 0, 'explain' => false, 'help' => false], ['h' => 'help']);
$courseid = (int)$options['course'];
if ($options['help'] || $unrecognised || $courseid <= 0) {
    cli_writeln('Usage: php measure.php --course=ID [--explain]');
    exit($options['help'] ? 0 : 1);
}

/**
 * Forwards every call to the real database object and keeps the SQL of the read queries, in Moodle form.
 */
class bench_recording_db {
    /** @var moodle_database Real database. */
    public moodle_database $real;

    /** @var array Recorded queries: [sql, params]. */
    public array $queries = [];

    /**
     * Constructor.
     *
     * @param moodle_database $real Real database.
     */
    public function __construct(moodle_database $real) {
        $this->real = $real;
    }

    /**
     * Forward a call, recording the read queries.
     *
     * @param string $name Method.
     * @param array $arguments Arguments.
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        if (in_array($name, ['get_records_sql', 'get_recordset_sql'], true)) {
            $this->queries[] = [$arguments[0], $arguments[1] ?? []];
        }
        return $this->real->$name(...$arguments);
    }
}

$context = context_course::instance($courseid);
$threshold = course_analysis::large_file_threshold();
$collector = new course_files();
$real = $DB;
$steps = [
    'Q0' => fn() => $collector->get_contexts($context),
    'Q1' => fn() => $collector->get_area_aggregates($context, $threshold),
    'Q2' => fn() => $collector->get_category_uniques($context),
    'Q3' => fn() => $collector->get_group_uniques($context),
    'Q4' => fn() => $collector->get_large_files($context, $threshold),
];

$records = $real->count_records_sql(
    "SELECT COUNT(1) FROM {files} f JOIN {context} ctx ON ctx.id = f.contextid
      WHERE (ctx.id = :ctxid OR ctx.path LIKE :path) AND f.filename <> :dot",
    ['ctxid' => $context->id, 'path' => $context->path . '/%', 'dot' => '.']
);
$result = [
    'courseid' => $courseid,
    'dbfamily' => $real->get_dbfamily(),
    'dbversion' => $real->get_server_info()['version'],
    'records' => $records,
    'sitefiles' => $real->count_records('files'),
    'threshold' => $threshold,
    'queries' => [],
];

foreach ($steps as $name => $step) {
    $recorder = new bench_recording_db($real);
    $DB = $recorder;
    $reads = $real->perf_get_reads();
    $start = microtime(true);
    try {
        $rows = $step();
    } finally {
        $DB = $real;
    }
    $query = [
        'seconds' => round(microtime(true) - $start, 4),
        'reads' => $real->perf_get_reads() - $reads,
        'rows' => count($rows),
    ];
    if ($options['explain'] && $name !== 'Q0') {
        [$sql, $params] = end($recorder->queries);
        if ($real->get_dbfamily() === 'postgres') {
            // A PostgreSQL recordset is a cursor, which cannot hold an EXPLAIN: read the plan lines as a field set.
            $query['plan'] = $real->get_fieldset_sql('EXPLAIN (ANALYZE, BUFFERS) ' . $sql, $params);
        } else {
            $plan = [];
            $recordset = $real->get_recordset_sql('ANALYZE ' . $sql, $params);
            foreach ($recordset as $line) {
                $plan[] = (array)$line;
            }
            $recordset->close();
            $query['plan'] = $plan;
        }
    }
    $result['queries'][$name] = $query;
}
$result['peakmemory'] = memory_get_peak_usage();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
