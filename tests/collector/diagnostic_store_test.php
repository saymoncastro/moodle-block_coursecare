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
    private static function result(int $bytes): diagnostic_result {
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
        $store->save_result(42, self::result(500), 1100);
        $store->save_failure(42, 'dml_read_exception', 1150);
        $store->save_result(42, self::result(800), 1200);

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
        $store->save_result(42, self::result(500), 1100);

        // A category name longer than the column makes the insert fail inside the transaction.
        $summary = self::result(900)->summary;
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
        $store->save_result(42, self::result(500), 1100);
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
        $store->save_result(42, self::result(500), 1100);
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
}
