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

namespace block_coursecare\output;

/**
 * Rendering tests of the "Your course in numbers" page (contracts/overview-page.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\output\course_overview
 */
final class course_overview_test extends \advanced_testcase {
    /**
     * A course with the given resources, a teacher as current user, and a completed diagnostic of the generator with
     * the given materials per activity (keyed by activity name) and categories.
     *
     * @param array $materials Materials bytes by activity name ('' = course level).
     * @param array $categories Logical bytes by category.
     * @param array $extra Extra fields of the diagnostic record.
     * @return array{0: \stdClass, 1: array} The course and the course modules by name.
     */
    private function course_with_diagnostic(array $materials, array $categories, array $extra = []): array {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $cms = [];
        $activities = [];
        foreach ($materials as $name => $bytes) {
            $cmid = 0;
            if ($name !== '') {
                $cms[$name] = $generator->create_module('resource', ['course' => $course->id, 'name' => $name]);
                $cmid = (int)$cms[$name]->cmid;
            }
            $activities[$cmid] = ['materialbytes' => $bytes, 'logicalbytes' => $bytes];
        }
        $rows = [];
        foreach ($categories as $category => $bytes) {
            $rows[$category] = ['filecount' => 1, 'logicalbytes' => $bytes, 'uniquecount' => 1, 'uniquebytes' => $bytes];
        }
        /** @var \block_coursecare_generator $plugin */
        $plugin = $generator->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic($extra + ['courseid' => $course->id, 'timecompletedago' => 600,
            'logicalbytes' => array_sum($categories), 'filecount' => 3, 'categories' => $rows,
            'activities' => $activities]);
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        return [$course, $cms];
    }

    /**
     * Render the page of a course.
     *
     * @param int $courseid Course id.
     * @return string HTML.
     */
    private function render(int $courseid): string {
        global $PAGE;
        $PAGE->set_url('/blocks/coursecare/overview.php');
        $output = $PAGE->get_renderer('core');
        return $output->render_from_template(
            'block_coursecare/overview',
            course_overview::for_course($courseid)->export_for_template($output)
        );
    }

    /**
     * State D: categories with their explanation, size and share in text; materials per activity with links; the
     * first ten and "Show all" for the rest; no query to the files table (CT-04, CT-07, CT-20).
     */
    public function test_complete_page(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $materials = ['' => 1000];
        for ($i = 1; $i <= 12; $i++) {
            $materials["Reading {$i}"] = 1000 * $i;
        }
        $total = array_sum($materials);
        [$course, $cms] = $this->course_with_diagnostic($materials, ['material' => $total, 'submission' => $total]);

        // Warm the course module cache of the core, as the course page does before the link is followed.
        get_fast_modinfo($course->id);
        $DB->set_debug(true);
        ob_start();
        $html = $this->render((int)$course->id);
        $queries = ob_get_clean();
        $DB->set_debug(false);
        $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($CFG->prefix, '/') . 'files\b/', $queries);

