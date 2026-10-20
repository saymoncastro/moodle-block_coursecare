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

namespace block_coursecare\analysis;

use block_coursecare\local\analysis\storage_potential;

/**
 * Pure tests of the theoretical potential of an assignment (Feature 005, contracts/potential-calculation.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\storage_potential
 */
final class storage_potential_test extends \basic_testcase {
    /** @var int One megabyte. */
    private const MB = 1048576;

    /**
     * Settings of an assignment: 30 participants, 3 files of 10 MB, 1 attempt, no group submission.
     *
     * @param array $values Overrides.
     * @return array
     */
    private static function settings(array $values = []): array {
        return $values + [
            'enabled' => true, 'maxfiles' => 3, 'activitybytes' => 10 * self::MB, 'maxattempts' => 1,
            'reopenmethod' => 'untilpass', 'teamsubmission' => false, 'groupingid' => 0, 'preventnogroup' => false,
            'participants' => range(1, 30),
        ];
    }

    /**
     * Settings of the course: every limit above the one of the assignment, no groups.
     *
     * @param array $limits Overrides of the limits.
     * @param array $values Overrides of memberships and groupings.
     * @return array
     */
    private static function course(array $limits = [], array $values = []): array {
        return $values + [
            'limits' => $limits + ['server' => 100 * self::MB, 'site' => 0, 'course' => 0, 'default' => 0],
            'memberships' => [], 'groupings' => [],
        ];
    }

    /**
     * The grade item of an assignment.
     *
     * @param int $type Grade type (0 none, 1 value, 2 scale, 3 text).
     * @param float|null $pass Grade to pass.
     * @param float $min Minimum grade.
     * @return array
     */
    private static function pass(int $type, ?float $pass, float $min): array {
        return ['gradetype' => $type, 'gradepass' => $pass, 'grademin' => $min];
    }

    /**
     * Group submission for participants 1 to 12 in six groups of two.
     *
     * @return array userid => group ids
     */
    private static function six_groups(): array {
        $memberships = [];
        foreach (range(1, 12) as $userid) {
            $memberships[$userid] = [100 + intdiv($userid - 1, 2)];
        }
        return $memberships;
    }

