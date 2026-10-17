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

namespace block_coursecare\output;

/**
 * Sizes shown to teachers: the rule of display_size() with the decimal separator of the language.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\output\diagnostic_line::size
 */
final class diagnostic_line_test extends \advanced_testcase {
    /**
     * In English, the same text as display_size(), from bytes to terabytes.
     */
    public function test_size_matches_display_size_in_english(): void {
        foreach ([0, 6, 1023, 1024, 2048, 1536000, 6291456, 3 * 1024 ** 3, 5 * 1024 ** 4] as $bytes) {
            $this->assertSame(display_size($bytes), diagnostic_line::size($bytes), (string)$bytes);
        }
    }

    /**
     * In a language with a decimal comma, the comma is used and the unit stays the plugin's symbol.
     */
    public function test_size_uses_the_decimal_separator_of_the_language(): void {
        global $CFG;
        $this->resetAfterTest();
        // A minimal installed language whose only difference is the decimal comma.
        make_writable_directory($CFG->dataroot . '/lang/xx_dec');
        file_put_contents($CFG->dataroot . '/lang/xx_dec/langconfig.php', "<?php\n" .
            "\$string['thislanguage'] = 'Decimal comma';\n\$string['parentlanguage'] = '';\n\$string['decsep'] = ',';\n");
        get_string_manager()->reset_caches();
        force_current_language('xx_dec');
        try {
            $this->assertSame("6,0\u{a0}MB", diagnostic_line::size(6291456));
            $this->assertSame("1,5\u{a0}KB", diagnostic_line::size(1536));
            $this->assertSame(display_size(6), diagnostic_line::size(6));
        } finally {
            force_current_language('en');
            get_string_manager()->reset_caches();
        }
    }
}
