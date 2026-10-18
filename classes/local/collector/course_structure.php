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

namespace block_coursecare\local\collector;

/**
 * Current activities of a course, as the current user sees them (contracts/overview-data.md).
 *
 * Read from the course module cache of the core: no query to the files table and nothing of the diagnostic. The
 * names and the visibility are those of now, which may differ from the time of the analysis.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_structure {
    /**
     * Activities of the course, keyed by course module id.
     *
     * @param int $courseid Course id.
     * @return array<int, \stdClass> Rows with name (formatted), url (null when the activity has no page, such as a
     *     text and media area) and uservisible (for the current user).
     */
    public function get_activities(int $courseid): array {
        $activities = [];
        foreach (get_fast_modinfo($courseid)->get_cms() as $cm) {
            $activities[(int)$cm->id] = (object)[
                'name' => $cm->get_formatted_name(),
                'url' => $cm->url ? $cm->url->out(false) : null,
                'uservisible' => (bool)$cm->uservisible,
            ];
        }
        return $activities;
    }
}
