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

namespace block_coursecare\collector;

use block_coursecare\local\analysis\storage_potential;
use block_coursecare\local\collector\assign_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Tests of the settings read for the potential of the assignments (Feature 005, contracts/potential-calculation.md).
 *
 * The participants and groups are compared with what Moodle itself counts (count_enrolled_users() and
 * assign::count_teams()).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\collector\assign_settings
 */
final class assign_settings_test extends \advanced_testcase {
    /**
     * An assignment with file submissions.
     *
     * @param \stdClass $course Course.
     * @param array $values Overrides of the module record.
     * @return \stdClass Module record (with cmid).
     */
    private function assign(\stdClass $course, array $values = []): \stdClass {
        return $this->getDataGenerator()->create_module('assign', $values + ['course' => $course->id,
            'assignsubmission_file_enabled' => 1, 'assignsubmission_file_maxfiles' => 3,
            'assignsubmission_file_maxsizebytes' => 10485760, 'assignsubmission_onlinetext_enabled' => 0]);
    }

    /**
     * The settings of the assignments, the limits of the course and who can submit.
     */
    public function test_settings_and_participants(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('maxbytes', 20971520, 'assignsubmission_file');
        $CFG->maxbytes = 52428800;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['maxbytes' => 31457280]);
        $files = $this->assign($course, ['maxattempts' => 3, 'attemptreopenmethod' => 'manual']);
        $text = $this->assign($course, ['assignsubmission_file_enabled' => 0,
            'assignsubmission_onlinetext_enabled' => 1]);
        $missing = $this->assign($course);
        $DB->delete_records('assign_plugin_config', ['assignment' => $missing->id, 'plugin' => 'file',
            'subtype' => 'assignsubmission', 'name' => 'maxfilesubmissions']);
        $DB->delete_records('assign_plugin_config', ['assignment' => $missing->id, 'plugin' => 'file',
            'subtype' => 'assignsubmission', 'name' => 'maxsubmissionsizebytes']);
        $prohibited = $this->assign($course);
        $local = $this->assign($course);

        $student = $generator->create_and_enrol($course, 'student');
        $suspended = $generator->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $norole = $generator->create_user();
        $generator->enrol_user($norole->id, $course->id, 0);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $prohibitedcontext = \context_module::instance($prohibited->cmid);
        role_change_permission($studentrole, $prohibitedcontext, 'mod/assign:submit', CAP_PROHIBIT);
        role_assign($studentrole, $norole->id, \context_module::instance($local->cmid));

        $cmids = [$files->cmid, $text->cmid, $missing->cmid, $prohibited->cmid, $local->cmid];
        $settings = (new assign_settings())->for_course((int)$course->id, $cmids);

        $this->assertSame(['server' => (int)get_max_upload_file_size(), 'site' => 52428800, 'course' => 31457280,
            'default' => 20971520], $settings['limits']);
        $this->assertSame([], $settings['memberships']);
        $this->assertSame([], $settings['groupings']);
        $this->assertSame([
            'enabled' => true, 'maxfiles' => 3, 'activitybytes' => 10485760, 'maxattempts' => 3,
            'reopenmethod' => 'manual', 'gradetype' => 1, 'gradepass' => 0.0, 'grademin' => 0.0, 'teamsubmission' => false,
            'groupingid' => 0, 'preventnogroup' => false, 'participants' => [(int)$student->id],
        ], $settings['assigns'][$files->cmid]);
        $this->assertFalse($settings['assigns'][$text->cmid]['enabled']);
        $this->assertNull($settings['assigns'][$missing->cmid]['maxfiles']);
        $this->assertSame(0, $settings['assigns'][$missing->cmid]['activitybytes']);
        $this->assertSame([], $settings['assigns'][$prohibited->cmid]['participants']);
        $localparticipants = $settings['assigns'][$local->cmid]['participants'];
        $this->assertEqualsCanonicalizing([(int)$student->id, (int)$norole->id], $localparticipants);
        $this->assertNotContains((int)$suspended->id, $settings['assigns'][$files->cmid]['participants']);
        $this->assertNotContains((int)$teacher->id, $settings['assigns'][$files->cmid]['participants']);

        // The grade to pass of the grade item, which decides whether "until pass" reopens.
        $DB->set_field('grade_items', 'gradepass', 50, ['itemtype' => 'mod', 'itemmodule' => 'assign',
            'iteminstance' => $files->id, 'itemnumber' => 0]);
        $nograde = $this->assign($course, ['grade' => 0]);
        $graded = (new assign_settings())->for_course((int)$course->id, [$files->cmid, $nograde->cmid]);
        $this->assertSame(50.0, $graded['assigns'][$files->cmid]['gradepass']);
        $this->assertNotContains($graded['assigns'][$nograde->cmid]['gradetype'], [1, 2]);

