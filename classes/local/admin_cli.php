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

namespace block_coursecare\local;

use block_coursecare\local\analysis\diagnostic_result;
use block_coursecare\local\analysis\state_resolver;
use block_coursecare\local\collector\diagnostic_store;
use block_coursecare\local\collector\eligibility;
use block_coursecare\task\analyse_course;

/**
 * Operations of the administrator command line tool cli/diagnostics.php (contracts/admin-cli.md).
 *
 * Every operation returns an exit code and data; the script only parses options and prints. No file, activity or
 * user name is ever returned: the data comes from the aggregate tables only.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_cli {
    /** @var int Success. */
    public const EXIT_OK = 0;
    /** @var int Invalid options. */
    public const EXIT_INVALID = 1;
    /** @var int Missing or ineligible course. */
    public const EXIT_INELIGIBLE = 2;
    /** @var int Another process is analysing the course. */
    public const EXIT_LOCKED = 3;
    /** @var int The analysis failed; the previous result was kept. */
    public const EXIT_FAILED = 4;

    /** @var int Requests registered per transaction in queue_all(). */
    public const QUEUE_CHUNK = 500;

    /** @var diagnostic_store Result store. */
    private diagnostic_store $store;

    /** @var eligibility Eligibility policy. */
    private eligibility $eligibility;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->store = new diagnostic_store();
        $this->eligibility = new eligibility();
    }

    /**
     * Validate the options of the script.
     *
     * Exactly one mode; --course is a positive integer required by --run, --show and --queue without --all;
     * --all only with --queue; --json only with --run or --show.
     *
     * @param array $options Options from cli_get_params().
     * @return int|null Course id (0 for --queue --all), or null if the options are invalid.
     */
    public static function validate_options(array $options): ?int {
        $modes = array_filter(['queue', 'run', 'show'], fn(string $mode): bool => !empty($options[$mode]));
        if (count($modes) !== 1) {
            return null;
        }
        $mode = reset($modes);
        $all = !empty($options['all']);
        $hascourse = isset($options['course']) && $options['course'] !== false && $options['course'] !== '';
        if (($all && $mode !== 'queue') || (!empty($options['json']) && $mode === 'queue') || ($all && $hascourse)) {
            return null;
        }
        if ($all) {
            return 0;
        }
        if (!$hascourse) {
            return null;
        }
        $courseid = clean_param($options['course'], PARAM_INT);
        return ((string)$courseid === (string)$options['course'] && $courseid > 0) ? $courseid : null;
    }

    /**
     * Request the analysis of one eligible course.
     *
     * @param int $courseid Course id.
     * @return array{0: int, 1: string} Exit code and message.
     */
    public function queue_course(int $courseid): array {
        if (!$this->eligibility->is_eligible($courseid)) {
            return [self::EXIT_INELIGIBLE, get_string('cli:ineligible', 'block_coursecare', $courseid)];
        }
        if (!analyse_course::request($courseid, time())) {
            return [self::EXIT_OK, get_string('cli:alreadypending', 'block_coursecare', $courseid)];
        }
        return [self::EXIT_OK, get_string('cli:queued', 'block_coursecare', $courseid)];
    }

    /**
     * Request the analysis of every eligible course without a pending request, in chunks.
     *
     * @return array{0: int, 1: string} Exit code and message.
     */
    public function queue_all(): array {
        $queued = 0;
        $now = time();
        $candidates = $this->store->get_request_candidates($this->eligibility, 0);
        foreach (array_chunk($candidates, self::QUEUE_CHUNK) as $chunk) {
            $this->store->in_transaction(function () use ($chunk, $now, &$queued): void {
                foreach ($chunk as $candidate) {
                    if (analyse_course::request((int)$candidate->courseid, $now)) {
                        $queued++;
                    }
                }
            });
        }
        return [self::EXIT_OK, get_string('cli:queuedall', 'block_coursecare', $queued)];
    }

    /**
     * Analyse one course in this process, with the same lock as the task.
     *
     * @param int $courseid Course id.
     * @return array{0: int, 1: array} Exit code and data (courseid, outcome, failure, seconds, peakmemory).
     */
    public function run_course(int $courseid): array {
        $start = microtime(true);
        $analysis = new course_analysis();
        $outcome = $analysis->run($courseid, true);
        $data = [
            'courseid' => $courseid,
            'outcome' => $outcome,
            'failure' => $analysis->get_last_failure(),
            'seconds' => round(microtime(true) - $start, 3),
            'peakmemory' => memory_get_peak_usage(),
        ];
        $code = match ($outcome) {
            course_analysis::INELIGIBLE => self::EXIT_INELIGIBLE,
            course_analysis::LOCKED => self::EXIT_LOCKED,
            course_analysis::FAILED => self::EXIT_FAILED,
            default => self::EXIT_OK,
        };
        return [$code, $data];
    }

    /**
     * Current result of one course, with every indicator, category and limitation.
     *
     * @param int $courseid Course id.
     * @return array{0: int, 1: array} Exit code and data.
     */
    public function show_course(int $courseid): array {
        if (!$this->eligibility->is_eligible($courseid)) {
            return [self::EXIT_INELIGIBLE, ['courseid' => $courseid]];
        }
        $record = $this->store->get_summary($courseid);
        $staleness = (int)get_config('block_coursecare', 'stalenessthreshold');
        $state = state_resolver::resolve($record, time(), $staleness > 0 ? $staleness : 3 * DAYSECS);
        $data = [
            'courseid' => $courseid,
            'state' => $state->state,
            'partial' => $state->partial,
            'requeststatus' => $record ? (int)$record->requeststatus : null,
            'timecompleted' => $record && $record->timecompleted !== null ? (int)$record->timecompleted : null,
            'timeattempted' => $record && $record->timeattempted !== null ? (int)$record->timeattempted : null,
            'lastattemptfailed' => $record ? (int)$record->lastattemptfailed : null,
            'failurecount' => $record ? (int)$record->failurecount : null,
            'failurereason' => $record ? $record->failurereason : null,
            'summary' => [],
            'categories' => [],
            'activities' => [],
            'largelinks' => null,
        ];
        if ($record) {
            foreach (diagnostic_result::SUMMARY_FIELDS as $field) {
                $data['summary'][$field] = $record->$field === null ? null : (int)$record->$field;
            }
            foreach ($this->store->get_categories((int)$record->id) as $category => $row) {
                $data['categories'][$category] = [];
                foreach (diagnostic_result::CATEGORY_FIELDS as $field) {
                    $data['categories'][$category][$field] = (int)$row->$field;
                }
            }
            // Activities by cmid only: no activity name is shown.
            foreach ($this->store->get_activities((int)$record->id) as $cmid => $row) {
                foreach (diagnostic_result::ACTIVITY_FIELDS as $field) {
                    $data['activities'][$cmid][$field] = (int)$row->$field;
                }
            }
            // Only the number of links: the files themselves are never listed here.
            $data['largelinks'] = $this->store->count_large_links((int)$record->id);
        }
        return [self::EXIT_OK, $data];
    }

    /**
     * Plain text rendering of show_course() data: one "key: value" line per value, sizes also in readable units.
     *
     * The keys are the data identifiers of the JSON output, not interface sentences.
     *
     * @param array $data Data from show_course().
     * @return string
     */
    public static function format_show(array $data): string {
        $lines = [get_string('cli:showheading', 'block_coursecare', $data['courseid'])];
        $flat = [];
        foreach ($data as $key => $value) {
            if ($key === 'summary') {
                foreach ($value as $field => $fieldvalue) {
                    $flat[$field] = $fieldvalue;
                }
            } else if ($key === 'categories') {
                foreach ($value as $category => $row) {
                    foreach ($row as $field => $fieldvalue) {
                        $flat["{$category}.{$field}"] = $fieldvalue;
                    }
                }
            } else if ($key === 'activities') {
                foreach ($value as $cmid => $row) {
                    foreach ($row as $field => $fieldvalue) {
                        $flat["activity.{$cmid}.{$field}"] = $fieldvalue;
                    }
                }
            } else {
                $flat[$key] = $value;
            }
        }
        foreach ($flat as $key => $value) {
            if ($value === null) {
                $value = '-';
            } else if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } else if (is_int($value) && substr($key, -5) === 'bytes') {
                $value = $value . ' (' . display_size($value) . ')';
            } else if (is_int($value) && substr($key, 0, 4) === 'time') {
                $value = $value . ' (' . userdate($value, '%Y-%m-%d %H:%M') . ')';
            }
            $lines[] = "  {$key}: {$value}";
        }
        return implode(PHP_EOL, $lines);
    }
}
