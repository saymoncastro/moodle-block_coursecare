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
 * Development tool (not packaged): course for the measurement of the potential of assignments (Feature 005, SC-003).
 *
 * Creates one course with the CourseCare block, 200 activities (150 pages and 50 assignments with file submissions),
 * 500 students in 25 groups, 5 assignments with group submission and N assignments whose context has an override of
 * the submit capability (an allow that changes nothing, so that the exact count runs for them), analyses it and prints
 * its id, for tools/dev/bench/render.php --page --course=ID.
 *
 * Usage: php blocks/coursecare/tools/dev/bench/potential.php --confirm [--overrides=5]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

[$options, $unrecognised] = cli_get_params(['confirm' => false, 'overrides' => 5, 'help' => false], ['h' => 'help']);
$overrides = min(50, max(0, (int)$options['overrides']));
if ($options['help'] || $unrecognised || !$options['confirm']) {
    cli_writeln('Creates a measurement course for the potential of assignments. Only for a disposable development site.');
    cli_writeln('Usage: php potential.php --confirm [--overrides=5]');
    exit($options['help'] ? 0 : 1);
}

\core\session\manager::set_user(get_admin());
$generator = phpunit_util::get_data_generator();
$shortname = 'BENCH-POTENTIAL-' . $overrides . '-' . time();
$course = $generator->create_course(['shortname' => $shortname, 'fullname' => $shortname]);
$generator->create_block('coursecare', ['parentcontextid' => context_course::instance($course->id)->id,
    'pagetypepattern' => 'course-view-*', 'defaultregion' => 'side-pre']);

$groups = [];
for ($i = 1; $i <= 25; $i++) {
    $groups[] = $generator->create_group(['courseid' => $course->id, 'name' => "Group {$i}"]);
}
for ($i = 0; $i < 500; $i++) {
    $student = $generator->create_user(['username' => strtolower($shortname) . "-s{$i}"]);
    $generator->enrol_user($student->id, $course->id, 'student');
    $generator->create_group_member(['groupid' => $groups[$i % 25]->id, 'userid' => $student->id]);
}

$studentrole = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
for ($i = 1; $i <= 200; $i++) {
    if ($i % 4 !== 0) {
        $generator->create_module('page', ['course' => $course->id, 'name' => "Page {$i}"]);
        continue;
    }
    $n = $i / 4;
    $assign = $generator->create_module('assign', ['course' => $course->id, 'name' => "Assignment {$n}",
        'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 3,
        'assignsubmission_file_maxsizebytes' => 10485760, 'assignsubmission_onlinetext_enabled' => 0,
        'maxattempts' => $n % 3 === 0 ? 3 : 1, 'attemptreopenmethod' => 'manual', 'teamsubmission' => $n <= 5 ? 1 : 0]);
    if ($n > 50 - $overrides) {
        // An override equal to the inherited permission: role_change_permission() would drop it, so assign it directly.
        assign_capability('mod/assign:submit', CAP_ALLOW, $studentrole, context_module::instance($assign->cmid)->id, true);
    }
}

(new \block_coursecare\local\course_analysis())->run((int)$course->id, true);
cli_writeln(json_encode(['courseid' => (int)$course->id, 'shortname' => $shortname, 'activities' => 200,
    'assignments' => 50, 'students' => 500, 'overrides' => $overrides]));