    /**
     * Cases of the contract, with the expected fields.
     *
     * @return array
     */
    public static function cases_provider(): array {
        $mb = self::MB;
        $team = ['teamsubmission' => true, 'participants' => range(1, 12)];
        return [
            'default case' => [self::settings(), self::course(),
                ['status' => 'potential', 'unit' => 'participant', 'units' => 30, 'files' => 3, 'filebytes' => 10 * $mb,
                    'limitsource' => 'activity', 'attempts' => 1, 'perattempt' => 900 * $mb, 'potential' => 900 * $mb]],
            'site default for assignments' => [self::settings(['activitybytes' => 0]),
                self::course(['default' => 20 * $mb]),
                ['filebytes' => 20 * $mb, 'limitsource' => 'default', 'potential' => 1800 * $mb]],
            'smaller course limit' => [self::settings(['activitybytes' => 50 * $mb]), self::course(['course' => 10 * $mb]),
                ['filebytes' => 10 * $mb, 'limitsource' => 'course']],
            'smaller site limit' => [self::settings(), self::course(['site' => 5 * $mb, 'course' => 8 * $mb]),
                ['filebytes' => 5 * $mb, 'limitsource' => 'site']],
            'smaller server limit' => [self::settings(), self::course(['server' => 2 * $mb, 'site' => 50 * $mb]),
                ['filebytes' => 2 * $mb, 'limitsource' => 'server']],
            'tie between assignment and course' => [self::settings(), self::course(['course' => 10 * $mb]),
                ['filebytes' => 10 * $mb, 'limitsource' => 'activity']],
            'tie between default and site' => [self::settings(['activitybytes' => 0]),
                self::course(['default' => 8 * $mb, 'site' => 8 * $mb]), ['filebytes' => 8 * $mb, 'limitsource' => 'default']],
            'tie between course, site and server' => [self::settings(['activitybytes' => 0]),
                self::course(['course' => 4 * $mb, 'site' => 4 * $mb, 'server' => 4 * $mb]),
                ['filebytes' => 4 * $mb, 'limitsource' => 'course']],
            'server only' => [self::settings(['activitybytes' => 0]), self::course(),
                ['filebytes' => 100 * $mb, 'limitsource' => 'server']],
            'no limit known' => [self::settings(['activitybytes' => 0]), self::course(['server' => 0]),
                ['status' => 'unknown', 'filebytes' => 0, 'limitsource' => null, 'potential' => null, 'perattempt' => 0]],
            'three attempts, manual reopening' => [self::settings(['maxattempts' => 3, 'reopenmethod' => 'manual']),
                self::course(), ['attempts' => 3, 'perattempt' => 900 * $mb, 'potential' => 2700 * $mb]],
            'three attempts, automatic reopening' => [self::settings(['maxattempts' => 3, 'reopenmethod' => 'automatic']),
                self::course(), ['attempts' => 3]],
            'until pass with a grade to pass' => [self::settings(['maxattempts' => 3] + self::pass(1, 50.0, 0.0)),
                self::course(), ['attempts' => 3]],
            'until pass on a scale with a grade to pass' => [self::settings(['maxattempts' => 3] + self::pass(2, 2.0, 1.0)),
                self::course(), ['attempts' => 3]],
            'until pass without a grade item' => [self::settings(['maxattempts' => 3]), self::course(),
                ['attempts' => 1, 'potential' => 900 * $mb]],
            'until pass with the grade to pass at the minimum' => [
                self::settings(['maxattempts' => 3] + self::pass(1, 0.0, 0.0)), self::course(), ['attempts' => 1]],
            'until pass with an empty grade to pass' => [self::settings(['maxattempts' => 3] + self::pass(1, null, 0.0)),
                self::course(), ['attempts' => 1]],
            'until pass on a scale with a zero grade to pass' => [
                self::settings(['maxattempts' => 3] + self::pass(2, 0.0, 1.0)), self::course(), ['attempts' => 1]],
            'until pass without grading' => [self::settings(['maxattempts' => 3] + self::pass(0, 50.0, 0.0)),
                self::course(), ['attempts' => 1]],
            'until pass with unlimited attempts and no grade to pass' => [self::settings(['maxattempts' => -1]),
                self::course(), ['status' => 'potential', 'attempts' => 1, 'potential' => 900 * $mb]],
            'until pass with unlimited attempts and a grade to pass' => [
                self::settings(['maxattempts' => -1] + self::pass(1, 50.0, 0.0)), self::course(),
                ['status' => 'unlimited', 'attempts' => null]],
            'unlimited attempts' => [self::settings(['maxattempts' => -1, 'reopenmethod' => 'manual']), self::course(),
                ['status' => 'unlimited', 'attempts' => null, 'perattempt' => 900 * $mb, 'potential' => null]],
            'reopening none with a maximum of five' => [self::settings(['maxattempts' => 5, 'reopenmethod' => 'none']),
                self::course(), ['attempts' => 1, 'potential' => 900 * $mb]],
            'maximum of one with manual reopening' => [self::settings(['maxattempts' => 1, 'reopenmethod' => 'manual']),
                self::course(), ['attempts' => 1, 'potential' => 900 * $mb]],
            'attempts not determined' => [self::settings(['maxattempts' => 0, 'reopenmethod' => 'manual',
                'participants' => []]), self::course(),
                ['status' => 'unknown', 'potential' => null]],
            'no file allowed' => [self::settings(['maxfiles' => 0]), self::course(),
                ['status' => 'nofiles', 'files' => 0, 'filebytes' => 0, 'limitsource' => null, 'potential' => null]],
            'maximum of files missing' => [self::settings(['maxfiles' => null]), self::course(),
                ['status' => 'nofiles', 'potential' => null]],
            'nobody can submit' => [self::settings(['participants' => []]), self::course(),
                ['status' => 'nobody', 'units' => 0, 'potential' => null, 'perattempt' => 0]],
            'six groups' => [self::settings($team), self::course([], ['memberships' => self::six_groups()]),
                ['unit' => 'group', 'units' => 6, 'potential' => 6 * 3 * 10 * $mb]],
            'grouping with two of four groups used' => [
                self::settings(['groupingid' => 7, 'preventnogroup' => true] + $team),
                self::course([], ['memberships' => self::six_groups(), 'groupings' => [7 => [100, 101, 200, 201]]]),
                ['units' => 2]],
            'grouping and participants outside it' => [self::settings(['groupingid' => 7] + $team),
                self::course([], ['memberships' => self::six_groups(), 'groupings' => [7 => [100, 101, 200, 201]]]),
                ['units' => 3]],
            'no groups' => [self::settings($team), self::course(), ['unit' => 'group', 'units' => 1]],
            'no groups and submission outside a group prevented' => [self::settings(['preventnogroup' => true] + $team),
                self::course(), ['status' => 'nobody', 'units' => 0]],
            'participant in two groups goes to the default group' => [self::settings($team),
                self::course([], ['memberships' => [1 => [100], 2 => [100, 101], 3 => [101]]]), ['units' => 3]],
            'participant in two groups, outside a group prevented' => [self::settings(['preventnogroup' => true] + $team),
                self::course([], ['memberships' => [1 => [100], 2 => [100, 101]]]), ['units' => 1]],
            'grouping with a participant in two of its groups' => [
                self::settings(['groupingid' => 7, 'preventnogroup' => true, 'participants' => [1]] + $team),
                self::course([], ['memberships' => [1 => [100, 101]], 'groupings' => [7 => [100, 101]]]), ['units' => 2]],
            'grouping that no longer exists' => [self::settings(['groupingid' => 9] + $team),
                self::course([], ['memberships' => self::six_groups()]), ['units' => 1]],
            'grouping that no longer exists, outside a group prevented' => [
                self::settings(['groupingid' => 9, 'preventnogroup' => true] + $team),
                self::course([], ['memberships' => self::six_groups()]), ['status' => 'nobody', 'units' => 0]],
            'large values' => [
                self::settings(['participants' => range(1, 500), 'maxfiles' => 10, 'maxattempts' => 20,
                    'reopenmethod' => 'manual',
                    'activitybytes' => 2048 * $mb]),
                self::course(['server' => 4096 * $mb]),
                ['units' => 500, 'potential' => 500 * 20 * 10 * 2048 * $mb]],
        ];
    }

