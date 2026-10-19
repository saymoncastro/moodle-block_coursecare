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

namespace block_coursecare\collector;

use block_coursecare\local\analysis\diagnostic_result;
use block_coursecare\local\collector\diagnostic_store;

/**
 * Tests of the diagnostic store: requests, atomic results, failures and removal (data-model.md).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\collector\diagnostic_store
 */
final class diagnostic_store_test extends \advanced_testcase {
    /**
     * A result with a given logical volume, one category row, one activity row and one link.
     *
     * @param int $bytes Logical bytes.
     * @return diagnostic_result
     */
    private static function sample_result(int $bytes): diagnostic_result {
        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary = ['methodversion' => 1, 'largethreshold' => 100, 'filecount' => 1, 'logicalbytes' => $bytes] + $summary;
        $category = array_fill_keys(diagnostic_result::CATEGORY_FIELDS, 1);
        $activity = array_fill_keys(diagnostic_result::ACTIVITY_FIELDS, 1);
        return new diagnostic_result(
            $summary,
            ['material' => $category],
            [7 => $activity],
            [['fileid' => 99, 'cmid' => 7, 'filesize' => 200]]
        );
    }

    /**
     * A request is accepted only when none is pending.
     */
    public function test_request_once(): void {
        $this->resetAfterTest();
        $store = new diagnostic_store();
        $this->assertTrue($store->request(42, 1000));
        $this->assertFalse($store->request(42, 1001));
        $record = $store->get_summary(42);
        $this->assertSame(diagnostic_store::STATUS_QUEUED, (int)$record->requeststatus);
        $this->assertSame(1000, (int)$record->timerequested);
        $this->assertNull($record->timecompleted);
    }

    /**
     * Saving a result replaces the previous one completely and clears the request and failure state.
     */
    public function test_save_result_replaces(): void {
        global $DB;
        $this->resetAfterTest();
        $store = new diagnostic_store();
        $store->request(42, 1000);
        $store->save_result(42, self::sample_result(500), 1100);
        $store->save_failure(42, 'dml_read_exception', 1150);
        $store->save_result(42, self::sample_result(800), 1200);

        $record = $store->get_summary(42);
        $this->assertSame(800, (int)$record->logicalbytes);
        $this->assertSame(1200, (int)$record->timecompleted);
        $this->assertSame(diagnostic_store::STATUS_NONE, (int)$record->requeststatus);
        $this->assertSame(0, (int)$record->lastattemptfailed);
        $this->assertSame(0, (int)$record->failurecount);
        $this->assertNull($record->failurereason);
        $this->assertSame(1, $DB->count_records('block_coursecare_cat', ['diagid' => $record->id]));
        $this->assertSame(1, $DB->count_records('block_coursecare_act', ['diagid' => $record->id]));
        $this->assertSame(1, $DB->count_records('block_coursecare_large', ['diagid' => $record->id]));
    }

    /**
     * An error inside the transaction leaves the previous result intact.
     */
    public function test_save_result_is_atomic(): void {
        global $DB;
        $this->resetAfterTest();
        // The test must run without the outer test transaction, so that the store's own transaction is real.
        $this->preventResetByRollback();
        $store = new diagnostic_store();
        $store->save_result(42, self::sample_result(500), 1100);

        // A category name longer than the column makes the insert fail inside the transaction.
        $summary = self::sample_result(900)->summary;
        $bad = new diagnostic_result($summary, [str_repeat('x', 40) => array_fill_keys(diagnostic_result::CATEGORY_FIELDS, 1)]);
        try {
            $store->save_result(42, $bad, 1300);
            $this->fail('An exception was expected');
        } catch (\dml_exception $e) {
            $this->assertInstanceOf(\dml_exception::class, $e);
        }
        $record = $store->get_summary(42);
        $this->assertSame(500, (int)$record->logicalbytes);
        $this->assertSame(1100, (int)$record->timecompleted);
        $this->assertSame(1, $DB->count_records('block_coursecare_cat', ['diagid' => $record->id]));
    }

    /**
     * Work run through in_transaction() is committed together, or rolled back together on an exception.
     */
    public function test_in_transaction(): void {
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $store = new diagnostic_store();

        $store->in_transaction(function () use ($store): void {
            $store->request(41, 1000);
            $store->request(42, 1000);
        });
        $this->assertNotNull($store->get_summary(41));
        $this->assertNotNull($store->get_summary(42));

        try {
            $store->in_transaction(function () use ($store): void {
                $store->request(43, 1000);
                throw new \moodle_exception('error');
            });
            $this->fail('An exception was expected');
        } catch (\moodle_exception $e) {
            $this->assertSame('error', $e->errorcode);
        }
        $this->assertNull($store->get_summary(43));
    }

