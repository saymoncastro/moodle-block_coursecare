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
        // The observed sentence is HTML, with the count and the total size emphasised.
        $observed = fn(string $key, int $count, int $threshold, int $bytes): string => get_string(
            $key,
            'block_coursecare',
            (object)['count' => '<strong>' . $count . '</strong>',
            'threshold' => s(diagnostic_line::size($threshold)),
            'size' => '<strong>' . s(diagnostic_line::size($bytes)) . '</strong>']
        );

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
        $this->assertStringContainsString($observed('suggestion:large:observed', 2, $mb, 5 * $mb), $section);
        // Each place with its size, as in the large materials by activity.
        $this->assertMatchesRegularExpression('/>Lab video<\/a><\/span>\s*<span class="block_coursecare-overview-placesize">'
            . preg_quote(s(diagnostic_line::size(3 * $mb)), '/') . '</', $section);
        foreach (['Lab video', 'Slides'] as $name) {
            $url = s((new \moodle_url('/mod/resource/view.php', ['id' => $cms[$name]->cmid]))->out(false));
            $this->assertStringContainsString('<a href="' . $url . '">' . $name . '</a>', $section, $name);
        }
        foreach (
            ['suggestion:large:why', 'suggestion:large:todo', 'suggestion:large:option1',
                'suggestion:large:option1_example', 'suggestion:large:option2', 'suggestion:large:option3'] as $key
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
        $this->assertStringContainsString($observed('suggestion:large:observed_one', 1, $mb, 3 * $mb), $section);

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

        // Large materials only in removed activities: no named place, so a sentence without the list and without
        // "and N more"; in the singular with one material.
        [$course] = $this->course_with_large_links(['removed' => 4 * $mb]);
        $section = self::suggestions_section($this->render((int)$course->id));
        $this->assertStringContainsString($observed('suggestion:large:observed_unnamed_one', 1, $mb, 4 * $mb), $section);
        $this->assertStringNotContainsString('block_coursecare-overview-places', $section);
        $this->assertStringNotContainsString($str('suggestion:large:more_one'), $section);
        [$course, $cms] = $this->course_with_large_links(['Secret plan' => 3 * $mb, 'removed' => 4 * $mb]);
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
        $this->assertStringContainsString($observed('suggestion:large:observed_unnamed', 2, $mb, 7 * $mb), $section);
        $this->assertStringNotContainsString('block_coursecare-overview-places', $section);
        $this->assertStringNotContainsString($str('suggestion:large:more', 2), $section);
        $this->assertStringNotContainsString('Secret plan', $section);

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
        $this->assertCount(20, $new);
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

    /**
     * The potential section of a rendered page, or null when there is none (Feature 005).
     *
     * @param string $html Page HTML.
     * @return string|null
     */
    private static function potential_section(string $html): ?string {
        if (!preg_match('/<section id="block_coursecare-potential".*?<\/section>/s', $html, $match)) {
            return null;
        }
        return $match[0];
    }

    /**
     * The item of one assignment in the potential section, or '' when it is not listed.
     *
     * @param string $section Potential section HTML.
     * @param string $name Name of the assignment.
     * @return string
     */
    private static function potential_item(string $section, string $name): string {
        preg_match_all('/<li class="block_coursecare-overview-potentialitem">.*?<\/li>/s', $section, $matches);
        foreach ($matches[0] as $item) {
            if (str_contains($item, '>' . $name . '<')) {
                return $item;
            }
        }
        return '';
    }

    /**
     * An assignment with file submissions: 3 files of 1 MB, 1 attempt (below any upload limit of a server).
     *
     * @param \stdClass $course Course.
     * @param string $name Name.
     * @param array $values Overrides of the module record.
     * @return \stdClass Module record.
     */
    private function create_assign(\stdClass $course, string $name, array $values = []): \stdClass {
        return $this->getDataGenerator()->create_module('assign', $values + ['course' => $course->id, 'name' => $name,
            'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 3,
            'assignsubmission_file_maxsizebytes' => 1048576, 'assignsubmission_onlinetext_enabled' => 0,
            'maxattempts' => 1]);
    }

    /**
     * The potential section of a page built from given results, without a diagnostic.
     *
     * @param array $potential As course_overview expects.
     * @return string Section HTML ('' when there is none).
     */
    private function render_potential(array $potential): string {
        global $PAGE;
        $PAGE->set_url('/blocks/coursecare/overview.php');
        $output = $PAGE->get_renderer('core');
        $state = \block_coursecare\local\analysis\state_resolver::resolve(null, time(), DAYSECS);
        $view = (new \block_coursecare\local\analysis\overview_builder())->build($state, null, [], [], [], []);
        $page = new course_overview($view, new \moodle_url('/course/view.php', ['id' => SITEID]), $potential);
        $html = $output->render_from_template('block_coursecare/overview', $page->export_for_template($output));
        return (string)self::potential_section($html);
    }

    /**
     * The section lists the assignments with file submissions in the order of the course, each with its settings, the
     * level of its limit, attempts, who can submit and the theoretical potential; no total and no assignment without
     * file submissions (Feature 005, T009; CT-01 to CT-06, CT-08).
     */
    public function test_potential_section(): void {
        $this->resetAfterTest();
        $mb = 1048576;
        $str = fn(string $key, $a = null): string => s(get_string('potential:' . $key, 'block_coursecare', $a));
        $size = fn(int $bytes): string => diagnostic_line::size($bytes);
        [$course] = $this->course_with_diagnostic(['Reading' => 1000], ['material' => 1000]);
        $teacher = \core_user::get_user($GLOBALS['USER']->id);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $groups = [];
        foreach (range(1, 3) as $i) {
            $student = $generator->create_and_enrol($course, 'student');
            $groups[$i] = $generator->create_group(['courseid' => $course->id]);
            $generator->create_group_member(['groupid' => $groups[$i]->id, 'userid' => $student->id]);
        }
        $this->create_assign($course, 'Lab report');
        $this->create_assign($course, 'Essay', ['assignsubmission_file_maxfiles' => 1, 'maxattempts' => 3,
            'attemptreopenmethod' => 'manual']);
        $this->create_assign($course, 'Group project', ['assignsubmission_file_maxfiles' => 2, 'teamsubmission' => 1]);
        $this->create_assign($course, 'Journal', ['maxattempts' => -1, 'attemptreopenmethod' => 'manual']);
        $this->create_assign($course, 'Online text', ['assignsubmission_file_enabled' => 0,
            'assignsubmission_onlinetext_enabled' => 1]);
        // Reopening "until pass" without a grade to pass never reopens: one attempt, whatever the maximum.
        $this->create_assign($course, 'Until pass', ['maxattempts' => 3, 'attemptreopenmethod' => 'untilpass']);
        $this->setUser($teacher);

        $html = $this->render((int)$course->id);
        $section = self::potential_section($html);
        $this->assertNotNull($section);
        $this->assertStringContainsString($str('heading'), $section);
        $activities = strpos($html, 'block_coursecare-overview-activities');
        $this->assertNotFalse($activities);
        $this->assertGreaterThan($activities, strpos($html, 'id="block_coursecare-potential"'));
        $technical = strpos($html, 'block_coursecare-overview-technicalsection');
        $this->assertGreaterThan(strpos($html, 'id="block_coursecare-potential"'), $technical);

        $positions = [];
        foreach (['Lab report', 'Essay', 'Group project', 'Journal'] as $name) {
            $positions[] = strpos($section, '>' . $name . '<');
        }
        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, array_values(array_unique($positions)));
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Order of the course');
        $this->assertSame('', self::potential_item($section, 'Online text'));

        $item = self::potential_item($section, 'Lab report');
        $this->assertStringContainsString('/mod/assign/view.php?id=', $item);
        $this->assertStringContainsString($str('files_participant', (object)['files' => 3, 'size' => $size($mb)]), $item);
        $this->assertStringContainsString($str('limit_activity'), $item);
        $this->assertStringContainsString($str('attempts_one'), $item);
        $this->assertStringContainsString($str('units_participants', 3), $item);
        $formula = get_string('potential:formula', 'block_coursecare', (object)['units' => 3, 'files' => 3,
            'size' => $size($mb)]);
        $expected = $str('value', (object)['size' => $size(9 * $mb), 'formula' => $formula]);
        $this->assertStringContainsString($expected, $item);

        $item = self::potential_item($section, 'Essay');
        $this->assertStringContainsString($str('files_participant_one', (object)['size' => $size($mb)]), $item);
        $this->assertStringContainsString($str('attempts', 3), $item);
        $formula = get_string('potential:formula_attempts', 'block_coursecare', (object)['units' => 3, 'attempts' => 3,
            'files' => 1, 'size' => $size($mb)]);
        $expected = $str('value', (object)['size' => $size(9 * $mb), 'formula' => $formula]);
        $this->assertStringContainsString($expected, $item);

        $item = self::potential_item($section, 'Group project');
        $this->assertStringContainsString($str('files_group', (object)['files' => 2, 'size' => $size($mb)]), $item);
        $this->assertStringContainsString($str('units_groups', 3), $item);

        $item = self::potential_item($section, 'Journal');
        $this->assertStringContainsString($str('attempts_unlimited'), $item);
        $formula = get_string('potential:formula', 'block_coursecare', (object)['units' => 3, 'files' => 3,
            'size' => $size($mb)]);
        $expected = $str('unlimited', (object)['size' => $size(9 * $mb), 'formula' => $formula]);
        $this->assertStringContainsString($expected, $item);

        $item = self::potential_item($section, 'Until pass');
        $this->assertStringContainsString($str('attempts_one'), $item);
        $this->assertStringNotContainsString($str('attempts', 3), $item);

        // No total of the course: 9 + 9 + 6 MB is nowhere.
        $this->assertStringNotContainsString($size(24 * $mb), $section);

        // The limit of the course, when smaller than the one of the assignment.
        $small = $generator->create_course(['maxbytes' => 524288]);
        $generator->create_and_enrol($small, 'student');
        $this->setAdminUser();
        $this->create_assign($small, 'Poster');
        $this->setUser($generator->create_and_enrol($small, 'editingteacher'));
        $item = self::potential_item((string)self::potential_section($this->render((int)$small->id)), 'Poster');
        $this->assertStringContainsString($str('limit_course'), $item);
        $this->assertStringContainsString($str('units_participants_one'), $item);
        $expected = $str('files_participant', (object)['files' => 3, 'size' => $size(524288)]);
        $this->assertStringContainsString($expected, $item);

        // The example of the specification: 30 participants, 3 files of 10 MB, 1 attempt → 900 MB.
        $settings = ['maxfiles' => 3, 'activitybytes' => 10 * $mb, 'maxattempts' => 1, 'reopenmethod' => 'untilpass',
            'teamsubmission' => false, 'groupingid' => 0, 'preventnogroup' => false, 'participants' => range(1, 30)];
        $result = \block_coursecare\local\analysis\storage_potential::evaluate($settings, ['limits' => ['server' => 0,
            'site' => 0, 'course' => 0, 'default' => 0]]);
        $section = $this->render_potential([['name' => 'Final work', 'url' => null, 'visible' => true,
            'result' => $result]]);
        $formula = get_string('potential:formula', 'block_coursecare', (object)['units' => 30, 'files' => 3,
            'size' => $size(10 * $mb)]);
        $expected = $str('value', (object)['size' => $size(900 * $mb), 'formula' => $formula]);
        $this->assertStringContainsString($expected, $section);
        $this->assertStringContainsString('900.0', $section);
        $this->assertStringContainsString('30 × 3 × 10.0', $section);
    }

    /**
     * What the potential means and does not mean is always shown; the recorded storage only as the total of the course,
     * once, and only with a result; the calculation and its limits in a collapsed part (Feature 005, T015).
     */
    public function test_potential_meaning_and_recorded(): void {
        $this->resetAfterTest();
        $mb = 1048576;
        $str = fn(string $key, $a = null): string => s(get_string('potential:' . $key, 'block_coursecare', $a));
        $recordedstart = strstr($str('recorded', (object)['size' => 'SIZE', 'date' => 'DATE']), 'SIZE', true);
        [$course] = $this->course_with_diagnostic(['Reading' => 1000], ['material' => 1000, 'submission' => 5 * $mb]);
        $teacher = \core_user::get_user($GLOBALS['USER']->id);
        $this->setAdminUser();
        $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->create_assign($course, 'Lab report');
        $this->setUser($teacher);

        $section = self::potential_section($this->render((int)$course->id));
        foreach (['intro', 'meaning', 'scope'] as $key) {
            $this->assertStringContainsString($str($key), $section);
        }
        $this->assertSame(1, substr_count($section, $recordedstart));
        $this->assertSame(1, substr_count($section, diagnostic_line::size(5 * $mb)));
        $this->assertStringContainsString(diagnostic_line::size(6 * $mb), self::potential_item($section, 'Lab report'));
        $this->assertStringNotContainsString(diagnostic_line::size(5 * $mb), self::potential_item($section, 'Lab report'));
        $pattern = '/<details class="block_coursecare-overview-potentialhow">\s*<summary>' .
            preg_quote($str('how'), '/') . '<\/summary>/';
        $this->assertMatchesRegularExpression($pattern, $section);
        $how = ['how_formula', 'how_attempts', 'limit_dates', 'limit_ignore', 'limit_other', 'how_server', 'limit_change'];
        foreach ($how as $key) {
            $this->assertStringContainsString($str($key), $section);
        }

        // Without a result, the section is there, without the recorded storage.
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->create_assign($course, 'Lab report');
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $section = self::potential_section($this->render((int)$course->id));
        $this->assertNotNull($section);
        $this->assertStringContainsString($str('meaning'), $section);
        $this->assertStringNotContainsString($recordedstart, $section);
    }

    /**
     * No name of a student, group or file in the section, and no word of judgement, forecast or saving in the new
     * strings (Feature 005, T016; FR-040, SC-002, SC-004).
     */
    public function test_potential_privacy_and_tone(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student', ['firstname' => 'Zelda', 'lastname' => 'Quixote']);
        $group = $generator->create_group(['courseid' => $course->id, 'name' => 'Team Rocket']);
        $generator->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $assign = $this->create_assign($course, 'Group project', ['teamsubmission' => 1]);
        get_file_storage()->create_file_from_string(['contextid' => \context_module::instance($assign->cmid)->id,
            'component' => 'assignsubmission_file', 'filearea' => 'submission_files', 'itemid' => 1, 'filepath' => '/',
            'filename' => 'secret-notes.pdf', 'userid' => $student->id], 'x');
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));

        $section = self::potential_section($this->render((int)$course->id));
        $this->assertNotNull($section);
        foreach (['Zelda', 'Quixote', 'Team Rocket', 'secret-notes'] as $private) {
            $this->assertStringNotContainsString($private, $section);
        }
        $judgement = '/\b(must|should|waste|save|saving|too much|adequate|inadequate)\b/i';
        $this->assertDoesNotMatchRegularExpression($judgement, strip_tags($section));

        $string = [];
        include($CFG->dirroot . '/blocks/coursecare/translations/pt_br/block_coursecare.php');
        $new = array_filter($string, fn(string $key): bool => str_starts_with($key, 'potential:'), ARRAY_FILTER_USE_KEY);
        $this->assertCount(38, $new);
        $judgement = '/\b(deve|desperdício|economia|economizar|excesso|adequad\w*)\b/iu';
        $this->assertDoesNotMatchRegularExpression($judgement, implode(' ', $new));
    }

    /**
     * Instead of a number, the sentence of each case: nobody can submit, no file, cannot be calculated; hidden
     * assignments are flagged; a course without assignments with files says so (Feature 005, T019; CT-09, CT-10).
     */
    public function test_potential_states(): void {
        global $DB;
        $this->resetAfterTest();
        $str = fn(string $key, $a = null): string => s(get_string('potential:' . $key, 'block_coursecare', $a));
        $valuestart = strstr($str('value', (object)['size' => 'SIZE', 'formula' => 'F']), 'SIZE', true);
        $filesstart = strstr($str('files_participant', (object)['files' => 'N', 'size' => 'S']), 'N', true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_and_enrol($course, 'student');
        $this->create_assign($course, 'Zero files', ['assignsubmission_file_maxfiles' => 0]);
        $missing = $this->create_assign($course, 'Missing setting');
        $DB->delete_records('assign_plugin_config', ['assignment' => $missing->id, 'plugin' => 'file',
            'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
        $prohibited = $this->create_assign($course, 'Nobody');
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $prohibitedcontext = \context_module::instance($prohibited->cmid);
        role_change_permission($studentrole, $prohibitedcontext, 'mod/assign:submit', CAP_PROHIBIT);
        $this->create_assign($course, 'No groups', ['teamsubmission' => 1, 'preventsubmissionnotingroup' => 1]);
        $this->create_assign($course, 'Hidden', ['visible' => 0]);
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $section = self::potential_section($this->render((int)$course->id));

        foreach (['Zero files', 'Missing setting'] as $name) {
            $item = self::potential_item($section, $name);
            $this->assertStringContainsString($str('nofiles'), $item, $name);
            $this->assertStringNotContainsString($valuestart, $item, $name);
            $this->assertStringNotContainsString($filesstart, $item, $name);
        }
        foreach (['Nobody', 'No groups'] as $name) {
            $item = self::potential_item($section, $name);
            $this->assertStringContainsString($str('nobody'), $item, $name);
            $this->assertStringNotContainsString($valuestart, $item, $name);
            $this->assertStringNotContainsString('0 bytes', $item, $name);
        }
        $item = self::potential_item($section, 'Hidden');
        $this->assertStringContainsString($str('hidden'), $item);
        $this->assertStringContainsString($valuestart, $item);
        $this->assertStringNotContainsString($str('hidden'), self::potential_item($section, 'Nobody'));

        // A factor that cannot be determined: no number, no limit sentence.
        $result = \block_coursecare\local\analysis\storage_potential::evaluate(['maxfiles' => 3, 'activitybytes' => 0,
            'maxattempts' => 1, 'participants' => [1]], ['limits' => ['server' => 0, 'site' => 0, 'course' => 0,
            'default' => 0]]);
        $section = $this->render_potential([['name' => 'Imported', 'url' => null, 'visible' => true,
            'result' => $result]]);
        $this->assertStringContainsString($str('unknown'), $section);
        $this->assertStringNotContainsString($valuestart, $section);
        $this->assertStringNotContainsString($str('limit_server'), $section);

        // Only assignments without file submissions: the neutral sentence and no item.
        $course = $generator->create_course();
        $this->setAdminUser();
        $this->create_assign($course, 'Online text', ['assignsubmission_file_enabled' => 0,
            'assignsubmission_onlinetext_enabled' => 1]);
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $section = self::potential_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('none'), $section);
        $this->assertStringNotContainsString('block_coursecare-overview-potentialitem', $section);

        // The file submission plugin disabled on the site: Moodle offers no file submission, so nothing is listed.
        $course = $generator->create_course();
        $this->setAdminUser();
        $this->create_assign($course, 'Lab report');
        set_config('disabled', 1, 'assignsubmission_file');
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $section = self::potential_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('none'), $section);
        $this->assertSame('', self::potential_item($section, 'Lab report'));
        set_config('disabled', 0, 'assignsubmission_file');

        // No assignment at all: no section.
        $course = $generator->create_course();
        $this->setUser($generator->create_and_enrol($course, 'editingteacher'));
        $this->assertNull(self::potential_section($this->render((int)$course->id)));
    }

    /**
     * The history section of a rendered page, or null when there is none (Feature 006).
     *
     * @param string $html Page HTML.
     * @return string|null
     */
    private static function history_section(string $html): ?string {
        if (!preg_match('/<section id="block_coursecare-history".*?<\/section>/s', $html, $match)) {
            return null;
        }
        return $match[0];
    }

    /**
     * A course with a current result and history points: one per entry, days ago => values.
     *
     * @param array $points Days ago => values of block_coursecare_hist.
     * @return \stdClass The course.
     */
    private function course_with_history(array $points): \stdClass {
        [$course] = $this->course_with_diagnostic(['Reading' => 1000], ['material' => 1000]);
        /** @var \block_coursecare_generator $plugin */
        $plugin = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        foreach ($points as $daysago => $values) {
            $plugin->create_diagnostic_history(['courseid' => $course->id, 'daysago' => $daysago] + $values);
        }
        return $course;
    }

    /**
     * The history section: placed between the files of the course and the materials by activity, with the latest
     * measurement, the sentence of the change, the chart without its automatic table and, collapsed, one row per
     * measurement (Feature 006, T013).
     */
    public function test_history_section(): void {
        $this->resetAfterTest();
        $this->setTimezone('America/Sao_Paulo');
        $mb = 1048576;
        $str = fn(string $key, $a = null): string => s(get_string('history:' . $key, 'block_coursecare', $a));
        $size = fn(int $bytes): string => diagnostic_line::size($bytes);
        $course = $this->course_with_history([2 => ['materialbytes' => 120 * $mb], 1 => ['materialbytes' => 150 * $mb],
            0 => ['materialbytes' => 140 * $mb]]);

        $html = $this->render((int)$course->id);
        $section = self::history_section($html);
        $this->assertNotNull($section);
        $position = strpos($html, 'id="block_coursecare-history"');
        $this->assertGreaterThan(strpos($html, 'block_coursecare-overview-grid'), $position);
        $this->assertLessThan(strpos($html, 'block_coursecare-overview-activities'), $position);
        foreach (['heading', 'intro', 'meaning'] as $key) {
            $this->assertStringContainsString($str($key), $section);
        }
        $points = (new \block_coursecare\local\collector\diagnostic_store())->get_history((int)$course->id, 0);
        $date = fn(\stdClass $point): string => userdate((int)$point->timecompleted, get_string('strftimedate', 'langconfig'));
        $latest = userdate((int)$points[2]->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
        $this->assertStringContainsString($str('latest', $latest), $section);
        $expected = $str('decreased', (object)['delta' => $size(10 * $mb), 'date' => $date($points[1]),
            'from' => $size(150 * $mb), 'to' => $size(140 * $mb)]);
        $this->assertStringContainsString($expected, $section);
        $this->assertStringContainsString('chart-area', $section);
        $this->assertStringNotContainsString('chart-table', $section);
        $pattern = '/<details class="block_coursecare-overview-historydata">\s*<summary>' . preg_quote($str('data'), '/') .
            '<\/summary>/';
        $this->assertMatchesRegularExpression($pattern, $section);
        $this->assertSame(3, substr_count($section, '<th scope="row">'));
        // The caption is for screen readers only (Moodle 4.5 uses Bootstrap 4: sr-only).
        $this->assertStringContainsString('<caption class="sr-only">', $section);
        $this->assertLessThan(strpos($section, $date($points[0])), strpos($section, $date($points[2])));

        // An increase and no change.
        $course = $this->course_with_history([1 => ['materialbytes' => 100], 0 => ['materialbytes' => 300]]);
        $section = self::history_section($this->render((int)$course->id));
        $increased = $str('increased', (object)['delta' => 'X', 'date' => 'D', 'from' => 'F', 'to' => 'T']);
        $this->assertStringContainsString(substr($increased, 0, 30), $section);
        $course = $this->course_with_history([1 => ['materialbytes' => 100], 0 => ['materialbytes' => 100]]);
        $section = self::history_section($this->render((int)$course->id));
        $this->assertStringContainsString(substr($str('unchanged', (object)['date' => 'D', 'to' => 'T']), 0, 30), $section);
    }

    /**
     * A short or broken history: no section without points; one point without a chart; a gap without values; a
     * partial point marked; a change of method not compared; no word of judgement (Feature 006, T019).
     */
    public function test_history_states(): void {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $this->setTimezone('America/Sao_Paulo');
        $str = fn(string $key, $a = null): string => s(get_string('history:' . $key, 'block_coursecare', $a));

        [$course] = $this->course_with_diagnostic(['Reading' => 1000], ['material' => 1000]);
        $this->assertNull(self::history_section($this->render((int)$course->id)));

        $course = $this->course_with_history([0 => ['materialbytes' => 100]]);
        $section = self::history_section($this->render((int)$course->id));
        $this->assertStringContainsString($str('first'), $section);
        $this->assertStringNotContainsString('chart-area', $section);

        // A gap of five days: no row for those days and no value in the chart data.
        $course = $this->course_with_history([6 => ['materialbytes' => 100, 'partial' => 1], 0 => ['materialbytes' => 200]]);
        $section = self::history_section($this->render((int)$course->id));
        $this->assertSame(2, substr_count($section, '<th scope="row">'));
        $this->assertSame(1, substr_count($section, $str('partial')));
        // The chart data goes to the page through its JavaScript requirements.
        $js = $PAGE->requires->get_end_code();
        $this->assertMatchesRegularExpression('/"values":\[[^\]]*null,null,null,null,null[^\]]*\]/', $js);

        // Labels of the chart: day and month within a year; with the year when the history crosses years.
        $PAGE->requires->get_end_code();
        $course = $this->course_with_history([1 => ['materialbytes' => 1], 0 => ['materialbytes' => 2]]);
        $this->render((int)$course->id);
        $today = userdate(time(), get_string('strftimedateshort', 'langconfig'));
        $this->assertStringContainsString(json_encode($today), $PAGE->requires->get_end_code());
        set_config('historyretention', 5 * 365 * DAYSECS, 'block_coursecare');
        $course = $this->course_with_history([400 => ['materialbytes' => 1], 0 => ['materialbytes' => 2]]);
        $this->render((int)$course->id);
        $today = userdate(time(), get_string('strftimedatefullshort', 'langconfig'));
        $this->assertStringContainsString(json_encode($today), $PAGE->requires->get_end_code());
        unset_config('historyretention', 'block_coursecare');

        // A failed analysis after points keeps the section (state F with the previous result).
        [$course] = $this->course_with_diagnostic(['Reading' => 1000], ['material' => 1000], ['lastattemptfailed' => 1]);
        $this->getDataGenerator()->get_plugin_generator('block_coursecare')->create_diagnostic_history(
            ['courseid' => $course->id, 'daysago' => 1, 'materialbytes' => 1000]
        );
        $this->assertNotNull(self::history_section($this->render((int)$course->id)));

        // A change of method: said, and no increase or decrease.
        $course = $this->course_with_history([1 => ['materialbytes' => 100, 'methodversion' => 0],
            0 => ['materialbytes' => 900]]);
        $section = self::history_section($this->render((int)$course->id));
        $this->assertStringContainsString(substr($str('methodchanged', 'D'), 0, 20), $section);
        $increased = $str('increased', (object)['delta' => 'X', 'date' => 'D', 'from' => 'F', 'to' => 'T']);
        $this->assertStringNotContainsString(substr($increased, 0, 30), $section);

        // A change of method earlier in the period: said, while the two latest measurements are compared.
        $course = $this->course_with_history([2 => ['materialbytes' => 100, 'methodversion' => 0],
            1 => ['materialbytes' => 900], 0 => ['materialbytes' => 950]]);
        $earlier = self::history_section($this->render((int)$course->id));
        $this->assertStringContainsString(substr($str('methodchanged', 'D'), 0, 20), $earlier);
        $this->assertStringContainsString(substr($increased, 0, 30), $earlier);

        // No judgement, cause or alarm, in English and in Brazilian Portuguese ("problem" and "improvement" appear
        // only denied, in the sentence of what a change does not mean).
        $judgement = '/\b(waste|should|must|reduce|alarm|warning)\b/i';
        $this->assertDoesNotMatchRegularExpression($judgement, strip_tags($section));
        $string = [];
        include($CFG->dirroot . '/blocks/coursecare/translations/pt_br/block_coursecare.php');
        $new = array_filter($string, fn(string $key): bool => str_starts_with($key, 'history:'), ARRAY_FILTER_USE_KEY);
        $this->assertCount(23, $new);
        $judgement = '/\b(desperdício|deve|reduz\w*|economi\w*|alerta)\b/iu';
        $this->assertDoesNotMatchRegularExpression($judgement, implode(' ', $new));
    }

    /**
     * The table of the measurements: the four categories and the unique content of each point, submissions only as the
     * total of the course, and no name of a student, file or activity (Feature 006, T022).
     */
    public function test_history_categories(): void {
        $this->resetAfterTest();
        $mb = 1048576;
        $size = fn(int $bytes): string => diagnostic_line::size($bytes);
        $course = $this->course_with_history([1 => ['materialbytes' => 10 * $mb, 'submissionbytes' => 2 * $mb],
            0 => ['materialbytes' => 12 * $mb, 'submissionbytes' => 3 * $mb, 'otherbytes' => $mb, 'unclassifiedbytes' => 5,
                'uniquebytes' => 9 * $mb]]);
        $this->getDataGenerator()->create_and_enrol($course, 'student', ['firstname' => 'Zelda', 'lastname' => 'Quixote']);
        $section = self::history_section($this->render((int)$course->id));

        $headers = [get_string('history:col_date', 'block_coursecare'), get_string('history:col_total', 'block_coursecare')];
        foreach (['material', 'submission', 'other', 'unclassified'] as $category) {
            $headers[] = get_string('overview:category:' . $category, 'block_coursecare');
        }
        $headers[] = get_string('history:col_unique', 'block_coursecare');
        preg_match_all('/<th scope="col">([^<]*)<\/th>/', $section, $matches);
        $this->assertSame(array_map('s', $headers), $matches[1]);

        preg_match('/<tbody>\s*<tr>(.*?)<\/tr>/s', $section, $row);
        preg_match_all('/<td>([^<]*)<\/td>/', $row[1], $cells);
        $expected = [$size(16 * $mb + 5), $size(12 * $mb), $size(3 * $mb), $size($mb), $size(5), $size(9 * $mb)];
        $this->assertSame(array_map('s', $expected), $cells[1]);
        $this->assertStringNotContainsString('Quixote', $section);
        $this->assertStringNotContainsString('Reading', $section);
    }
}
