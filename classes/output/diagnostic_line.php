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
 * The single line of the block about the course file overview (contracts/block-ui.md).
 *
 * At most one number, the logical storage, always with the date of the analysis; no evaluation of the course.
 * Each sentence is a complete string with at most one parameter.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_line implements \renderable, \templatable {
    /** @var diagnostic_state State to describe. */
    private diagnostic_state $state;

    /**
     * Constructor.
     *
     * @param diagnostic_state $state Resolved state.
     */
    public function __construct(diagnostic_state $state) {
        $this->state = $state;
    }

    /**
     * Data for the template: the sentences of the line.
     *
     * @param \renderer_base $output Renderer.
     * @return array{sentences: array<int, array{text: string}>}
     */
    public function export_for_template(\renderer_base $output): array {
        $sentences = [];
        switch ($this->state->state) {
            case diagnostic_state::QUEUED:
            case diagnostic_state::RUNNING:
                $sentences[] = get_string('diagnostic:preparing', 'block_coursecare');
                break;
            case diagnostic_state::NO_FILES:
            case diagnostic_state::COMPLETE:
            case diagnostic_state::OUTDATED:
            case diagnostic_state::FAILED:
                $sentences[] = $this->state->filecount === 0
                    ? get_string('diagnostic:nofiles', 'block_coursecare')
                    : get_string('diagnostic:volume', 'block_coursecare', display_size($this->state->logicalbytes));
                $sentences[] = $this->calculated_on();
                if ($this->state->state === diagnostic_state::OUTDATED) {
                    $sentences[] = get_string('diagnostic:mayoutdated', 'block_coursecare');
                } else if ($this->state->state === diagnostic_state::FAILED) {
                    $sentences[] = get_string('diagnostic:updatefailed', 'block_coursecare');
                }
                break;
            default:
                $sentences[] = get_string('diagnostic:notavailable', 'block_coursecare');
        }
        return ['sentences' => array_map(fn(string $text): array => ['text' => $text], $sentences)];
    }

    /**
     * Sentence with the date of the analysis, in the user's time zone.
     *
     * @return string
     */
    private function calculated_on(): string {
        $date = userdate($this->state->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
        return get_string('diagnostic:calculatedon', 'block_coursecare', $date);
    }
}
