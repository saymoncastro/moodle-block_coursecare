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
 * Development tool (not packaged): fictitious courses for the visual validation and the teacher evaluation.
 *
 * Creates, on a disposable site, one course with the CourseCare block for each state of the diagnostic, with real
 * files written by the File API (small contents; the few large files are real files of 1 to 2 MB, large under the
 * demonstration threshold). The analyses are the real ones; only the states that need time or a failure are set
 * afterwards through the diagnostic store. No real person or institution data is used.
 *
 * Usage: php blocks/coursecare/tools/dev/demo/setup.php --confirm --password=SECRET [--threshold-mb=1] [--activities=N]
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use block_coursecare\local\analysis\indicator_calculator;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\course_analysis;

[$options, $unrecognised] = cli_get_params(
    ['confirm' => false, 'password' => '', 'threshold-mb' => 1, 'activities' => 0, 'help' => false],
    ['h' => 'help']
);
if ($options['help'] || $unrecognised || !$options['confirm'] || $options['password'] === '') {
    cli_writeln('Creates fictitious CourseCare demonstration courses. Only for a disposable development site.');
    cli_writeln('Usage: php setup.php --confirm --password=SECRET [--threshold-mb=1] [--activities=N]');
    exit($options['help'] ? 0 : 1);
}
if (!debugging('', DEBUG_DEVELOPER)) {
    cli_error('Developer debugging is required: this tool is for disposable development sites only.');
}
if ($DB->record_exists_select('course', $DB->sql_like('shortname', ':prefix'), ['prefix' => 'DEMO-%'])) {
    cli_error('Demonstration courses already exist (short names starting with DEMO-); use a fresh site.');
}

\core\session\manager::set_user(get_admin());
$generator = phpunit_util::get_data_generator();
$fs = get_file_storage();
$store = new diagnostic_store();
$mb = 1048576;
set_config('largefilethreshold', max(1, (int)$options['threshold-mb']), 'block_coursecare');
$large = max(1, (int)$options['threshold-mb']) * $mb;

/**
 * Deterministic content of a given size.
 *
 * @param int $bytes Size.
 * @param string $seed Seed, so different files have different contents.
 * @return string
 */
function demo_content(int $bytes, string $seed): string {
    return substr(str_repeat(hash('sha256', $seed), (int)ceil($bytes / 64) + 1), 0, $bytes);
}

/**
 * Write one file with the File API.
 *
 * @param int $contextid Context.
 * @param string $component Component.
 * @param string $filearea Area.
 * @param int $itemid Item.
 * @param string $filename File name.
 * @param int $bytes Size.
 * @param int|null $userid Author.
 */
function demo_file(
    int $contextid,
    string $component,
    string $filearea,
    int $itemid,
    string $filename,
    int $bytes,
    ?int $userid = null
): void {
    get_file_storage()->create_file_from_string(
        ['contextid' => $contextid, 'component' => $component,
        'filearea' => $filearea, 'itemid' => $itemid, 'filepath' => '/', 'filename' => $filename, 'userid' => $userid],
        demo_content($bytes, "{$contextid}-{$component}-{$filearea}-{$itemid}-{$filename}")
    );
}

/**
 * Demonstration history points of a course on past days (Feature 006), for this demonstration site only.
 *
 * @param int $courseid Course.
 * @param int[] $days Days ago with a point.
 * @param callable $values Values of block_coursecare_hist for a number of days ago (missing categories are 0).
 */
