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

namespace block_coursecare\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Tests for the CourseCare privacy provider.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The provider declares that no personal data is stored, with an existing reason string.
     */
    public function test_null_provider_reason(): void {
        $this->assertTrue(is_subclass_of(provider::class, null_provider::class));

        $reason = provider::get_reason();
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(get_string_manager()->string_exists($reason, 'block_coursecare'));
    }
}
