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

namespace block_coursecare\task;

use block_coursecare\local\course_analysis;

/**
 * Ad hoc task that analyses the file storage of one course.
 *
 * Custom data holds the course id only (core writes it to the task log). The log lists only the course id,
 * the outcome, the duration, the peak memory and a short failure code: never file, activity or user names.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analyse_course extends \core\task\adhoc_task {
    /**
     * Build the task for a course.
     *
     * @param int $courseid Course id.
     * @return self
     */
    public static function instance(int $courseid): self {
        $task = new self();
        $task->set_component('block_coursecare');
        $task->set_custom_data(['courseid' => $courseid]);
        return $task;
    }

    /**
     * Task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:analysecourse', 'block_coursecare');
    }

    /**
     * At most two analyses run at the same time by default, leaving cron runners for other components.
     *
     * @return int
     */
    protected function get_default_concurrency_limit(): int {
        return 2;
    }

    /**
     * Analyse the course. A failure is recorded in the diagnostic and never thrown (research R10).
     */
    public function execute() {
        $data = $this->get_custom_data();
        $courseid = (int)($data->courseid ?? 0);
        if ($courseid <= 0) {
            return;
        }
        $start = microtime(true);
        $analysis = new course_analysis();
        $outcome = $analysis->run($courseid);
        $failure = $analysis->get_last_failure();
        mtrace(sprintf(
            'block_coursecare: course %d, outcome %s%s, %.2f s, peak memory %s',
            $courseid,
            $outcome,
            $failure !== null ? " ({$failure})" : '',
            microtime(true) - $start,
            display_size(memory_get_peak_usage())
        ));
    }
}