function demo_history(int $courseid, array $days, callable $values): void {
    global $DB;
    foreach ($days as $daysago) {
        $time = time() - $daysago * DAYSECS;
        $day = diagnostic_store::history_day($time);
        if ($DB->record_exists('block_coursecare_hist', ['courseid' => $courseid, 'timeday' => $day])) {
            // A real measurement already exists for that day.
            continue;
        }
        $point = $values($daysago) + ['materialbytes' => 0, 'submissionbytes' => 0, 'otherbytes' => 0,
            'unclassifiedbytes' => 0, 'partial' => 0, 'methodversion' => indicator_calculator::METHOD_VERSION];
        $logical = $point['materialbytes'] + $point['submissionbytes'] + $point['otherbytes'] + $point['unclassifiedbytes'];
        $DB->insert_record('block_coursecare_hist', (object)($point + ['courseid' => $courseid,
            'timeday' => $day, 'timecompleted' => $time, 'filecount' => 10,
            'logicalbytes' => $logical, 'uniquecount' => 10, 'uniquebytes' => $logical]));
    }
}

/**
 * Create a course with the CourseCare block and the teacher enrolled.
 *
 * @param string $shortname Short name (DEMO-...).
 * @param string $fullname Full name.
 * @param int $teacherid Teacher.
 * @return stdClass
 */
function demo_course(string $shortname, string $fullname, int $teacherid): stdClass {
    $generator = phpunit_util::get_data_generator();
    $course = $generator->create_course(['shortname' => $shortname, 'fullname' => $fullname]);
    $generator->create_block('coursecare', ['parentcontextid' => context_course::instance($course->id)->id,
        'pagetypepattern' => 'course-view-*', 'defaultregion' => 'side-pre']);
    $generator->enrol_user($teacherid, $course->id, 'editingteacher');
    return $course;
}

/**
 * Create a resource with one file.
 *
 * @param stdClass $course Course.
 * @param string $name Name.
 * @param string $filename File name.
 * @param int $bytes Size.
 * @param int $teacherid Author.
 */
function demo_resource(stdClass $course, string $name, string $filename, int $bytes, int $teacherid): void {
    $module = phpunit_util::get_data_generator()->create_module('resource', ['course' => $course->id, 'name' => $name]);
    $context = context_module::instance($module->cmid);
    get_file_storage()->delete_area_files($context->id, 'mod_resource', 'content');
    demo_file($context->id, 'mod_resource', 'content', 0, $filename, $bytes, $teacherid);
}

$teacher = $generator->create_user(['username' => 'demo.teacher', 'password' => $options['password'],
    'firstname' => 'Professora', 'lastname' => 'Demonstração', 'email' => 'demo.teacher@example.invalid']);
$students = [];
foreach (['Ana', 'Bruno', 'Carla'] as $n => $firstname) {
    $students[] = $generator->create_user(['username' => 'demo.student' . ($n + 1), 'firstname' => $firstname,
        'lastname' => 'Estudante', 'email' => 'demo.student' . ($n + 1) . '@example.invalid']);
}
$expected = [];

// D. Complete course: materials, submissions, other files, a large material and a large submission.
$course = demo_course('DEMO-COMPLETE', 'Biologia celular (demonstração)', $teacher->id);
foreach ($students as $student) {
    $generator->enrol_user($student->id, $course->id, 'student');
}
demo_file(context_course::instance($course->id)->id, 'course', 'summary', 0, 'capa-da-disciplina.png', 180000, $teacher->id);
demo_resource($course, 'Guia da disciplina', 'guia.pdf', 420000, $teacher->id);
demo_resource($course, 'Aula 1 — A célula', 'aula1.pdf', 2400000, $teacher->id);
demo_resource($course, 'Vídeo de laboratório', 'laboratorio.mp4', $large + 600000, $teacher->id);
$folder = $generator->create_module('folder', ['course' => $course->id, 'name' => 'Leituras complementares']);
$context = context_module::instance($folder->cmid);
$fs->delete_area_files($context->id, 'mod_folder', 'content');
demo_file($context->id, 'mod_folder', 'content', 0, 'artigo1.pdf', 650000, $teacher->id);
demo_file($context->id, 'mod_folder', 'content', 0, 'artigo2.pdf', 510000, $teacher->id);
$page = $generator->create_module('page', ['course' => $course->id, 'name' => 'Glossário ilustrado']);
demo_file(context_module::instance($page->cmid)->id, 'mod_page', 'content', 0, 'mitocondria.png', 240000, $teacher->id);
$label = $generator->create_module('label', ['course' => $course->id, 'intro' => 'Boas-vindas à disciplina']);
demo_file(context_module::instance($label->cmid)->id, 'mod_label', 'intro', 0, 'boas-vindas.png', 90000, $teacher->id);
$assignrecord = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Relatório de laboratório',
    'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 5,
    'assignsubmission_file_maxsizebytes' => 0]);
