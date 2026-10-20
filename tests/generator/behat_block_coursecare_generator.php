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
     * requeststatus and any other field of block_coursecare_diag.
     *
     * Child rows of an existing diagnostic:
     * - "block_coursecare > diagnostic categories": course, category, filecount, logicalbytes, uniquecount, uniquebytes;
     * - "block_coursecare > diagnostic activities": course, activity (idnumber; omit it for the course level) and fields
     *   of block_coursecare_act such as materialbytes;
     * - "block_coursecare > diagnostic large links": course, activity (idnumber; omit it for the course level), filesize;
     * - "block_coursecare > diagnostic suggestions" (Feature 009): course, activity (idnumber of an assignment found by
     *   the analysis as allowing new attempts without a maximum).
     *
     * History points (Feature 006): "block_coursecare > diagnostic history points": course, daysago and fields of
     * block_coursecare_hist such as logicalbytes, materialbytes, submissionbytes, methodversion or partial.
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
            'diagnostic categories' => [
                'singular' => 'diagnostic category',
                'datagenerator' => 'diagnostic_category',
                'required' => ['course', 'category'],
                'switchids' => ['course' => 'courseid'],
            ],
            'diagnostic activities' => [
                'singular' => 'diagnostic activity',
                'datagenerator' => 'diagnostic_activity',
                'required' => ['course'],
                'switchids' => ['course' => 'courseid', 'activity' => 'cmid'],
            ],
            'diagnostic history points' => [
                'singular' => 'diagnostic history point',
                'datagenerator' => 'diagnostic_history',
                'required' => ['course'],
                'switchids' => ['course' => 'courseid'],
            ],
            'diagnostic suggestions' => [
                'singular' => 'diagnostic suggestion',
                'datagenerator' => 'diagnostic_suggestion',
                'required' => ['course', 'activity'],
                'switchids' => ['course' => 'courseid', 'activity' => 'cmid'],
            ],
            'diagnostic large links' => [
                'singular' => 'diagnostic large link',
                'datagenerator' => 'diagnostic_largelink',
                'required' => ['course', 'filesize'],
                'switchids' => ['course' => 'courseid', 'activity' => 'cmid'],
            ],
        ];
    }
}