        foreach (['material', 'submission', 'other', 'unclassified'] as $category) {
            $this->assertStringContainsString(s(get_string("overview:category:{$category}", 'block_coursecare')), $html);
            $this->assertStringContainsString(
                s(get_string("overview:category:{$category}_help", 'block_coursecare')),
                $html
            );
        }
        $this->assertStringContainsString(s(get_string('overview:share', 'block_coursecare', 50)), $html);
        $this->assertStringContainsString(diagnostic_line::size($total), $html);
        $this->assertStringContainsString(s(get_string('overview:activities', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:courselevel', 'block_coursecare')), $html);
        $url = s((new \moodle_url('/mod/resource/view.php', ['id' => $cms['Reading 12']->cmid]))->out(false));
        $this->assertStringContainsString('href="' . $url . '"', $html);
        $this->assertStringContainsString('Reading 12', $html);
        $this->assertStringContainsString(s(get_string('overview:showall', 'block_coursecare', 12)), $html);
        $this->assertMatchesRegularExpression('/<details[^>]*>\s*<summary[^>]*>[^<]*' .
            preg_quote(s(get_string('overview:showall', 'block_coursecare', 12)), '/') . '/', $html);
        // The two smallest activities are only inside "Show all".
        $this->assertGreaterThan(strpos($html, '<details'), strpos($html, '>Reading 1<'));
        $this->assertGreaterThan(strpos($html, '<details'), strpos($html, '>Reading 2<'));
        // Bars are decorative; the share is always in text.
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    /**
     * Without a result, the page shows only the state message, no number and no section.
     */
    public function test_page_without_result(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $html = $this->render((int)$course->id);
        $this->assertStringContainsString(s(get_string('diagnostic:notavailable', 'block_coursecare')), $html);
        $this->assertStringNotContainsString(s(get_string('overview:categories', 'block_coursecare')), $html);
        $this->assertStringNotContainsString(s(get_string('overview:activities', 'block_coursecare')), $html);
        $this->assertDoesNotMatchRegularExpression('/\d/', strip_tags(preg_replace('/<a [^>]*>/', '', $html)));
    }
    /**
     * The page in each state: the sentence of the state, the technical details collapsed and always explaining what
     * the numbers do not mean, and no section without data (FR-020; CT-05, CT-11).
     */
    public function test_states(): void {
        $this->resetAfterTest();
        $technical = s(get_string('overview:technical', 'block_coursecare'));
        $notmeaning = s(get_string('overview:notmeaning', 'block_coursecare'));
        $categories = s(get_string('overview:categories', 'block_coursecare'));
        $partial = s(get_string('overview:partial', 'block_coursecare', (object)['size' => diagnostic_line::size(120),
            'share' => get_string('overview:share', 'block_coursecare', 12)]));
        $cases = [
            'E' => [['timecompletedago' => 4 * DAYSECS], get_string('diagnostic:mayoutdated', 'block_coursecare'), true],
            'F' => [['lastattemptfailed' => 1], get_string('diagnostic:updatefailed', 'block_coursecare'), true],
            'G' => [['logicalbytes' => 0, 'filecount' => 0, 'categories' => []],
                get_string('diagnostic:nofiles', 'block_coursecare'), false],
            'H' => [['partial' => 1, 'unclassifiedcount' => 2, 'unclassifiedbytes' => 120], $partial, true],
        ];
        foreach ($cases as $name => [$values, $expected, $hassections]) {
            [$course] = $this->course_with_diagnostic(
                ['Reading' => 880],
                ['material' => 880, 'unclassified' => 120],
                $values
            );
            $html = $this->render((int)$course->id);
            $this->assertStringContainsString(s($expected), $html, $name);
            $this->assertMatchesRegularExpression('/<details class="block_coursecare-overview-technical">\s*<summary><h3[^>]*>' .
                preg_quote($technical, '/') . '/', $html, $name);
            $this->assertStringContainsString($notmeaning, $html, $name);
            $this->assertSame($hassections, str_contains($html, $categories), $name);
        }

        // E and F of a result without files: the sentence of G with the date and the note of the state, no volume
        // and no section but the technical details, as in the block (T056).
        $nofiles = ['logicalbytes' => 0, 'filecount' => 0, 'categories' => [], 'activities' => []];
        $cases = [
            'E without files' => [['timecompletedago' => 4 * DAYSECS], 'diagnostic:mayoutdated'],
            'F without files' => [['lastattemptfailed' => 1], 'diagnostic:updatefailed'],
        ];
        foreach ($cases as $name => [$values, $note]) {
            [$course] = $this->course_with_diagnostic([], [], $values + $nofiles);
            $html = $this->render((int)$course->id);
            $this->assertStringContainsString(s(get_string('diagnostic:nofiles', 'block_coursecare')), $html, $name);
            $this->assertStringContainsString(s(get_string($note, 'block_coursecare')), $html, $name);
            $calculated = substr(get_string('diagnostic:calculatedon', 'block_coursecare', ''), 0, 10);
            $this->assertStringContainsString($calculated, $html, $name);
            $this->assertStringNotContainsString(s(get_string('overview:files', 'block_coursecare')), $html, $name);
            $this->assertStringNotContainsString($categories, $html, $name);
            $this->assertStringNotContainsString(s(get_string('overview:activities', 'block_coursecare')), $html, $name);
            $this->assertStringNotContainsString(s(get_string('overview:large', 'block_coursecare')), $html, $name);
            $this->assertStringContainsString($technical, $html, $name);
        }

        // B: a request is queued and there is no result yet.
        $course = $this->getDataGenerator()->create_course();
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic(['courseid' => $course->id, 'requeststatus' => 1, 'timerequested' => time()]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $html = $this->render((int)$course->id);
        $this->assertStringContainsString(s(get_string('diagnostic:preparing', 'block_coursecare')), $html);
        $this->assertStringNotContainsString($technical, $html);
    }

    /**
     * Technical details: unique content, references, copies, coverage and threshold, each with its explanation.
     */
    public function test_technical_details(): void {
        $this->resetAfterTest();
        [$course] = $this->course_with_diagnostic(
            ['Reading' => 1000],
            ['material' => 1000, 'backup' => 4000],
            ['logicalbytes' => 1000, 'uniquecount' => 1, 'uniquebytes' => 800, 'refinternalcount' => 3, 'refinternalbytes' => 900,
            'refexternalcount' => 1, 'refexternalbytes' => 5000, 'refundeterminedcount' => 4, 'bruniquebytes' => 4000,
            'largethreshold' => 52428800]
        );
        $html = $this->render((int)$course->id);
        $strings = [
            ['overview:unique', diagnostic_line::size(800)],
            ['overview:unique_help', null],
            ['overview:references', null],
            ['overview:references_help', null],
            ['overview:references_internal', (object)['count' => 3, 'size' => diagnostic_line::size(900)]],
            ['overview:references_external', (object)['count' => 1, 'size' => diagnostic_line::size(5000)]],
            ['overview:references_undetermined', 4],
            ['overview:copies', null],
            ['overview:copies_help', null],
            ['overview:copies_backup', (object)['count' => 1, 'size' => diagnostic_line::size(4000)]],
            ['overview:copies_unique', diagnostic_line::size(4000)],
            ['overview:threshold', diagnostic_line::size(52428800)],
        ];
        foreach ($strings as [$key, $a]) {
            $this->assertStringContainsString(s(get_string($key, 'block_coursecare', $a)), $html, $key);
        }
    }
    /**
     * FR-030, FR-031, FR-034 and SC-006: nothing per activity reveals submissions or other files of students, an
     * activity with submissions only is not listed, and no student name, file name or file link appears (CT-14).
     */
    public function test_privacy(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $essay = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Essay with a guide']);
        $quiet = $generator->create_module('assign', ['course' => $course->id, 'name' => 'Submissions only']);
        $students = [];
        foreach (['Zuleica', 'Yaroslav'] as $firstname) {
            $students[] = $generator->create_and_enrol($course, 'student', ['firstname' => $firstname,
                'lastname' => 'Privatestudent']);
        }
        $mb = 1048576;
        // Distinct sizes per activity, none equal to a total of the course.
        $perassign = ['filecount' => 37, 'logicalbytes' => 10 * $mb + 2048, 'materialbytes' => 2048,
            'submissionbytes' => 7 * $mb, 'otherbytes' => 3 * $mb, 'largecount' => 1, 'largebytes' => 70 * $mb];
        $perquiet = ['filecount' => 5, 'logicalbytes' => 6 * $mb, 'materialbytes' => 0, 'submissionbytes' => 5 * $mb,
            'otherbytes' => $mb];
        /** @var \block_coursecare_generator $plugin */
        $plugin = $generator->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic(['courseid' => $course->id, 'timecompletedago' => 600, 'filecount' => 42,
            'logicalbytes' => 16 * $mb + 2048, 'categories' => [
                'material' => ['filecount' => 1, 'logicalbytes' => 2048],
                'submission' => ['filecount' => 30, 'logicalbytes' => 12 * $mb],
                'other' => ['filecount' => 11, 'logicalbytes' => 4 * $mb],
            ], 'activities' => [$essay->cmid => $perassign, $quiet->cmid => $perquiet]]);
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $html = $this->render((int)$course->id);

        // The activity with materials shows only its materials; the one with submissions only is not listed.
        $this->assertStringContainsString('Essay with a guide', $html);
        $this->assertStringContainsString(diagnostic_line::size(2048), $html);
        $this->assertStringNotContainsString('Submissions only', $html);
        foreach ([$perassign, $perquiet] as $values) {
            foreach (['logicalbytes', 'submissionbytes', 'otherbytes', 'largebytes'] as $field) {
                if (!empty($values[$field])) {
                    // A whole number only: "6.0 MB" must not match inside the course total "16.0 MB".
                    $size = preg_quote(diagnostic_line::size($values[$field]), '/');
                    $this->assertDoesNotMatchRegularExpression('/(?<![\d.])' . $size . '/u', $html, $field);
                }
            }
        }
        $this->assertDoesNotMatchRegularExpression('/\b37\b/', strip_tags($html));
        // The course totals of submissions are shown, as the decision of the product owner allows.
        $this->assertStringContainsString(diagnostic_line::size(12 * $mb), $html);

        // No student name, no file name or link to a file.
        foreach ($students as $student) {
            $this->assertStringNotContainsString($student->firstname, $html);
            $this->assertStringNotContainsString($student->lastname, $html);
        }
        $this->assertStringNotContainsString('pluginfile.php', $html);
        $this->assertStringNotContainsString('draftfile.php', $html);
        $this->assertFalse($DB->record_exists('block_coursecare_large', []));
    }
    /**
     * Activity names come formatted by format_string() and are shown escaped once, in the materials by activity and
     * in the large materials by activity.
     */
    public function test_activity_names_escaped_once(): void {
        $this->resetAfterTest();
        $mb = 1048576;
        [$course, $cms] = $this->course_with_diagnostic(
            ['Leituras & exercícios' => 60 * $mb],
            ['material' => 60 * $mb],
            ['largecount' => 1, 'largebytes' => 60 * $mb, 'largethreshold' => 50 * $mb]
        );
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic_largelink(['courseid' => $course->id, 'cmid' => $cms['Leituras & exercícios']->cmid,
            'filesize' => 60 * $mb]);
        $html = $this->render((int)$course->id);

        // In the materials by activity, the large materials by activity and the suggestion (Feature 004).
        $this->assertSame(3, substr_count($html, '>Leituras &amp; exercícios</a>'));
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    /**
     * Large files section: totals of the course with the threshold used, the screening explanation, materials per
     * activity with a link, and the truncation notice only when the links were truncated (CT-10, CT-22).
     */
    public function test_large_files(): void {
        global $DB;
        $this->resetAfterTest();
        $mb = 1048576;
        [$course, $cms] = $this->course_with_diagnostic(
            ['Lab video' => 60 * $mb, 'Slides' => 2 * $mb],
            ['material' => 62 * $mb, 'submission' => 60 * $mb],
            ['largecount' => 2, 'largebytes' => 120 * $mb,
            'largethreshold' => 50 * $mb]
        );
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic_largelink(['courseid' => $course->id, 'cmid' => $cms['Lab video']->cmid,
            'filesize' => 60 * $mb]);
        $html = $this->render((int)$course->id);

        $this->assertStringContainsString(s(get_string('overview:large', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:large_summary', 'block_coursecare', (object)[
            'threshold' => diagnostic_line::size(50 * $mb), 'count' => 2, 'size' => diagnostic_line::size(120 * $mb)])), $html);
        $this->assertStringContainsString(s(get_string('overview:large_help', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:large_materials', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:large_count', 'block_coursecare', 1)), $html);
        $url = s((new \moodle_url('/mod/resource/view.php', ['id' => $cms['Lab video']->cmid]))->out(false));
        // In the materials by activity, the large materials by activity and the suggestion (Feature 004).
        $this->assertSame(3, substr_count($html, 'href="' . $url . '"'));
        $this->assertStringNotContainsString(s(get_string('overview:large_truncated', 'block_coursecare')), $html);

        // With truncated links, the notice appears.
        $DB->set_field('block_coursecare_diag', 'largelinkstruncated', 1, ['courseid' => $course->id]);
        $html = $this->render((int)$course->id);
        $this->assertStringContainsString(s(get_string('overview:large_truncated', 'block_coursecare')), $html);
    }

    /**
     * The suggestions section of a rendered page, or null when there is none.
     *
     * @param string $html Page HTML.
     * @return string|null
     */
    private static function suggestions_section(string $html): ?string {
        if (!preg_match('/<section id="block_coursecare-suggestions".*?<\/section>/s', $html, $match)) {
            return null;
        }
        return $match[0];
    }

    /**
     * A course with large material links: one per activity name (or '' for the course level) with its size.
     *
     * @param array $links Size in bytes by activity name ('' = course level, 'removed' = an activity that no longer
     *     exists).
     * @param array $extra Extra fields of the diagnostic record.
     * @return array{0: \stdClass, 1: array} The course and its course modules by name.
     */
    private function course_with_large_links(array $links, array $extra = []): array {
        $mb = 1048576;
        $materials = [];
        foreach ($links as $name => $bytes) {
            if ($name !== 'removed') {
                $materials[$name] = $bytes;
            }
        }
        $total = array_sum($materials);
        [$course, $cms] = $this->course_with_diagnostic(
            $materials,
            ['material' => $total],
            $extra + ['largethreshold' => $mb]
        );
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        foreach ($links as $name => $bytes) {
            $cmid = match ($name) {
                '' => 0,
                'removed' => 987654,
                default => (int)$cms[$name]->cmid,
            };
            $plugin->create_diagnostic_largelink(['courseid' => $course->id, 'cmid' => $cmid, 'filesize' => $bytes]);
        }
        return [$course, $cms];
    }

    /**
     * Suggestion S1: the section after the opening, the three labelled parts, the counts of the large course
     * materials only (a large submission in the course summary is left out), the places with links, the good
     * practices as options, the date of the analysis, and the variants of the text (Feature 004, T005).
     */
    public function test_suggestion_large_materials(): void {
        global $DB;
        $this->resetAfterTest();
        $mb = 1048576;
        $str = fn(string $key, $a = null): string => s(get_string($key, 'block_coursecare', $a));

        // Two activities with large materials, and a large submission counted only in the course summary.
        [$course, $cms] = $this->course_with_large_links(
            ['Lab video' => 3 * $mb, 'Slides' => 2 * $mb],
            ['largecount' => 3, 'largebytes' => 9 * $mb]
        );
        $html = $this->render((int)$course->id);
        $section = self::suggestions_section($html);
        $this->assertNotNull($section);
        $this->assertLessThan(strpos($html, $str('overview:categories')), strpos($html, 'id="block_coursecare-suggestions"'));
        $this->assertStringContainsString($str('suggestions:heading'), $section);
        $this->assertStringContainsString($str('suggestion:large:title'), $section);
        foreach (['suggestion:observed', 'suggestion:why', 'suggestion:todo'] as $label) {
            $this->assertStringContainsString('<dt>' . $str($label) . '</dt>', $section, $label);
        }
        $this->assertStringContainsString($str('suggestion:large:observed', (object)['count' => 2,
            'threshold' => diagnostic_line::size($mb), 'size' => diagnostic_line::size(5 * $mb)]), $section);
        foreach (['Lab video', 'Slides'] as $name) {
            $url = s((new \moodle_url('/mod/resource/view.php', ['id' => $cms[$name]->cmid]))->out(false));
            $this->assertStringContainsString('<a href="' . $url . '">' . $name . '</a>', $section, $name);
        }
        foreach (
            ['suggestion:large:why', 'suggestion:large:todo', 'suggestion:large:option1', 'suggestion:large:option2',
                'suggestion:large:option3'] as $key
        ) {
            $this->assertStringContainsString($str($key), $section, $key);
        }
        $time = (int)$DB->get_field('block_coursecare_diag', 'timecompleted', ['courseid' => $course->id]);
        $this->assertStringContainsString($str(
            'suggestions:basedon',
            userdate($time, get_string('strftimedatetimeshort', 'langconfig'))
        ), $section);
        $this->assertStringNotContainsString($str('suggestion:large:more', 0), $section);
        $this->assertStringNotContainsString($str('overview:large_truncated'), $section);

        // One large material: the singular sentence.
        [$course] = $this->course_with_large_links(['Lab video' => 3 * $mb]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('suggestion:large:observed_one', (object)[
            'threshold' => diagnostic_line::size($mb), 'size' => diagnostic_line::size(3 * $mb)]), $section);

        // Five places: three cited, the largest first, and two more.
        [$course] = $this->course_with_large_links(['A1' => 5 * $mb, 'A2' => 4 * $mb, 'A3' => 3 * $mb,
            'A4' => 2 * $mb, 'A5' => (int)(1.5 * $mb)]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertMatchesRegularExpression('/>A1<\/a>.*>A2<\/a>.*>A3<\/a>/s', $section);
        $this->assertStringNotContainsString('>A4</a>', $section);
        $this->assertStringContainsString($str('suggestion:large:more', 2), $section);

        // Four places: one more, in the singular.
        [$course] = $this->course_with_large_links(['A1' => 5 * $mb, 'A2' => 4 * $mb, 'A3' => 3 * $mb, 'A4' => 2 * $mb]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('suggestion:large:more_one'), $section);

        // The course level is a place, with a link to the course page.
        [$course] = $this->course_with_large_links(['' => 4 * $mb, 'Slides' => 2 * $mb]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $courseurl = s((new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false));
        $this->assertStringContainsString('<a href="' . $courseurl . '">' . $str('overview:courselevel') . '</a>', $section);

        // An activity the teacher cannot see and a removed one: never named, only counted in "more".
        [$course, $cms] = $this->course_with_large_links(['Visible' => 2 * $mb, 'Secret plan' => 3 * $mb,
            'removed' => 4 * $mb]);
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability(
            'moodle/course:viewhiddenactivities',
            CAP_PROHIBIT,
            $roleid,
            \context_course::instance($course->id)->id
        );
        set_coursemodule_visible($cms['Secret plan']->cmid, 0);
        rebuild_course_cache((int)$course->id, true);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertStringContainsString('>Visible</a>', $section);
        $this->assertStringNotContainsString('Secret plan', $section);
        $this->assertStringContainsString($str('suggestion:large:more', 2), $section);

        // Truncated links: the notice inside the suggestion.
        [$course] = $this->course_with_large_links(['Lab video' => 3 * $mb], ['largelinkstruncated' => 1]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('overview:large_truncated'), $section);
    }

    /**
     * A large submission without any large course material makes no suggestion; no student or file name appears;
     * the new sentences hold no obligation, judgement or promise, in English and in Brazilian Portuguese (Feature
     * 004, T006).
     */
    public function test_suggestion_privacy(): void {
        global $CFG;
        $this->resetAfterTest();
        $mb = 1048576;
        [$course] = $this->course_with_diagnostic(
            ['Reading' => 1000],
            ['material' => 1000, 'submission' => 9 * $mb],
            ['largecount' => 1, 'largebytes' => 9 * $mb, 'largethreshold' => $mb]
        );
        $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Zelda', 'lastname' => 'Quixote']);
        $html = $this->render((int)$course->id);
        $section = self::suggestions_section($html);
        $this->assertNotNull($section);
        $this->assertStringNotContainsString(s(get_string('suggestion:large:title', 'block_coursecare')), $section);
        $this->assertStringContainsString(s(get_string('suggestions:none', 'block_coursecare')), $section);
        $this->assertStringNotContainsString('Quixote', $html);

        // Words of obligation, judgement or promise, in the rendered English suggestion.
        [$course] = $this->course_with_large_links(['Lab video' => 3 * $mb]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertDoesNotMatchRegularExpression('/\b(must|should|waste|save|delete)\b/i', strip_tags($section));

        // And in the Brazilian Portuguese translation of the new keys.
        $string = [];
        include($CFG->dirroot . '/blocks/coursecare/translations/pt_br/block_coursecare.php');
        $new = array_filter($string, fn(string $key): bool => str_starts_with($key, 'suggestion')
            || $key === 'block:suggestion', ARRAY_FILTER_USE_KEY);
        $this->assertCount(17, $new);
        $this->assertDoesNotMatchRegularExpression(
            '/\b(deve|desperdício|excluir|economia|economizar)\b/iu',
            implode(' ', $new)
        );
    }

    /**
     * The suggestions section in each state (FR-020 of Feature 004): absent without a result; the neutral sentence
     * with a result and no suggestion, also without files; the suggestion with its date in the out-of-date and failed
     * states (T012).
     */
    public function test_suggestion_states(): void {
        $this->resetAfterTest();
        $mb = 1048576;
        $str = fn(string $key, $a = null): string => s(get_string($key, 'block_coursecare', $a));
        $none = $str('suggestions:none');
        $title = $str('suggestion:large:title');

        // A: never analysed; B: a request is queued and there is no result yet.
        $course = $this->getDataGenerator()->create_course();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $this->assertNull(self::suggestions_section($this->render((int)$course->id)), 'A');
        $course = $this->getDataGenerator()->create_course();
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $plugin->create_diagnostic(['courseid' => $course->id, 'requeststatus' => 1, 'timerequested' => time()]);
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $this->assertNull(self::suggestions_section($this->render((int)$course->id)), 'B');

        // With a result and no large course material, the neutral sentence, in every state.
        $nofiles = ['logicalbytes' => 0, 'filecount' => 0, 'categories' => [], 'activities' => []];
        $cases = [
            'D' => [['Reading' => 880], ['material' => 880], []],
            'H' => [['Reading' => 880], ['material' => 880, 'unclassified' => 120],
                ['partial' => 1, 'unclassifiedcount' => 2, 'unclassifiedbytes' => 120]],
            'G' => [[], [], $nofiles],
            'E without files' => [[], [], ['timecompletedago' => 4 * DAYSECS] + $nofiles],
            'F without files' => [[], [], ['lastattemptfailed' => 1] + $nofiles],
        ];
        foreach ($cases as $name => [$materials, $categories, $extra]) {
            [$course] = $this->course_with_diagnostic($materials, $categories, $extra);
            $section = self::suggestions_section($this->render((int)$course->id));
            $this->assertNotNull($section, $name);
            $this->assertStringContainsString($none, $section, $name);
            $this->assertStringNotContainsString($title, $section, $name);
            // Neither praise nor blame, and no date repeated in the neutral case.
            $this->assertDoesNotMatchRegularExpression(
                '/\b(good|great|well done|bad|problem|congratulations)\b/i',
                strip_tags($section),
                $name
            );
            $this->assertStringNotContainsString(substr($str('suggestions:basedon', ''), 0, 20), $section, $name);
        }

        // E and F with large course materials: the suggestion with its date, and the note of the opening.
        $cases = [
            'E' => [['timecompletedago' => 4 * DAYSECS], 'diagnostic:mayoutdated'],
            'F' => [['lastattemptfailed' => 1], 'diagnostic:updatefailed'],
        ];
        foreach ($cases as $name => [$extra, $note]) {
            [$course] = $this->course_with_large_links(['Lab video' => 3 * $mb], $extra);
            $html = $this->render((int)$course->id);
            $section = self::suggestions_section($html);
            $this->assertStringContainsString($title, $section, $name);
            $this->assertStringContainsString(substr($str('suggestions:basedon', ''), 0, 20), $section, $name);
            $this->assertStringContainsString($str($note), $html, $name);
        }
    }
}
