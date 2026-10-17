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
        $this->assertStringContainsString(display_size($total), $html);
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
        $partial = s(get_string('overview:partial', 'block_coursecare', (object)['size' => display_size(120),
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
            ['overview:unique', display_size(800)],
            ['overview:unique_help', null],
            ['overview:references', null],
            ['overview:references_help', null],
            ['overview:references_internal', (object)['count' => 3, 'size' => display_size(900)]],
            ['overview:references_external', (object)['count' => 1, 'size' => display_size(5000)]],
            ['overview:references_undetermined', 4],
            ['overview:copies', null],
            ['overview:copies_help', null],
            ['overview:copies_backup', (object)['count' => 1, 'size' => display_size(4000)]],
            ['overview:copies_unique', display_size(4000)],
            ['overview:threshold', display_size(52428800)],
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
        $this->assertStringContainsString(display_size(2048), $html);
        $this->assertStringNotContainsString('Submissions only', $html);
        foreach ([$perassign, $perquiet] as $values) {
            foreach (['logicalbytes', 'submissionbytes', 'otherbytes', 'largebytes'] as $field) {
                if (!empty($values[$field])) {
                    // A whole number only: "6.0 MB" must not match inside the course total "16.0 MB".
                    $this->assertDoesNotMatchRegularExpression('/(?<![\d.])' . preg_quote(display_size($values[$field]), '/') .
                        '/u', $html, $field);
                }
            }
        }
        $this->assertDoesNotMatchRegularExpression('/\b37\b/', strip_tags($html));
        // The course totals of submissions are shown, as the decision of the product owner allows.
        $this->assertStringContainsString(display_size(12 * $mb), $html);

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
            'threshold' => display_size(50 * $mb), 'count' => 2, 'size' => display_size(120 * $mb)])), $html);
        $this->assertStringContainsString(s(get_string('overview:large_help', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:large_materials', 'block_coursecare')), $html);
        $this->assertStringContainsString(s(get_string('overview:large_count', 'block_coursecare', 1)), $html);
        $url = s((new \moodle_url('/mod/resource/view.php', ['id' => $cms['Lab video']->cmid]))->out(false));
        $this->assertSame(2, substr_count($html, 'href="' . $url . '"'));
        $this->assertStringNotContainsString(s(get_string('overview:large_truncated', 'block_coursecare')), $html);

        // With truncated links, the notice appears.
        $DB->set_field('block_coursecare_diag', 'largelinkstruncated', 1, ['courseid' => $course->id]);
        $html = $this->render((int)$course->id);
        $this->assertStringContainsString(s(get_string('overview:large_truncated', 'block_coursecare')), $html);
    }
}