$assigncontext = context_module::instance($assignrecord->cmid);
$assign = new assign($assigncontext, get_coursemodule_from_id('assign', $assignrecord->cmid), $course);
demo_file($assigncontext->id, 'mod_assign', 'introattachment', 0, 'modelo-de-relatorio.docx', 60000, $teacher->id);
foreach ($students as $n => $student) {
    $submission = $assign->get_user_submission($student->id, true);
    $bytes = $n === 0 ? $large + 200000 : 300000 + 50000 * $n;
    demo_file(
        $assigncontext->id,
        'assignsubmission_file',
        'submission_files',
        (int)$submission->id,
        "relatorio-{$n}.pdf",
        $bytes,
        (int)$student->id
    );
}
$forum = $generator->create_module('forum', ['course' => $course->id, 'name' => 'Fórum de dúvidas']);
$discussion = $generator->get_plugin_generator('mod_forum')->create_discussion(['course' => $course->id,
    'forum' => $forum->id, 'userid' => $students[1]->id, 'attachment' => 1]);
demo_file(
    context_module::instance($forum->cmid)->id,
    'mod_forum',
    'attachment',
    (int)$discussion->firstpost,
    'duvida.jpg',
    150000,
    (int)$students[1]->id
);
$expected['DEMO-COMPLETE'] = 'D (complete)';

// More than 10 activities with materials.
$course = demo_course('DEMO-MANY', 'Química geral (demonstração)', $teacher->id);
for ($i = 1; $i <= 14; $i++) {
    demo_resource($course, "Material {$i}", "material{$i}.pdf", 50000 * $i, $teacher->id);
}
$expected['DEMO-MANY'] = 'D (14 activities with materials)';

// G. No files in the main scope.
$course = demo_course('DEMO-NOFILES', 'Filosofia (demonstração)', $teacher->id);
$generator->create_module('forum', ['course' => $course->id, 'name' => 'Debates']);
$expected['DEMO-NOFILES'] = 'G (no files)';

// H. Partial coverage: files of an area that the overview does not classify.
$course = demo_course('DEMO-PARTIAL', 'História (demonstração)', $teacher->id);
demo_resource($course, 'Linha do tempo', 'linha-do-tempo.pdf', 380000, $teacher->id);
$glossary = $generator->create_module('glossary', ['course' => $course->id, 'name' => 'Glossário da turma']);
demo_file(context_module::instance($glossary->cmid)->id, 'mod_glossary', 'attachment', 1, 'termo.png', 70000, $teacher->id);
$expected['DEMO-PARTIAL'] = 'H (partial coverage)';

// Potential of assignments (Feature 005): assignments with varied settings and no submission. The course limits
// files to 20 MB, so the 50 MB poster is limited by the course; the default of the site applies when the size is 0.
$course = demo_course('DEMO-POTENTIAL', 'Projeto integrador (demonstração)', $teacher->id);
$DB->set_field('course', 'maxbytes', 20 * $mb, ['id' => $course->id]);
foreach ($students as $student) {
    $generator->enrol_user($student->id, $course->id, 'student');
}
$groupa = $generator->create_group(['courseid' => $course->id, 'name' => 'Grupo A']);
$groupb = $generator->create_group(['courseid' => $course->id, 'name' => 'Grupo B']);
$generator->create_group_member(['groupid' => $groupa->id, 'userid' => $students[0]->id]);
$generator->create_group_member(['groupid' => $groupa->id, 'userid' => $students[1]->id]);
$generator->create_group_member(['groupid' => $groupb->id, 'userid' => $students[2]->id]);
$grouping = $generator->create_grouping(['courseid' => $course->id, 'name' => 'Projeto']);
$generator->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $groupa->id]);
$files = ['assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 3,
    'assignsubmission_file_maxsizebytes' => 10 * $mb, 'assignsubmission_onlinetext_enabled' => 0, 'maxattempts' => 1];
