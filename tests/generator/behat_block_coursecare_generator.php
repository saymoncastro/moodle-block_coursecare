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
 * Behat data generator of the CourseCare block.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_coursecare_generator extends behat_generator_base {
    /**
     * Entities that can be created in Behat with "the following "block_coursecare > diagnostics" exist".
     *
     * Columns: course (short name), logicalbytes, filecount, timecompletedago (seconds), lastattemptfailed,
     * requeststatus.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'diagnostics' => [
                'singular' => 'diagnostic',
                'datagenerator' => 'diagnostic',
                'required' => ['course'],
                'switchids' => ['course' => 'courseid'],
            ],
        ];
    }
}