        // With the file submission plugin disabled on the site, Moodle offers no file submission.
        set_config('disabled', 1, 'assignsubmission_file');
        $disabled = (new assign_settings())->for_course((int)$course->id, [$files->cmid]);
        $this->assertFalse($disabled['assigns'][$files->cmid]['enabled']);
        set_config('disabled', 0, 'assignsubmission_file');

        foreach ($cmids as $cmid) {
            $expected = count_enrolled_users(\context_module::instance($cmid), 'mod/assign:submit', 0, true);
            $this->assertCount($expected, $settings['assigns'][$cmid]['participants'], "cmid $cmid");
        }
    }

    /**
     * The number of groups of the rule, with the data read here, is the one assign::count_teams() gives.
     */
    public function test_groups_match_count_teams(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $groups = [];
        foreach (range(1, 6) as $i) {
            $groups[$i] = $generator->create_group(['courseid' => $course->id]);
        }
        $grouping = $generator->create_grouping(['courseid' => $course->id]);
        foreach ([1, 2, 5] as $i) {
            $generator->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $groups[$i]->id]);
        }
        foreach (range(1, 6) as $i) {
            foreach (range(1, 2) as $unused) {
                $student = $generator->create_and_enrol($course, 'student');
                $generator->create_group_member(['groupid' => $groups[$i]->id, 'userid' => $student->id]);
            }
        }
        $twogroups = $generator->create_and_enrol($course, 'student');
        $generator->create_group_member(['groupid' => $groups[1]->id, 'userid' => $twogroups->id]);
        $generator->create_group_member(['groupid' => $groups[3]->id, 'userid' => $twogroups->id]);
        $generator->create_and_enrol($course, 'student');

        $empty = $generator->create_course();
        $generator->create_and_enrol($empty, 'student');

        $scenarios = [
            [$course, ['teamsubmission' => 1]],
            [$course, ['teamsubmission' => 1, 'preventsubmissionnotingroup' => 1]],
            [$course, ['teamsubmission' => 1, 'teamsubmissiongroupingid' => $grouping->id]],
            [$course, ['teamsubmission' => 1, 'teamsubmissiongroupingid' => $grouping->id,
                'preventsubmissionnotingroup' => 1]],
            [$empty, ['teamsubmission' => 1]],
            [$empty, ['teamsubmission' => 1, 'preventsubmissionnotingroup' => 1]],
        ];
        foreach ($scenarios as $index => [$scenariocourse, $values]) {
            $record = $this->assign($scenariocourse, $values);
            $settings = (new assign_settings())->for_course((int)$scenariocourse->id, [$record->cmid]);
            $result = storage_potential::evaluate($settings['assigns'][$record->cmid], $settings);
            [$cmcourse, $cm] = get_course_and_cm_from_cmid($record->cmid, 'assign');
            $assign = new \assign(\context_module::instance($cm->id), $cm, $cmcourse);
            $this->assertSame($assign->count_teams(), $result['units'], "scenario $index");
        }
    }

    /**
     * The reads do not grow with the number of assignments without anything of their own, and no submission, grade
     * or file record is read.
     */
    public function test_reads(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $reads = [];
        foreach ([5, 20] as $count) {
            $course = $generator->create_course();
            foreach (range(1, 10) as $unused) {
                $generator->create_and_enrol($course, 'student');
            }
            $cmids = [];
            foreach (range(1, $count) as $unused) {
                $cmids[] = $this->assign($course)->cmid;
            }
            $collector = new assign_settings();
            $collector->for_course((int)$course->id, $cmids);
            $before = $DB->perf_get_reads();
            $DB->set_debug(true);
            ob_start();
            $collector->for_course((int)$course->id, $cmids);
            $queries = ob_get_clean();
            $DB->set_debug(false);
            $reads[$count] = $DB->perf_get_reads() - $before;
            foreach (['files', 'assign_submission', 'assign_grades'] as $table) {
                $pattern = '/\b' . preg_quote($CFG->prefix, '/') . $table . '\b/';
                $this->assertDoesNotMatchRegularExpression($pattern, $queries);
            }
        }
        $this->assertSame($reads[5], $reads[20]);
    }

    /**
     * Feature 009: the candidates are the assignments of the course whose stored maximum of attempts is unlimited, in
     * the order of the course, read in one query.
     */
    public function test_unlimited_attempts(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $unlimited = $this->assign($course, ['maxattempts' => -1, 'attemptreopenmethod' => 'manual']);
        $this->assign($course, ['maxattempts' => 1]);
        $second = $this->assign($course, ['maxattempts' => -1, 'attemptreopenmethod' => 'untilpass']);
        $this->assign($other, ['maxattempts' => -1, 'attemptreopenmethod' => 'manual']);

        get_fast_modinfo($course->id);
        $reads = $DB->perf_get_reads();
        $this->assertSame([(int)$unlimited->cmid, (int)$second->cmid], (new assign_settings())->unlimited_attempts($course->id));
        $this->assertSame(1, $DB->perf_get_reads() - $reads);
        $this->assertSame([], (new assign_settings())->unlimited_attempts($this->getDataGenerator()->create_course()->id));
    }
}
