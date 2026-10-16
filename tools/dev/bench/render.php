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
 * Development tool (not packaged): time of the CourseCare block rendering (SC-002, benchmark.md).
 *
 * Renders the block content of one course many times for an administrator and prints the mean, the standard
 * deviation and the database reads per rendering. Run it with the 0.1.0 checkout and with the current one.
 *
 * Usage: php blocks/coursecare/tools/dev/bench/render.php --course=ID [--iterations=1000]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/blocklib.php');

[$options, $unrecognised] = cli_get_params(['course' => 0, 'iterations' => 1000, 'help' => false], ['h' => 'help']);
$courseid = (int)$options['course'];
if ($options['help'] || $unrecognised || $courseid <= 0) {
    cli_writeln('Usage: php render.php --course=ID [--iterations=1000]');
    exit($options['help'] ? 0 : 1);
}

\core\session\manager::set_user(get_admin());
$course = get_course($courseid);
$record = $DB->get_record('block_instances', ['blockname' => 'coursecare',
    'parentcontextid' => context_course::instance($courseid)->id], '*', MUST_EXIST);
$iterations = max(1, (int)$options['iterations']);

$times = [];
$reads = 0;
for ($i = 0; $i < $iterations; $i++) {
    $page = new moodle_page();
    $page->set_course($course);
    $page->set_pagetype('course-view-' . $course->format);
    $block = block_instance('coursecare', $record, $page);
    $before = $DB->perf_get_reads();
    $start = microtime(true);
    $block->get_content();
    $times[] = (microtime(true) - $start) * 1000;
    $reads += $DB->perf_get_reads() - $before;
}

$mean = array_sum($times) / count($times);
$variance = array_sum(array_map(fn(float $t): float => ($t - $mean) ** 2, $times)) / count($times);
cli_writeln(json_encode([
    'courseid' => $courseid,
    'iterations' => $iterations,
    'meanms' => round($mean, 3),
    'stdevms' => round(sqrt($variance), 3),
    'readspercall' => round($reads / $iterations, 2),
], JSON_PRETTY_PRINT));
