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
 * The "Your course in numbers" page of the CourseCare block (Feature 003, contracts/overview-page.md).
 *
 * Opened from the block with the id of the block instance. It only reads the stored diagnostic of the course of
 * that instance and the course structure; it never reads file records and changes nothing.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The login and the capability are required by overview_access::require_access(), in the order of the contract.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

use block_coursecare\local\overview_access;
use block_coursecare\output\course_overview;

$id = required_param('id', PARAM_INT);
[$course] = overview_access::require_access($id);

$PAGE->set_url(new moodle_url('/blocks/coursecare/overview.php', ['id' => $id]));
$PAGE->set_pagelayout('incourse');
$title = get_string('overview:title', 'block_coursecare');
$PAGE->set_title($title . moodle_page::TITLE_SEPARATOR . format_string(
    $course->shortname,
    true,
    ['context' => context_course::instance($course->id)]
));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]));
$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template(
    'block_coursecare/overview',
    course_overview::for_course((int)$course->id)->export_for_template($OUTPUT)
);
echo $OUTPUT->footer();
