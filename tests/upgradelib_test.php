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

namespace block_coursecare;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/coursecare/db/upgradelib.php');

/**
 * Tests of the first history points created on the upgrade to 0.6.0 (Feature 006, FR-006).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::block_coursecare_seed_history
 */
final class upgradelib_test extends \advanced_testcase {
    /**
     * Each completed current result becomes one point with its real values and date; a queued request does not; a
     * second run adds nothing; the files table is never read.
     */
    public function test_seed_history(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setTimezone('America/Sao_Paulo');
        $generator = $this->getDataGenerator();
        /** @var \block_coursecare_generator $plugin */
        $plugin = $generator->get_plugin_generator('block_coursecare');
        $full = $generator->create_course();
        $empty = $generator->create_course();
        $queued = $generator->create_course();
        $row = fn(int $bytes): array => ['filecount' => 1, 'logicalbytes' => $bytes, 'uniquecount' => 1,
            'uniquebytes' => $bytes];
        $plugin->create_diagnostic(['courseid' => $full->id, 'timecompletedago' => 3 * DAYSECS, 'logicalbytes' => 1500,
            'filecount' => 3, 'uniquecount' => 2, 'uniquebytes' => 1200, 'partial' => 1,
            'categories' => ['material' => $row(1000), 'submission' => $row(400), 'unclassified' => $row(100)]]);
        $plugin->create_diagnostic(['courseid' => $empty->id, 'timecompletedago' => 600, 'logicalbytes' => 0,
            'filecount' => 0, 'categories' => []]);
        $plugin->create_diagnostic(['courseid' => $queued->id, 'requeststatus' => 1, 'timerequested' => time()]);
        $DB->delete_records('block_coursecare_hist');

        $DB->set_debug(true);
        ob_start();
        $created = block_coursecare_seed_history();
        $queries = ob_get_clean();
        $DB->set_debug(false);
        $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($CFG->prefix, '/') . 'files\b/', $queries);

        $this->assertSame(2, $created);
        $diag = $DB->get_record('block_coursecare_diag', ['courseid' => $full->id]);
        $point = $DB->get_record('block_coursecare_hist', ['courseid' => $full->id], '*', MUST_EXIST);
        $this->assertSame((int)$diag->timecompleted, (int)$point->timecompleted);
        $day = usergetmidnight((int)$diag->timecompleted, \core_date::get_server_timezone());
        $this->assertSame($day, (int)$point->timeday);
        $this->assertSame(1500, (int)$point->logicalbytes);
        $this->assertSame(1200, (int)$point->uniquebytes);
        $this->assertSame(1000, (int)$point->materialbytes);
        $this->assertSame(400, (int)$point->submissionbytes);
        $this->assertSame(0, (int)$point->otherbytes);
        $this->assertSame(100, (int)$point->unclassifiedbytes);
        $this->assertSame(1, (int)$point->partial);
        $this->assertSame(0, (int)$DB->get_field('block_coursecare_hist', 'logicalbytes', ['courseid' => $empty->id]));
        $this->assertFalse($DB->record_exists('block_coursecare_hist', ['courseid' => $queued->id]));

        $this->assertSame(0, block_coursecare_seed_history());
        $this->assertSame(2, $DB->count_records('block_coursecare_hist'));
    }
}
