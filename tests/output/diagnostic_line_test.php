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

use block_coursecare\local\analysis\diagnostic_state;

/**
 * Sizes shown to teachers (the rule of display_size() with the decimal separator of the language) and the educational
 * state of the block (Feature 008).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\output\diagnostic_line::size
 * @covers     \block_coursecare\output\diagnostic_line::educational
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

    /**
     * Every combination of the resolved state, files, partial coverage and suggestion (data-model.md).
     *
     * @return array<string, array{0: string, 1: bool, 2: ?int, 3: bool, 4: bool, 5: string}>
     */
    public static function educational_provider(): array {
        $cases = [];
        foreach ([diagnostic_state::NOT_ANALYSED, diagnostic_state::QUEUED, diagnostic_state::RUNNING] as $state) {
            foreach ([false, true] as $suggestion) {
                $expected = $state === diagnostic_state::NOT_ANALYSED ? 'notavailable' : 'preparing';
                $cases["{$state} suggestion=" . (int)$suggestion] = [$state, false, null, false, $suggestion, $expected];
            }
        }
        $withresult = [diagnostic_state::COMPLETE, diagnostic_state::OUTDATED, diagnostic_state::FAILED,
            diagnostic_state::NO_FILES];
        foreach ($withresult as $state) {
            foreach ([0, 5] as $files) {
                foreach ([false, true] as $partial) {
                    foreach ([false, true] as $suggestion) {
                        if ($files === 0) {
                            $expected = 'nofiles';
                        } else if ($suggestion) {
                            $expected = 'idea';
                        } else if ($state === diagnostic_state::COMPLETE && !$partial) {
                            $expected = 'allgood';
                        } else {
                            $expected = 'neutral';
                        }
                        $name = "{$state} files={$files} partial=" . (int)$partial . ' suggestion=' . (int)$suggestion;
                        $cases[$name] = [$state, true, $files, $partial, $suggestion, $expected];
                    }
                }
            }
        }
        return $cases;
    }

    /**
     * The educational state: "allgood" only for a complete, current, fully classified result with files and no
     * suggestion; a title and a message only for "idea" and "allgood".
     *
     * @dataProvider educational_provider
     * @param string $state Resolved state.
     * @param bool $hasresult Whether a result exists.
     * @param int|null $files Records of the main scope.
     * @param bool $partial H marker.
     * @param bool $suggestion Whether the suggestion applies.
     * @param string $expected Expected kind.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('educational_provider')]
    public function test_educational_state(
        string $state,
        bool $hasresult,
        ?int $files,
        bool $partial,
        bool $suggestion,
        string $expected
    ): void {
        $resolved = new diagnostic_state(
            $state,
            $hasresult,
            $partial,
            $hasresult ? 1000 : null,
            $hasresult ? 1792300000 : null,
            $files
        );
        $result = diagnostic_line::educational($resolved, $suggestion);
        $this->assertSame($expected, $result['kind']);
        $this->assertSame($expected === 'idea', $result['isidea']);
        $this->assertSame($expected === 'allgood', $result['isallgood']);
        if (in_array($expected, ['idea', 'allgood'], true)) {
            $this->assertSame(get_string("state:{$expected}_title", 'block_coursecare'), $result['title']);
            $this->assertSame(get_string("state:{$expected}_message", 'block_coursecare'), $result['message']);
        } else {
            $this->assertSame('', $result['title']);
            $this->assertSame('', $result['message']);
        }
    }

    /**
     * The block: the partial coverage note (without numbers) and the action "See the suggestion" to the suggestions.
     */
    public function test_block_notes_and_action(): void {
        $this->resetAfterTest();
        $url = new \moodle_url('/blocks/coursecare/overview.php', ['id' => 7]);
        $renderer = $GLOBALS['PAGE']->get_renderer('core');

        $partialoutdated = new diagnostic_state(diagnostic_state::OUTDATED, true, true, 2048, 1792300000, 3);
        $data = (new diagnostic_line($partialoutdated, $url, false))->export_for_template($renderer);
        $this->assertSame('neutral', $data['kind']);
        $this->assertSame([get_string('diagnostic:mayoutdated', 'block_coursecare'),
            get_string('block:partial', 'block_coursecare')], array_column($data['notes'], 'text'));
        $this->assertTrue($data['hasnotes']);
        $this->assertSame(get_string('overview:link', 'block_coursecare'), $data['overviewlink']);
        $this->assertStringNotContainsString('#', $data['overviewurl']);

        $idea = new diagnostic_state(diagnostic_state::COMPLETE, true, false, 2048, 1792300000, 3);
        $data = (new diagnostic_line($idea, $url, true))->export_for_template($renderer);
        $this->assertSame('idea', $data['kind']);
        $this->assertFalse($data['hasnotes']);
        $this->assertSame(get_string('state:idea_action', 'block_coursecare'), $data['overviewlink']);
        $this->assertStringEndsWith('#block_coursecare-suggestions', $data['overviewurl']);
        $this->assertDoesNotMatchRegularExpression('/\d/', $data['message'], 'No number in the message of the block.');
    }
}
