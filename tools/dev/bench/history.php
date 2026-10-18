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
 * Development tool (not packaged): measurements of the history of Feature 006 (SC-003, SC-004).
 *
 * --volume: inserts synthetic points for N courses × D days (course ids from 900000000, which no real course has),
 *     reports the size of the table and its indexes (PostgreSQL), the time of get_history() for one course and of
 *     the retention cleanup of one day of points, then removes them.
 * --seed: inserts N synthetic current results with categories, times block_coursecare_seed_history() and removes them.
 * --write: times N writes of the history point of a result (the cost added to an analysis).
 * --page: creates one course with the block, a real analysis and D daily points, and prints its id for
 *     tools/dev/bench/render.php --page.
 *
 * Usage: php blocks/coursecare/tools/dev/bench/history.php --confirm (--volume|--seed|--write|--page) [--courses=5000]
 *     [--days=365]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/blocks/coursecare/db/upgradelib.php');

use block_coursecare\local\analysis\diagnostic_result;
use block_coursecare\local\collector\diagnostic_store;

[$options, $unrecognised] = cli_get_params([
    'confirm' => false, 'volume' => false, 'seed' => false, 'write' => false, 'page' => false, 'courses' => 5000,
    'days' => 365, 'help' => false,
], ['h' => 'help']);
$modes = array_filter(['volume', 'seed', 'write', 'page'], fn(string $mode): bool => !empty($options[$mode]));
if ($options['help'] || $unrecognised || !$options['confirm'] || count($modes) !== 1) {
    cli_writeln('Measurements of the CourseCare history. Only for a disposable development site.');
    cli_writeln('Usage: php history.php --confirm (--volume|--seed|--write|--page) [--courses=5000] [--days=365]');
    exit($options['help'] ? 0 : 1);
}
$courses = max(1, (int)$options['courses']);
$days = max(1, (int)$options['days']);
$base = 900000000;
$store = new diagnostic_store();
$today = diagnostic_store::history_day(time());

/**
 * Elapsed milliseconds of a callable.
 *
 * @param callable $work Work.
 * @return float
 */
function bench_ms(callable $work): float {
    $start = microtime(true);
    $work();
    return round((microtime(true) - $start) * 1000, 2);
}

/**
 * Size of the history table and its indexes in bytes (PostgreSQL only; null elsewhere).
 *
 * @return array|null
 */
function bench_size(): ?array {
    global $DB, $CFG;
    if ($DB->get_dbfamily() !== 'postgres') {
        return null;
    }
    $table = $CFG->prefix . 'block_coursecare_hist';
    return [
        'table' => (int)$DB->get_field_sql('SELECT pg_relation_size(?)', [$table]),
        'indexes' => (int)$DB->get_field_sql('SELECT pg_indexes_size(?)', [$table]),
    ];
}

