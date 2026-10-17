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

namespace block_coursecare;

use block_coursecare\local\overview_access;

/**
 * Access to the overview page (contracts/overview-page.md, "Endereço e acesso"; FR-010; CT-12, CT-13).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\overview_access
 */
final class overview_access_test extends \advanced_testcase {
    /**
     * A course with a CourseCare block.
     *
     * @return array{0: \stdClass, 1: \stdClass} The course and the block instance.
     */
    private function course_with_block(): array {
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance($course->id)->id,
            'pagetypepattern' => 'course-view-*',
        ]);
        return [$course, $instance];
    }

    /**
     * Check the access with a fresh page, as one request would.
     *
     * @param int $instanceid Block instance id.
     * @return array
     */
    private static function access(int $instanceid): array {
        global $PAGE;
        $PAGE = new \moodle_page();
        return overview_access::require_access($instanceid);
    }

    /**
     * Whether the access is refused with a Moodle exception, and with which class.
     *
     * @param int $instanceid Block instance id.
     * @return string|null Short class name of the exception, or null if access was granted.
     */
    private static function refusal(int $instanceid): ?string {
        try {
            self::access($instanceid);
        } catch (\moodle_exception $e) {
            return (new \ReflectionClass($e))->getShortName();
        }
        return null;
    }

    /**
     * Editing teachers and managers of the course are allowed; the course comes from the instance.
     */
    public function test_allowed(): void {
        $this->resetAfterTest();
        [$course, $instance] = $this->course_with_block();
        foreach (['editingteacher', 'manager'] as $role) {
            $this->setUser($this->getDataGenerator()->create_and_enrol($course, $role));
            [$accessed, $context] = self::access((int)$instance->id);
            $this->assertSame((int)$course->id, (int)$accessed->id, $role);
            $this->assertSame((int)$instance->id, (int)$context->instanceid, $role);
        }
    }

    /**
     * Students, non-editing teachers, teachers of another course, guests and visitors are refused (CT-12).
     */
    public function test_refused_users(): void {
        $this->resetAfterTest();
        [$course, $instance] = $this->course_with_block();
        [$other] = $this->course_with_block();
        $id = (int)$instance->id;

        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'student'));
        $this->assertSame('required_capability_exception', self::refusal($id));
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'teacher'));
        $this->assertSame('required_capability_exception', self::refusal($id));
        $this->setUser($this->getDataGenerator()->create_and_enrol($other, 'editingteacher'));
        $this->assertNotNull(self::refusal($id));
        $this->setGuestUser();
        $this->assertNotNull(self::refusal($id));
        // Not logged in: the core redirects to the login page (an exception in PHPUnit).
        $this->setUser(0);
        $this->assertNotNull(self::refusal($id));
    }

    /**
     * A prohibition of the view capability on the block instance itself is respected.
     */
    public function test_refused_by_override_on_the_instance(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $instance] = $this->course_with_block();
        $this->setUser($this->getDataGenerator()->create_and_enrol($course, 'editingteacher'));
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        assign_capability('block/coursecare:view', CAP_PROHIBIT, $roleid, \context_block::instance($instance->id)->id);
        $this->assertSame('required_capability_exception', self::refusal((int)$instance->id));
    }

    /**
     * Ids that are not a CourseCare instance in a course are refused the same way, without saying whether the
     * instance exists (CT-13).
     */
    public function test_refused_instances(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        [$course] = $this->course_with_block();

        // No instance with this id; an instance of another block.
        $this->assertSame('dml_missing_record_exception', self::refusal(999999));
        $html = $this->getDataGenerator()->create_block('html', [
            'parentcontextid' => \context_course::instance($course->id)->id,
        ]);
        $this->assertSame('dml_missing_record_exception', self::refusal((int)$html->id));

        // A CourseCare instance on the front page (site course).
        $front = $this->getDataGenerator()->create_block('coursecare', [
            'parentcontextid' => \context_course::instance(SITEID)->id,
        ]);
        $this->assertSame('dml_missing_record_exception', self::refusal((int)$front->id));
    }
    /**
     * A CourseCare instance whose parent is not a course (an activity, a category or a user) or whose parent context
     * no longer exists is refused with the same exception as a missing instance (T033).
     */
    public function test_refused_parent_contexts(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $parents = [
            'activity' => \context_module::instance($page->cmid)->id,
            'category' => \context_coursecat::instance($course->category)->id,
            'user' => \context_user::instance($this->getDataGenerator()->create_user()->id)->id,
        ];
        foreach ($parents as $name => $contextid) {
            $instance = $this->getDataGenerator()->create_block('coursecare', ['parentcontextid' => $contextid]);
            $this->assertSame('dml_missing_record_exception', self::refusal((int)$instance->id), $name);
        }

        [, $instance] = $this->course_with_block();
        $DB->set_field('block_instances', 'parentcontextid', 987654321, ['id' => $instance->id]);
        $this->assertSame('dml_missing_record_exception', self::refusal((int)$instance->id), 'missing parent');
    }
}
