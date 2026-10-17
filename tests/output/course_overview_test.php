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
}