foreach ([
    ['name' => 'Trabalho final'],
    ['name' => 'Relatório parcial', 'assignsubmission_file_maxsizebytes' => 0],
    ['name' => 'Pôster do projeto', 'assignsubmission_file_maxfiles' => 1, 'assignsubmission_file_maxsizebytes' => 50 * $mb],
    ['name' => 'Exercícios', 'maxattempts' => 3, 'attemptreopenmethod' => 'manual'],
    ['name' => 'Diário de bordo', 'maxattempts' => -1, 'attemptreopenmethod' => 'manual'],
    ['name' => 'Projeto em grupo', 'teamsubmission' => 1],
    ['name' => 'Projeto em grupo (agrupamento)', 'teamsubmission' => 1, 'teamsubmissiongroupingid' => $grouping->id],
    ['name' => 'Reflexão escrita', 'assignsubmission_file_enabled' => 0, 'assignsubmission_onlinetext_enabled' => 1],
    ['name' => 'Entrega sem arquivos', 'assignsubmission_file_maxfiles' => 0],
    ['name' => 'Rascunho', 'visible' => 0],
] as $values) {
    $generator->create_module('assign', $values + $files + ['course' => $course->id]);
}
$expected['DEMO-POTENTIAL'] = 'G (assignments with varied settings, no file)';

// History over a year and over five years (Feature 006): the site keeps five years of history, so that the table
// shows every measurement; the chart shows the last 90 days.
set_config('historyretention', 5 * 365 * DAYSECS, 'block_coursecare');
$course = demo_course('DEMO-HISTORY-YEAR', 'Estatística (demonstração)', $teacher->id);
demo_resource($course, 'Apostila de estatística', 'apostila-estatistica.pdf', 820000, $teacher->id);
$course = demo_course('DEMO-HISTORY-5Y', 'Línguas (demonstração)', $teacher->id);
demo_resource($course, 'Gramática', 'gramatica.pdf', 640000, $teacher->id);

// E, F with a result and F without a result start from a real analysis or a failure.
$outdated = demo_course('DEMO-OUTDATED', 'Física (demonstração)', $teacher->id);
demo_resource($outdated, 'Lista de exercícios', 'lista.pdf', 280000, $teacher->id);
$failed = demo_course('DEMO-FAILED', 'Matemática (demonstração)', $teacher->id);
demo_resource($failed, 'Apostila', 'apostila.pdf', 900000, $teacher->id);
$failednoresult = demo_course('DEMO-FAILED-NORESULT', 'Geografia (demonstração)', $teacher->id);
demo_resource($failednoresult, 'Mapas', 'mapas.pdf', 450000, $teacher->id);

// Optional course for the performance measurement (SC-003).
$activities = max(0, (int)$options['activities']);
if ($activities > 0) {
    $course = demo_course('DEMO-SIZE-' . $activities, "Sala com {$activities} atividades (demonstração)", $teacher->id);
    for ($i = 1; $i <= $activities; $i++) {
        demo_resource($course, "Material {$i}", "material{$i}.pdf", 20000 + 1000 * $i, $teacher->id);
    }
    $expected['DEMO-SIZE-' . $activities] = "D ({$activities} activities with materials)";
}