    /**
     * Each case gives the expected status and factors.
     *
     * @dataProvider cases_provider
     * @param array $settings Settings of the assignment.
     * @param array $course Settings of the course.
     * @param array $expected Expected fields.
     */
    public function test_cases(array $settings, array $course, array $expected): void {
        $result = storage_potential::evaluate($settings, $course);
        $this->assertSame(['status', 'unit', 'units', 'files', 'filebytes', 'limitsource', 'attempts', 'perattempt',
            'potential'], array_keys($result));
        foreach ($expected as $field => $value) {
            $this->assertSame($value, $result[$field], $field);
        }
    }

    /**
     * Large values stay exact integers (no float, no rounding).
     */
    public function test_large_values_are_exact(): void {
        $settings = self::settings(['participants' => range(1, 500), 'maxfiles' => 10, 'maxattempts' => 20,
            'reopenmethod' => 'manual', 'activitybytes' => 2048 * self::MB]);
        $result = storage_potential::evaluate($settings, self::course(['server' => 4096 * self::MB]));
        $this->assertIsInt($result['potential']);
        $this->assertSame(214748364800000, $result['potential']);
    }

    /**
     * Assignments that effectively allow new attempts without a maximum, and those that do not (Feature 009, R1).
     *
     * @return array
     */
    public static function unlimited_attempts_provider(): array {
        $unlimited = ['maxattempts' => -1];
        $team = ['teamsubmission' => true, 'participants' => range(1, 12)];
        return [
            'manual reopening' => [self::settings($unlimited + ['reopenmethod' => 'manual']), self::course(), true],
            'automatic reopening' => [self::settings($unlimited + ['reopenmethod' => 'automatic']), self::course(), true],
            'until pass with a grade to pass' => [self::settings($unlimited + self::pass(1, 50.0, 0.0)), self::course(), true],
            'until pass without a grade to pass' => [self::settings($unlimited + self::pass(1, null, 0.0)), self::course(),
                false],
            'until pass without a grade' => [self::settings($unlimited), self::course(), false],
            'reopening none (before Moodle 4.4)' => [self::settings($unlimited + ['reopenmethod' => 'none']), self::course(),
                false],
            'one attempt' => [self::settings(['maxattempts' => 1, 'reopenmethod' => 'manual']), self::course(), false],
            'five attempts' => [self::settings(['maxattempts' => 5, 'reopenmethod' => 'manual']), self::course(), false],
            'file submissions disabled' => [self::settings($unlimited + ['reopenmethod' => 'manual', 'enabled' => false]),
                self::course(), false],
            'no file can be submitted' => [self::settings($unlimited + ['reopenmethod' => 'manual', 'maxfiles' => 0]),
                self::course(), false],
            'nobody can submit' => [self::settings($unlimited + ['reopenmethod' => 'manual', 'participants' => []]),
                self::course(), false],
            'groups' => [self::settings($unlimited + ['reopenmethod' => 'manual'] + $team),
                self::course([], ['memberships' => self::six_groups()]), true],
            'limit in bytes undetermined' => [self::settings($unlimited + ['reopenmethod' => 'manual', 'activitybytes' => 0]),
                self::course(['server' => 0]), true],
        ];
    }

    /**
     * The criterion of the suggestion reads the result of evaluate(), never the field of attempts alone.
     *
     * @dataProvider unlimited_attempts_provider
     * @param array $settings Settings of the assignment.
     * @param array $course Settings of the course.
     * @param bool $expected Whether new attempts without a maximum are effectively allowed.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unlimited_attempts_provider')]
    public function test_allows_unlimited_attempts(array $settings, array $course, bool $expected): void {
        $evaluation = storage_potential::evaluate($settings, $course);
        $this->assertSame($expected, storage_potential::allows_unlimited_attempts($settings, $evaluation));
    }
}
