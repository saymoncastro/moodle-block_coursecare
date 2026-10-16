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
 * Development tool (not packaged): synthetic courses for the storage diagnostic benchmark (benchmark.md).
 *
 * Creates courses with the CourseCare block, activities made by the core generators and files written with the
 * real File API in their real areas. Contents are pseudo-random with a fixed seed, so duplication is controlled.
 * Use only on a disposable development site.
 *
 * Usage: php blocks/coursecare/tools/dev/bench/generate.php --profile=small --courses=10 [--seed=1] [--manifest=FILE]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

/** Profiles of benchmark.md: files per course, activities, share of repeated content and of large files. */
const BENCH_PROFILES = [
    'small' => ['files' => [10, 50], 'activities' => 5, 'repeat' => 0.10, 'large' => 0.0],
    'medium' => ['files' => [1000, 5000], 'activities' => 20, 'repeat' => 0.25, 'large' => 0.01],
    'large' => ['files' => [20000, 50000], 'activities' => 200, 'repeat' => 0.25, 'large' => 0.01],
    'extreme' => ['files' => [200000, 200000], 'activities' => 200, 'repeat' => 0.25, 'large' => 0.01],
];

[$options, $unrecognised] = cli_get_params(
    ['profile' => 'small', 'courses' => 1, 'seed' => 1, 'manifest' => '', 'help' => false],
    ['h' => 'help']
);
if ($options['help'] || $unrecognised || !isset(BENCH_PROFILES[$options['profile']])) {
    cli_writeln('Usage: php generate.php --profile=small|medium|large|extreme --courses=N [--seed=N] [--manifest=FILE]');
    exit($options['help'] ? 0 : 1);
}
if (!debugging('', DEBUG_DEVELOPER)) {
    cli_error('Developer debugging is required: this tool is for disposable development sites only.');
}

\core\session\manager::set_user(get_admin());
$profile = BENCH_PROFILES[$options['profile']];
$courses = max(1, (int)$options['courses']);
mt_srand((int)$options['seed']);
$generator = phpunit_util::get_data_generator();
$fs = get_file_storage();
$manifest = [];
$warnedlarge = false;

for ($c = 0; $c < $courses; $c++) {
    $course = $generator->create_course(['shortname' => 'BENCH-' . $options['profile'] . '-' . $options['seed'] . '-' . $c]);
    $coursecontext = context_course::instance($course->id);
    $generator->create_block('coursecare', ['parentcontextid' => $coursecontext->id, 'pagetypepattern' => 'course-view-*']);

    // Activities: materials (resource), submissions (assignment) and other files (forum), real areas only.
    $areas = [];
    for ($a = 0; $a < $profile['activities']; $a++) {
        $kind = ['resource', 'resource', 'assign', 'forum'][$a % 4];
        $module = $generator->create_module($kind, ['course' => $course->id]);
        $context = context_module::instance($module->cmid);
        $fs->delete_area_files($context->id);
        $areas[] = match ($kind) {
            'resource' => [$context->id, 'mod_resource', 'content'],
            'assign' => [$context->id, 'assignsubmission_file', 'submission_files'],
            'forum' => [$context->id, 'mod_forum', 'attachment'],
        };
    }

    $count = mt_rand($profile['files'][0], $profile['files'][1]);
    $contents = [];
    $logical = 0;
    $unique = [];
    for ($i = 0; $i < $count; $i++) {
        $large = $profile['large'] > 0 && mt_rand() / mt_getrandmax() < $profile['large'];
        if (!$large && $contents && mt_rand() / mt_getrandmax() < $profile['repeat']) {
            $content = $contents[mt_rand(0, count($contents) - 1)];
        } else {
            $content = substr(str_repeat(hash('sha256', $options['seed'] . "-{$c}-{$i}"), 32), 0, mt_rand(16, 1024));
            if (!$large) {
                // Contents of large files are never reused, so each adjusted size belongs to one content only.
                $contents[] = $content;
            }
        }
        [$contextid, $component, $filearea] = $areas[$i % count($areas)];
        $file = $fs->create_file_from_string(['contextid' => $contextid, 'component' => $component,
            'filearea' => $filearea, 'itemid' => intdiv($i, 100), 'filepath' => '/', 'filename' => "f{$i}.bin"], $content);
        $size = strlen($content);
        if ($large) {
            // Benchmark data only: a large recorded size without writing large contents (benchmark.md).
            $size = mt_rand(60, 500) * 1048576;
            $DB->set_field('files', 'filesize', $size, ['id' => $file->get_id()]);
            if (!$warnedlarge) {
                cli_writeln('Note: large files have an adjusted recorded size (synthetic benchmark data).');
                $warnedlarge = true;
            }
        }
        $unique[$file->get_contenthash()] = $size;
        $logical += $size;
    }
    $manifest[$course->id] = ['files' => $count, 'logicalbytes' => $logical, 'uniquebytes' => array_sum($unique)];
    cli_writeln("Course {$course->id}: {$count} files");
}

if ($options['manifest'] !== '') {
    file_put_contents($options['manifest'], json_encode($manifest, JSON_PRETTY_PRINT));
    cli_writeln('Manifest written to ' . $options['manifest']);
}