$result = ['mode' => reset($modes), 'courses' => $courses, 'days' => $days];
switch (reset($modes)) {
    case 'volume':
        $result['insertms'] = bench_ms(function () use ($courses, $days, $base, $today) {
            global $DB;
            for ($c = 0; $c < $courses; $c++) {
                $rows = [];
                for ($d = 0; $d < $days; $d++) {
                    $rows[] = ['courseid' => $base + $c, 'timeday' => $today - $d * DAYSECS, 'timecompleted' => $today
                        - $d * DAYSECS + HOURSECS, 'methodversion' => 1, 'partial' => 0, 'filecount' => 10,
                        'logicalbytes' => 1000000 + $d, 'uniquecount' => 9, 'uniquebytes' => 900000, 'materialbytes' => 800000,
                        'submissionbytes' => 200000 + $d, 'otherbytes' => 0, 'unclassifiedbytes' => 0];
                }
                $DB->insert_records('block_coursecare_hist', $rows);
            }
        });
        $result['rows'] = $DB->count_records('block_coursecare_hist');
        $result['size'] = bench_size();
        $result['gethistoryms'] = bench_ms(fn() => $store->get_history($base, $today - $days * DAYSECS));
        $result['purgeonedayms'] = bench_ms(fn() => $store->purge_history($today - ($days - 2) * DAYSECS, 10000));
        $result['removedms'] = bench_ms(fn() => $DB->delete_records_select('block_coursecare_hist', 'courseid >= ?', [$base]));
        break;

    case 'seed':
        for ($c = 0; $c < $courses; $c++) {
            $id = $DB->insert_record('block_coursecare_diag', (object)['courseid' => $base + $c, 'requeststatus' => 0,
                'timecompleted' => time() - HOURSECS, 'methodversion' => 1, 'partial' => 0, 'filecount' => 10,
                'logicalbytes' => 1000000, 'uniquecount' => 9, 'uniquebytes' => 900000, 'timemodified' => time()]);
            $DB->insert_records('block_coursecare_cat', [
                ['diagid' => $id, 'category' => 'material', 'filecount' => 8, 'logicalbytes' => 800000],
                ['diagid' => $id, 'category' => 'submission', 'filecount' => 2, 'logicalbytes' => 200000],
            ]);
        }
        $created = 0;
        $result['seedms'] = bench_ms(function () use (&$created) {
            $created = block_coursecare_seed_history();
        });
        $result['created'] = $created;
        $ids = $DB->get_fieldset_select('block_coursecare_diag', 'id', 'courseid >= ?', [$base]);
        $DB->delete_records_list('block_coursecare_cat', 'diagid', $ids);
        $DB->delete_records_select('block_coursecare_diag', 'courseid >= ?', [$base]);
        $DB->delete_records_select('block_coursecare_hist', 'courseid >= ?', [$base]);
        break;

    case 'write':
        $summary = ['methodversion' => 1, 'filecount' => 10, 'logicalbytes' => 1000000, 'uniquecount' => 9,
            'uniquebytes' => 900000, 'partial' => 0] + array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $row = ['filecount' => 1, 'logicalbytes' => 800000, 'uniquecount' => 1, 'uniquebytes' => 800000];
        $resultobject = new diagnostic_result($summary, ['material' => $row], [], []);
        // The whole save_result() (current result + point) for N courses, then the point alone is the difference
        // reported by comparing with the same work on the 0.5.0 code; here, the total per save.
        $result['saveresultms'] = bench_ms(function () use ($store, $courses, $base, $resultobject) {
            for ($c = 0; $c < $courses; $c++) {
                $store->save_result($base + $c, $resultobject, time());
            }
        });
        $result['saveresultmseach'] = round($result['saveresultms'] / $courses, 3);
        $result['pointsafter'] = $DB->count_records_select('block_coursecare_hist', 'courseid >= ?', [$base]);
        foreach ($DB->get_fieldset_select('block_coursecare_diag', 'courseid', 'courseid >= ?', [$base]) as $courseid) {
            $store->delete_for_course((int)$courseid);
        }
        break;

    case 'page':
        \core\session\manager::set_user(get_admin());
        $generator = phpunit_util::get_data_generator();
        $course = $generator->create_course(['shortname' => 'BENCH-HISTORY-' . $days . '-' . time()]);
        $generator->create_block('coursecare', ['parentcontextid' => context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*', 'defaultregion' => 'side-pre']);
        $generator->create_module('page', ['course' => $course->id]);
        (new \block_coursecare\local\course_analysis())->run((int)$course->id, true);
        $rows = [];
        for ($d = 1; $d < $days; $d++) {
            $rows[] = ['courseid' => (int)$course->id, 'timeday' => $today - $d * DAYSECS, 'timecompleted' => $today
                - $d * DAYSECS + HOURSECS, 'methodversion' => 1, 'partial' => 0, 'filecount' => 10,
                'logicalbytes' => 1000000 + $d * 1000, 'uniquecount' => 9, 'uniquebytes' => 900000, 'materialbytes' => 800000,
                'submissionbytes' => 200000 + $d * 1000, 'otherbytes' => 0, 'unclassifiedbytes' => 0];
        }
        $DB->insert_records('block_coursecare_hist', $rows);
        $result['courseid'] = (int)$course->id;
        $result['points'] = $DB->count_records('block_coursecare_hist', ['courseid' => $course->id]);
        break;
}
cli_writeln(json_encode($result, JSON_PRETTY_PRINT));
