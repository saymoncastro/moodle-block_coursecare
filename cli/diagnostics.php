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
 * Administrator command line tool of the CourseCare storage diagnostic (contracts/admin-cli.md).
 *
 * Usage: php blocks/coursecare/cli/diagnostics.php --help
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use block_coursecare\local\admin_cli;

[$options, $unrecognised] = cli_get_params(
    ['queue' => false, 'run' => false, 'show' => false, 'all' => false, 'course' => false, 'json' => false, 'help' => false],
    ['h' => 'help']
);

if ($options['help']) {
    cli_writeln(get_string('cli:help', 'block_coursecare'));
    exit(admin_cli::EXIT_OK);
}

$courseid = $unrecognised ? null : admin_cli::validate_options($options);
if ($courseid === null) {
    cli_problem(get_string('cli:invalidoptions', 'block_coursecare'));
    exit(admin_cli::EXIT_INVALID);
}

$cli = new admin_cli();

if ($options['queue']) {
    [$code, $message] = $courseid === 0 ? $cli->queue_all() : $cli->queue_course($courseid);
    cli_writeln($message);
    exit($code);
}

if ($options['run']) {
    [$code, $data] = $cli->run_course($courseid);
    if ($options['json']) {
        cli_writeln(json_encode($data, JSON_PRETTY_PRINT));
    } else {
        cli_writeln(get_string('cli:runresult', 'block_coursecare', (object)[
            'courseid' => $data['courseid'],
            'outcome' => $data['outcome'] . ($data['failure'] !== null ? ' (' . $data['failure'] . ')' : ''),
            'seconds' => $data['seconds'],
            'memory' => display_size($data['peakmemory']),
        ]));
    }
    exit($code);
}

[$code, $data] = $cli->show_course($courseid);
if ($code === admin_cli::EXIT_INELIGIBLE) {
    cli_problem(get_string('cli:ineligible', 'block_coursecare', $courseid));
} else if ($options['json']) {
    cli_writeln(json_encode($data, JSON_PRETTY_PRINT));
} else {
    cli_writeln(admin_cli::format_show($data));
}
exit($code);
