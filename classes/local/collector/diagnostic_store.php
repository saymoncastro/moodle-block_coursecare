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

use block_coursecare\local\analysis\diagnostic_result;
use block_coursecare\local\analysis\diagnostic_state;

/**
 * Reads and writes the plugin's own diagnostic tables (data-model.md). Single access point to the results (FR-053).
 *
 * It stores aggregates only: no user id, file name, path or content hash. The only per-file rows are the large-file
 * links (file id, cmid and recorded size), which the calculator restricts to course materials (D10, at most 1,000
 * per course). Readers must check permissions before calling it and must pass the course of their own context, never
 * a request parameter (FR-041).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_store {
    /** @var int No pending request. */
    public const STATUS_NONE = diagnostic_state::REQUEST_NONE;
    /** @var int Request queued. */
    public const STATUS_QUEUED = diagnostic_state::REQUEST_QUEUED;
    /** @var int Analysis running. */
    public const STATUS_RUNNING = diagnostic_state::REQUEST_RUNNING;

    /** @var string Main table. */
    public const TABLE = 'block_coursecare_diag';
    /** @var string Child tables, keyed by diagid. */
    public const CHILD_TABLES = ['block_coursecare_cat', 'block_coursecare_act', 'block_coursecare_large'];

    /** @var int Maximum length of failurereason. */
    public const REASON_LENGTH = 30;

    /**
     * Current diagnostic record of a course: one read by the unique courseid index; never reads the files table.
     *
     * @param int $courseid Course id.
     * @return \stdClass|null
     */
    public function get_summary(int $courseid): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['courseid' => $courseid]);
        return $record ?: null;
    }

    /**
     * Category rows of a diagnostic, keyed by category (main categories and the backup and recycle bin aggregate).
     *
     * @param int $diagid Diagnostic id.
     * @return array<string, \stdClass>
     */
    public function get_categories(int $diagid): array {
        global $DB;

        $rows = [];
        foreach ($DB->get_records('block_coursecare_cat', ['diagid' => $diagid], 'category') as $record) {
            $rows[$record->category] = $record;
        }
        return $rows;
    }

    /**
     * Activity rows of a diagnostic, keyed by cmid (0 = course level).
     *
     * @param int $diagid Diagnostic id.
     * @return array<int, \stdClass>
     */
    public function get_activities(int $diagid): array {
        global $DB;

        $rows = [];
        foreach ($DB->get_records('block_coursecare_act', ['diagid' => $diagid], 'cmid') as $record) {
            $rows[(int)$record->cmid] = $record;
        }
        return $rows;
    }

    /**
     * Number of large-file links of a diagnostic.
     *
     * @param int $diagid Diagnostic id.
     * @return int
     */
    public function count_large_links(int $diagid): int {
        global $DB;

        return $DB->count_records('block_coursecare_large', ['diagid' => $diagid]);
    }

    /**
     * Run a callback in one delegated transaction, so that the other layers never open one themselves.
     *
     * @param callable $callback Work to run; an exception rolls everything back and is thrown again.
     */
    public function in_transaction(callable $callback): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            $callback();
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Register a request if the course has none pending.
     *
     * The check and the update are two statements, without a lock: two requests at the same moment (a block being
     * added while the scheduled task runs) can both be accepted and queue two tasks. That stays harmless: the first
     * task analyses the course, and the second finds a result newer than the request and ends without collecting
     * anything (course_analysis::FRESH). A lock here would cost every request for a rare, already handled case.
     *
     * @param int $courseid Course id.
     * @param int $now Current time.
     * @return bool True if the request was accepted (the caller then queues the task).
     */
    public function request(int $courseid, int $now): bool {
        global $DB;

        $record = $this->get_summary($courseid);
        if ($record) {
            if ((int)$record->requeststatus !== self::STATUS_NONE) {
                return false;
            }
            $DB->update_record(self::TABLE, (object)[
                'id' => $record->id, 'requeststatus' => self::STATUS_QUEUED, 'timerequested' => $now, 'timemodified' => $now,
            ]);
            return true;
        }
        try {
            $DB->insert_record(self::TABLE, (object)[
                'courseid' => $courseid, 'requeststatus' => self::STATUS_QUEUED, 'timerequested' => $now,
                'lastattemptfailed' => 0, 'failurecount' => 0, 'timemodified' => $now,
            ]);
        } catch (\dml_write_exception $e) {
            // Another process created the record at the same time (unique courseid): its request stands.
            return false;
        }
        return true;
    }

    /**
     * Mark the analysis of a course as running, creating the record if needed.
     *
     * @param int $courseid Course id.
     * @param int $now Current time.
     */
    public function mark_running(int $courseid, int $now): void {
        global $DB;

        $record = $this->get_summary($courseid);
        if (!$record) {
            $DB->insert_record(self::TABLE, (object)[
                'courseid' => $courseid, 'requeststatus' => self::STATUS_RUNNING, 'timerequested' => $now,
                'timestarted' => $now, 'lastattemptfailed' => 0, 'failurecount' => 0, 'timemodified' => $now,
            ]);
            return;
        }
        $DB->update_record(self::TABLE, (object)[
            'id' => $record->id, 'requeststatus' => self::STATUS_RUNNING, 'timestarted' => $now, 'timemodified' => $now,
        ]);
    }

    /**
     * Replace the current result of a course, atomically: until the transaction commits, the previous result stays.
     *
     * @param int $courseid Course id.
     * @param diagnostic_result $result New result.
     * @param int $now Current time.
     */
    public function save_result(int $courseid, diagnostic_result $result, int $now): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            $record = $this->get_summary($courseid);
            if (!$record) {
                $record = (object)['courseid' => $courseid, 'timemodified' => $now];
                $record->id = $DB->insert_record(self::TABLE, $record);
            }
            foreach (self::CHILD_TABLES as $table) {
                $DB->delete_records($table, ['diagid' => $record->id]);
            }
            $rows = [];
            foreach ($result->categories as $category => $values) {
                $rows[] = ['diagid' => $record->id, 'category' => $category] + $values;
            }
            $DB->insert_records('block_coursecare_cat', $rows);
            $rows = [];
            foreach ($result->activities as $cmid => $values) {
                $rows[] = ['diagid' => $record->id, 'cmid' => $cmid] + $values;
            }
            $DB->insert_records('block_coursecare_act', $rows);
            $rows = [];
            foreach ($result->largelinks as $link) {
                $rows[] = ['diagid' => $record->id] + $link;
            }
            $DB->insert_records('block_coursecare_large', $rows);

            $update = (object)($result->summary + [
                'id' => $record->id,
                'requeststatus' => self::STATUS_NONE,
                'timestarted' => null,
                'timeattempted' => $now,
                'timecompleted' => $now,
                'lastattemptfailed' => 0,
                'failurecount' => 0,
                'failurereason' => null,
                'timemodified' => $now,
            ]);
            $DB->update_record(self::TABLE, $update);
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Record a failed attempt, keeping the previous result.
     *
     * @param int $courseid Course id.
     * @param string $reason Short failure code, never a free message; truncated to 30 characters.
     * @param int $now Current time.
     */
    public function save_failure(int $courseid, string $reason, int $now): void {
        global $DB;

        $reason = \core_text::substr($reason, 0, self::REASON_LENGTH);
        $record = $this->get_summary($courseid);
        if (!$record) {
            $DB->insert_record(self::TABLE, (object)[
                'courseid' => $courseid, 'requeststatus' => self::STATUS_NONE, 'timeattempted' => $now,
                'lastattemptfailed' => 1, 'failurecount' => 1, 'failurereason' => $reason, 'timemodified' => $now,
            ]);
            return;
        }
        $DB->update_record(self::TABLE, (object)[
            'id' => $record->id,
            'requeststatus' => self::STATUS_NONE,
            'timestarted' => null,
            'timeattempted' => $now,
            'lastattemptfailed' => 1,
            'failurecount' => (int)$record->failurecount + 1,
            'failurereason' => $reason,
            'timemodified' => $now,
        ]);
    }

    /**
     * Courses that have a diagnostic but are no longer eligible (deleted course or no block instance).
     *
     * @param eligibility $eligibility Eligibility policy.
     * @param int $limit Maximum number of courses.
     * @return int[]
     */
    public function get_orphan_courseids(eligibility $eligibility, int $limit): array {
        global $DB;

        [$eligiblesql, $params] = $eligibility->eligible_courses_sql('orph');
        $sql = "SELECT d.courseid
                  FROM {" . self::TABLE . "} d
             LEFT JOIN ({$eligiblesql}) e ON e.courseid = d.courseid
                 WHERE e.courseid IS NULL
              ORDER BY d.courseid";
        return array_map('intval', $DB->get_fieldset_sql($sql, $params, 0, $limit));
    }

    /**
     * Recover requests whose process died: running too long becomes a failure, queued too long is released.
     *
     * @param int $now Current time.
     * @param int $runningtimeout Seconds after which a running analysis is considered interrupted.
     * @param int $queuedtimeout Seconds after which a queued request is considered lost.
     * @param string $reason Failure code for interrupted analyses.
     * @return array{interrupted: int, released: int}
     */
    public function recover_stuck(int $now, int $runningtimeout, int $queuedtimeout, string $reason): array {
        global $DB;

        $running = $DB->get_fieldset_select(
            self::TABLE,
            'courseid',
            'requeststatus = :running AND timestarted < :cutoff',
            ['running' => self::STATUS_RUNNING, 'cutoff' => $now - $runningtimeout]
        );
        foreach ($running as $courseid) {
            $this->save_failure((int)$courseid, $reason, $now);
        }
        $params = ['queued' => self::STATUS_QUEUED, 'cutoff' => $now - $queuedtimeout];
        $released = $DB->count_records_select(self::TABLE, 'requeststatus = :queued AND timerequested < :cutoff', $params);
        $DB->set_field_select(
            self::TABLE,
            'requeststatus',
            self::STATUS_NONE,
            'requeststatus = :queued AND timerequested < :cutoff',
            $params
        );
        return ['interrupted' => count($running), 'released' => $released];
    }

    /**
     * Eligible courses without a pending request, the longest without an attempt first (never attempted first).
     *
     * Each row has courseid and the diagnostic fields used by due_policy (null fields if the course has no record).
     *
     * @param eligibility $eligibility Eligibility policy.
     * @param int $limit Maximum number of rows.
     * @return \stdClass[]
     */
    public function get_request_candidates(eligibility $eligibility, int $limit): array {
        global $DB;

        [$eligiblesql, $params] = $eligibility->eligible_courses_sql('cand');
        $params['none'] = self::STATUS_NONE;
        $sql = "SELECT e.courseid, d.id AS diagid, d.requeststatus, d.timeattempted, d.timecompleted,
                       d.lastattemptfailed, d.failurecount, d.methodversion
                  FROM ({$eligiblesql}) e
             LEFT JOIN {" . self::TABLE . "} d ON d.courseid = e.courseid
                 WHERE d.id IS NULL OR d.requeststatus = :none
              ORDER BY COALESCE(d.timeattempted, 0) ASC, e.courseid ASC";
        return array_values($DB->get_records_sql($sql, $params, 0, $limit));
    }

    /**
     * Delete the diagnostic of a course and its child rows (light: index reads only).
     *
     * @param int $courseid Course id.
     */
    public function delete_for_course(int $courseid): void {
        global $DB;

        $record = $this->get_summary($courseid);
        if (!$record) {
            return;
        }
        foreach (self::CHILD_TABLES as $table) {
            $DB->delete_records($table, ['diagid' => $record->id]);
        }
        $DB->delete_records(self::TABLE, ['id' => $record->id]);
    }
}
