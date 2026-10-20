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

namespace block_coursecare\analysis;

use block_coursecare\local\analysis\stored_suggestions;

/**
 * The suggestions stored with the diagnostic (Feature 009, research R4): format, "not calculated" and the filter that
 * the block and the page share.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\stored_suggestions
 */
final class stored_suggestions_test extends \advanced_testcase {
    /**
     * Encoding and decoding; null, empty, unreadable or unknown versions are "not calculated", never "none".
     */
    public function test_format(): void {
        $items = [['kind' => stored_suggestions::ATTEMPTS, 'cmid' => 12], ['kind' => stored_suggestions::ATTEMPTS, 'cmid' => 7]];
        $value = stored_suggestions::encode($items);
        $this->assertSame('{"v":1,"items":[{"kind":"attempts","cmid":12},{"kind":"attempts","cmid":7}]}', $value);
        $this->assertSame($items, stored_suggestions::decode($value));
        $this->assertSame([], stored_suggestions::decode(stored_suggestions::encode([])));
        foreach (
            [null, '', 'not json', '{"v":2,"items":[]}', '{"v":1}', '{"v":1,"items":[{"kind":"attempts"}]}',
                '{"v":1,"items":[{"kind":"attempts","cmid":"12"}]}'] as $invalid
        ) {
            $this->assertNull(stored_suggestions::decode($invalid), (string)$invalid);
        }
    }

    /**
     * Only existing assignments visible to students apply, in the order of the course; the same for the block and the
     * page (current()).
     */
    public function test_applicable_and_current(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $first = $generator->create_module('assign', ['course' => $course->id]);
        $hidden = $generator->create_module('assign', ['course' => $course->id]);
        $page = $generator->create_module('page', ['course' => $course->id]);
        $last = $generator->create_module('assign', ['course' => $course->id]);
        set_coursemodule_visible($hidden->cmid, 0);
        $item = fn(int $cmid): array => ['kind' => stored_suggestions::ATTEMPTS, 'cmid' => $cmid];
        $stored = [$item($last->cmid), $item(987654), $item($page->cmid), $item($hidden->cmid), $item($first->cmid)];

        $applicable = stored_suggestions::applicable($stored, get_fast_modinfo($course->id));
        $this->assertSame([(int)$first->cmid, (int)$last->cmid], array_column($applicable, 'cmid'));
        $this->assertNull(stored_suggestions::applicable(null, get_fast_modinfo($course->id)));

        $record = (object)['suggestions' => stored_suggestions::encode($stored)];
        $this->assertSame(
            [(int)$first->cmid, (int)$last->cmid],
            array_column(stored_suggestions::current($record, true, $course->id), 'cmid')
        );
        $this->assertSame([], stored_suggestions::current($record, false, $course->id), 'Without a result, none.');
        $this->assertSame([], stored_suggestions::current(null, true, $course->id), 'Without a record, none.');
        $this->assertNull(stored_suggestions::current((object)['suggestions' => null], true, $course->id));

        // Showing the hidden assignment again brings its suggestion back at once.
        set_coursemodule_visible($hidden->cmid, 1);
        $this->assertSame(
            [(int)$first->cmid, (int)$hidden->cmid, (int)$last->cmid],
            array_column(stored_suggestions::current($record, true, $course->id), 'cmid')
        );
    }
}
