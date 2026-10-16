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

use block_coursecare\local\collector\diagnostic_store;
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
        $task = new static();
        $task->set_component('block_coursecare');
        $task->set_custom_data(['courseid' => $courseid]);
        return $task;
    }

    /**
     * Register a request for a course and queue its analysis, unless a request is already pending.
     *
     * The plugin's own request status prevents duplicates (research R9), so the core duplicate check is not used.
     *
     * @param int $courseid Course id.
     * @param int $now Current time.
     * @return bool Whether a task was queued.
     */
    public static function request(int $courseid, int $now): bool {
        if (!(new diagnostic_store())->request($courseid, $now)) {
            return false;
        }
        \core\task\manager::queue_adhoc_task(static::instance($courseid));
        return true;
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
     * The analysis to run. Tests override it to make one course fail inside a real cron run.
     *
     * @return course_analysis
     */
    protected function create_analysis(): course_analysis {
        return new course_analysis();
    }

    /**
     * Analyse the course.
     *
     * A failure of the analysis is recorded in the diagnostic and not thrown, so the task ends normally (research
     * R10). A database failure before the analysis or while recording the failure is thrown, and the core then
     * marks the task as failed and retries it (see course_analysis).
     */
    public function execute() {
        $data = $this->get_custom_data();
        $courseid = (int)($data->courseid ?? 0);
        if ($courseid <= 0) {
            return;
        }
        $start = microtime(true);
        $analysis = $this->create_analysis();
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