// Real analyses of every course except A, B and F without a result.
$analysis = new course_analysis();
foreach ($DB->get_records_select('course', $DB->sql_like('shortname', ':prefix'), ['prefix' => 'DEMO-%']) as $record) {
    if ($record->shortname !== 'DEMO-FAILED-NORESULT') {
        $analysis->run((int)$record->id, true);
    }
}

// History (Feature 006): demonstration points on past days, only on this demonstration site. Biologia celular gets
// three months of daily points with a week without analysis and a recent decrease; História gets a change of method.
// The point of today comes from the real analysis above.
demo_history(
    (int)$DB->get_field('course', 'id', ['shortname' => 'DEMO-COMPLETE']),
    array_merge(range(90, 47), range(39, 1)),
    fn(int $daysago): array => [
        'materialbytes' => 3500000 + (90 - $daysago) * 45000 - ($daysago < 6 ? 900000 : 0),
        'submissionbytes' => $daysago > 60 ? 0 : (60 - $daysago) * 25000,
        'otherbytes' => 150000,
    ]
);
demo_history(
    (int)$DB->get_field('course', 'id', ['shortname' => 'DEMO-PARTIAL']),
    range(10, 1),
    fn(int $daysago): array => ['materialbytes' => 300000, 'unclassifiedbytes' => 70000, 'partial' => 1,
        'methodversion' => $daysago > 4 ? 0 : indicator_calculator::METHOD_VERSION]
);

// A year of daily points with two weeks without analysis; five years with a cycle of terms and some gaps.
demo_history(
    (int)$DB->get_field('course', 'id', ['shortname' => 'DEMO-HISTORY-YEAR']),
    array_merge(range(364, 200), range(185, 1)),
    fn(int $daysago): array => [
        'materialbytes' => 500000 + (364 - $daysago) * 900,
        'submissionbytes' => (int)(200000 * (1 + sin($daysago / 30))),
    ]
);
demo_history(
    (int)$DB->get_field('course', 'id', ['shortname' => 'DEMO-HISTORY-5Y']),
    array_filter(range(1825, 1), fn(int $daysago): bool => $daysago % 97 !== 0),
    fn(int $daysago): array => [
        'materialbytes' => 300000 + (1825 - $daysago) * 180,
        'submissionbytes' => (int)(150000 * (1 + sin($daysago / 58))),
    ]
);

// E: the result is older than the out-of-date limit.
$staleness = (int)get_config('block_coursecare', 'stalenessthreshold') ?: 3 * DAYSECS;
$DB->set_field('block_coursecare_diag', 'timecompleted', time() - $staleness - DAYSECS, ['courseid' => $outdated->id]);
$expected['DEMO-OUTDATED'] = 'E (out of date)';
// F: the last attempt failed; with and without a previous result.
$store->save_failure((int)$failed->id, 'dml_read_exception', time());
$expected['DEMO-FAILED'] = 'F (failed, previous result kept)';
$store->save_failure((int)$failednoresult->id, 'dml_read_exception', time());
$expected['DEMO-FAILED-NORESULT'] = 'F without a result (shown as not available)';

// A: the block was added and nothing was requested yet. B: a request is queued, no result yet.
demo_course('DEMO-NEW', 'Artes (demonstração)', $teacher->id);
$expected['DEMO-NEW'] = 'A (not analysed)';
$queued = demo_course('DEMO-QUEUED', 'Sociologia (demonstração)', $teacher->id);
$store->request((int)$queued->id, time());
$expected['DEMO-QUEUED'] = 'B (being prepared)';

foreach ($expected as $shortname => $state) {
    $id = $DB->get_field('course', 'id', ['shortname' => $shortname]);
    cli_writeln(sprintf('%-22s course %5d  %s', $shortname, $id, $state));
}
cli_writeln('Teacher: demo.teacher (password from --password). Large-file threshold: ' . (int)$options['threshold-mb'] .
    ' MB.');