    /**
     * A failure keeps the result, counts consecutive failures and truncates the code.
     */
    public function test_save_failure_keeps_result(): void {
        $this->resetAfterTest();
        $store = new diagnostic_store();
        $store->save_result(42, self::sample_result(500), 1100);
        $store->save_failure(42, 'first', 1200);
        $store->save_failure(42, str_repeat('y', 45), 1300);

        $record = $store->get_summary(42);
        $this->assertSame(500, (int)$record->logicalbytes);
        $this->assertSame(1100, (int)$record->timecompleted);
        $this->assertSame(1300, (int)$record->timeattempted);
        $this->assertSame(1, (int)$record->lastattemptfailed);
        $this->assertSame(2, (int)$record->failurecount);
        $this->assertSame(str_repeat('y', 30), $record->failurereason);
    }

    /**
     * Removal deletes the record and its children.
     */
    public function test_delete_for_course(): void {
        global $DB;
        $this->resetAfterTest();
        $store = new diagnostic_store();
        $store->save_result(42, self::sample_result(500), 1100);
        $store->delete_for_course(42);
        $this->assertNull($store->get_summary(42));
        foreach (diagnostic_store::CHILD_TABLES as $table) {
            $this->assertSame(0, $DB->count_records($table));
        }
    }

    /**
     * Reading the summary is a single database read.
     */
    public function test_get_summary_single_read(): void {
        global $DB;
        $this->resetAfterTest();
        $store = new diagnostic_store();
        $store->request(42, 1000);
        $reads = $DB->perf_get_reads();
        $store->get_summary(42);
        $this->assertSame(1, $DB->perf_get_reads() - $reads);
    }
    /**
     * Large-material links grouped by activity: count and recorded size per cmid, for one diagnostic only.
     */
    public function test_get_large_materials_by_activity(): void {
        $this->resetAfterTest();
        /** @var \block_coursecare_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $diag = $generator->create_diagnostic(['courseid' => $course->id, 'timecompletedago' => 60, 'largelinks' => [
            ['cmid' => 11, 'filesize' => 100], ['cmid' => 11, 'filesize' => 50], ['cmid' => 12, 'filesize' => 70],
            ['cmid' => 13, 'filesize' => 9], ['cmid' => 0, 'filesize' => 30],
        ]]);
        $generator->create_diagnostic(['courseid' => $other->id, 'timecompletedago' => 60, 'largelinks' => [
            ['cmid' => 11, 'filesize' => 999],
        ]]);
        $empty = $generator->create_diagnostic(['courseid' => $this->getDataGenerator()->create_course()->id,
            'timecompletedago' => 60]);

        $store = new diagnostic_store();
        $rows = $store->get_large_materials_by_activity((int)$diag->id);
        ksort($rows);
        $this->assertSame([0, 11, 12, 13], array_keys($rows));
        $this->assertSame(['count' => 2, 'bytes' => 150], (array)$rows[11]);
        $this->assertSame(['count' => 1, 'bytes' => 70], (array)$rows[12]);
        $this->assertSame(['count' => 1, 'bytes' => 30], (array)$rows[0]);
        $this->assertSame([], $store->get_large_materials_by_activity((int)$empty->id));
    }

    /**
     * Whether a diagnostic has a link to a large course material: one indexed read, for that diagnostic only
     * (Feature 004, block line of the suggestion).
     */
    public function test_has_large_materials(): void {
        global $DB;
        $this->resetAfterTest();
        /** @var \block_coursecare_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('block_coursecare');
        $with = $generator->create_diagnostic(['courseid' => $this->getDataGenerator()->create_course()->id,
            'timecompletedago' => 60, 'largelinks' => [['cmid' => 11, 'filesize' => 100]]]);
        $without = $generator->create_diagnostic(['courseid' => $this->getDataGenerator()->create_course()->id,
            'timecompletedago' => 60]);

        $store = new diagnostic_store();
        $this->assertTrue($store->has_large_materials((int)$with->id));
        $this->assertFalse($store->has_large_materials((int)$without->id));
        $this->assertFalse($store->has_large_materials(987654));
        $reads = $DB->perf_get_reads();
        $store->has_large_materials((int)$with->id);
        $this->assertSame(1, $DB->perf_get_reads() - $reads);
    }

    /**
     * A result with categories and unique content, for the history (Feature 006).
     *
     * @param int $material Material bytes.
     * @param int $submission Submission bytes.
     * @param int $partial Partial coverage flag.
     * @return diagnostic_result
     */
    private static function history_result(int $material, int $submission, int $partial = 0): diagnostic_result {
        $summary = array_fill_keys(diagnostic_result::SUMMARY_FIELDS, null);
        $summary = ['methodversion' => 1, 'largethreshold' => 100, 'filecount' => 3, 'logicalbytes' => $material + $submission,
            'uniquecount' => 2, 'uniquebytes' => $material, 'partial' => $partial] + $summary;
        $row = fn(int $bytes): array => ['filecount' => 1, 'logicalbytes' => $bytes, 'uniquecount' => 1, 'uniquebytes' => $bytes];
        return new diagnostic_result($summary, ['material' => $row($material), 'submission' => $row($submission)], [], []);
    }

    /**
     * Each successful analysis writes the point of its day, a second analysis on the same day replaces it, a failure
     * writes nothing, and the points are read, purged and removed with the course (Feature 006, T004).
     */
    public function test_history_point_saved_with_result(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setTimezone('America/Sao_Paulo');
        $store = new diagnostic_store();
        $noon = make_timestamp(2026, 10, 10, 12, 0, 0, \core_date::get_server_timezone());
        $day = diagnostic_store::history_day($noon);

        $DB->set_debug(true);
        ob_start();
        $store->save_result(42, self::history_result(1000, 500), $noon);
        $points = $store->get_history(42, 0);
        $queries = ob_get_clean();
        $DB->set_debug(false);
        $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($CFG->prefix, '/') . 'files\b/', $queries);

        $this->assertCount(1, $points);
        $point = $points[0];
        $this->assertSame($day, (int)$point->timeday);
        $this->assertSame($noon, (int)$point->timecompleted);
        $this->assertSame(1500, (int)$point->logicalbytes);
        $this->assertSame(3, (int)$point->filecount);
        $this->assertSame(2, (int)$point->uniquecount);
        $this->assertSame(1000, (int)$point->uniquebytes);
        $this->assertSame(1000, (int)$point->materialbytes);
        $this->assertSame(500, (int)$point->submissionbytes);
        $this->assertSame(0, (int)$point->otherbytes);
        $this->assertSame(0, (int)$point->unclassifiedbytes);
        $this->assertSame(1, (int)$point->methodversion);
        $this->assertSame(0, (int)$point->partial);

        // A second analysis the same day replaces the point; the next day adds one; a failure changes nothing.
        $store->save_result(42, self::history_result(1200, 500, 1), $noon + 3 * HOURSECS);
        $this->assertCount(1, $store->get_history(42, 0));
        $this->assertSame(1700, (int)$store->get_history(42, 0)[0]->logicalbytes);
        $this->assertSame(1, (int)$store->get_history(42, 0)[0]->partial);
        $store->save_result(42, self::history_result(0, 0), $noon + DAYSECS);
        $store->save_failure(42, 'dml_read_exception', $noon + 2 * DAYSECS);
        $points = $store->get_history(42, 0);
        $this->assertCount(2, $points);
        $this->assertSame([1700, 0], array_map(fn($p) => (int)$p->logicalbytes, $points));
        $this->assertSame((int)$store->get_summary(42)->logicalbytes, (int)end($points)->logicalbytes);

        // Only the points of the course from the given day on.
        $store->save_result(43, self::history_result(10, 0), $noon);
        $this->assertCount(1, $store->get_history(42, $day + 1));
        $this->assertCount(1, $store->get_history(43, 0));

        // Purge: older than a day, at most the limit, any course.
        $this->assertSame(1, $store->purge_history($day + 1, 1));
        $this->assertSame(1, $store->purge_history($day + 1, 10));
        $this->assertSame(0, $store->purge_history($day + 1, 10));
        $this->assertCount(1, $store->get_history(42, 0));

        // Removal of the course removes its history.
        $store->delete_for_course(42);
        $this->assertSame([], $store->get_history(42, 0));
        $this->assertCount(0, $store->get_history(43, 0));
    }

    /**
     * An error inside the transaction of the result changes neither the result nor the history point.
     */
    public function test_history_point_is_atomic(): void {
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $store = new diagnostic_store();
        $now = 1790000000;
        $store->save_result(42, self::history_result(1000, 0), $now);
        $summary = self::history_result(5000, 0)->summary;
        $bad = new diagnostic_result($summary, [str_repeat('x', 40) => array_fill_keys(diagnostic_result::CATEGORY_FIELDS, 1)]);
        try {
            $store->save_result(42, $bad, $now + 60);
            $this->fail('An exception was expected');
        } catch (\dml_exception $e) {
            $this->assertInstanceOf(\dml_exception::class, $e);
        }
        $points = $store->get_history(42, 0);
        $this->assertCount(1, $points);
        $this->assertSame(1000, (int)$points[0]->logicalbytes);
    }
}
