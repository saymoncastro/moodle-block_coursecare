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
 * The block about the course file overview (contracts/block-ui.md; Feature 008, contracts/ui.md).
 *
 * At most one number, the logical storage, always with the date of the analysis; no evaluation of the course.
 * Each sentence is a complete string with at most one parameter. The educational state (Feature 008) is derived
 * from the resolved state and the suggestion flag only: nothing new is read.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagnostic_line implements \renderable, \templatable {
    /** @var diagnostic_state State to describe. */
    private diagnostic_state $state;

    /** @var \moodle_url|null Overview page of the course (Feature 003), linked only when a result exists. */
    private ?\moodle_url $overviewurl;

    /** @var bool Whether the suggestion about large materials applies to the result (Feature 004). */
    private bool $hassuggestion;

    /** @var int Suggestions about assignments that apply now (Feature 009); -1 when not calculated yet. */
    private int $assignments;

    /**
     * Constructor.
     *
     * @param diagnostic_state $state Resolved state.
     * @param \moodle_url|null $overviewurl Overview page of the course.
     * @param bool $hassuggestion Whether the suggestion about large materials applies to the result (Feature 004).
     * @param array|null $assignments Suggestions about assignments that apply now (stored_suggestions::current());
     *     null when not calculated yet (Feature 009).
     */
    public function __construct(
        diagnostic_state $state,
        ?\moodle_url $overviewurl = null,
        bool $hassuggestion = false,
        ?array $assignments = []
    ) {
        $this->state = $state;
        $this->overviewurl = $overviewurl;
        $this->hassuggestion = $hassuggestion;
        $this->assignments = $assignments === null ? -1 : count($assignments);
    }

    /**
     * Data for the template: the parts of the line (see parts()) and the link to the overview page.
     *
     * @param \renderer_base $output Renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        $parts = self::parts($this->state);
        if ($this->state->hasresult && $this->state->partial) {
            // Feature 008: the partial coverage is a note of the block too, without numbers (the volume is the only one).
            $parts['notes'][] = ['text' => get_string('block:partial', 'block_coursecare')];
        }
        $parts['hasnotes'] = $parts['notes'] !== [];
        return $parts + self::educational(
            $this->state,
            $this->hassuggestion,
            max(0, $this->assignments),
            $this->assignments >= 0
        ) + $this->link();
    }

    /**
     * The educational state of a result (Feature 008, data-model.md): which welcome the block and the page give.
     *
     * "idea" when a suggestion applies (large materials of Feature 004, or assignments of Feature 009, which can
     * apply in a course without files too); "allgood" only for a complete, current and fully classified result with
     * files, no suggestion and the suggestions about assignments already calculated; "neutral" for any other result
     * with files (out of date, last update failed, partial coverage, assignments not calculated yet), so that a
     * positive message never hides them. Without files or without a result, the existing sentences of parts() stay
     * the message.
     *
     * @param diagnostic_state $state Resolved state.
     * @param bool $large Whether the suggestion about large materials applies.
     * @param int $assignments Suggestions about assignments that apply now.
     * @param bool $calculated Whether the suggestions about assignments were calculated.
     * @return array{kind: string, isidea: bool, isallgood: bool, title: string, message: string, total: int}
     */
    public static function educational(
        diagnostic_state $state,
        bool $large,
        int $assignments = 0,
        bool $calculated = true
    ): array {
        $total = ($large ? 1 : 0) + $assignments;
        if (!$state->hasresult) {
            $kind = in_array($state->state, [diagnostic_state::QUEUED, diagnostic_state::RUNNING], true)
                ? 'preparing' : 'notavailable';
        } else if ($total > 0) {
            $kind = 'idea';
        } else if ($state->filecount === 0) {
            $kind = 'nofiles';
        } else if ($state->state === diagnostic_state::COMPLETE && !$state->partial && $calculated) {
            $kind = 'allgood';
        } else {
            $kind = 'neutral';
        }
        $message = '';
        if ($kind === 'allgood') {
            $message = get_string('state:allgood_message', 'block_coursecare');
        } else if ($kind === 'idea') {
            $key = match (true) {
                $large && $assignments > 0 => 'state:idea_message_both',
                $large => 'state:idea_message',
                default => 'state:idea_message_attempts',
            };
            $message = get_string($key, 'block_coursecare');
        }
        $titled = in_array($kind, ['idea', 'allgood'], true);
        return [
            'kind' => $kind,
            'isidea' => $kind === 'idea',
            'isallgood' => $kind === 'allgood',
            'title' => $titled ? get_string('state:' . $kind . '_title', 'block_coursecare') : '',
            'message' => $message,
            'total' => $kind === 'idea' ? $total : 0,
        ];
    }

    /**
     * A size for teachers: the same rule as display_size() (powers of 1024, one decimal, whole bytes below 1 KB),
     * with the decimal separator of the language (format_float) and unambiguous unit symbols from this plugin.
     *
     * @param int $bytes Size in bytes.
     * @return string Size with a non-breaking space between the number and the unit.
     */
    public static function size(int $bytes): string {
        if ($bytes < 1024) {
            return display_size($bytes);
        }
        $units = ['kb', 'mb', 'gb', 'tb'];
        $magnitude = min(count($units), (int)floor(log($bytes, 1024)));
        return format_float($bytes / 1024 ** $magnitude, 1) . "\u{a0}" .
            get_string('size:' . $units[$magnitude - 1], 'block_coursecare');
    }

    /**
     * The parts of the file overview line, shared by the block and the opening of the overview page (Feature 003).
     *
     * With a result and files, the volume is apart from its label so that it can be seen at once; the label is a
     * complete phrase of its own, never a sentence built from pieces. Without a result, or without files, the line
     * is one sentence. The date and the notes of states E and F follow.
     *
     * @param diagnostic_state $state Resolved state.
     * @return array{hasvolume: bool, volumelabel: string, volume: string, sentences: array, date: string, notes: array}
     */
    public static function parts(diagnostic_state $state): array {
        $parts = ['hasvolume' => false, 'volumelabel' => '', 'volume' => '', 'sentences' => [], 'date' => '',
            'notes' => []];
        $sentence = fn(string $key): array => ['text' => get_string($key, 'block_coursecare')];
        switch ($state->state) {
            case diagnostic_state::QUEUED:
            case diagnostic_state::RUNNING:
                $parts['sentences'][] = $sentence('diagnostic:preparing');
                return $parts;
            case diagnostic_state::NO_FILES:
            case diagnostic_state::COMPLETE:
            case diagnostic_state::OUTDATED:
            case diagnostic_state::FAILED:
                if ($state->filecount === 0) {
                    $parts['sentences'][] = $sentence('diagnostic:nofiles');
                } else {
                    $parts['hasvolume'] = true;
                    $parts['volumelabel'] = get_string('overview:files', 'block_coursecare');
                    $parts['volume'] = self::size($state->logicalbytes);
                }
                $date = userdate($state->timecompleted, get_string('strftimedatetimeshort', 'langconfig'));
                $parts['date'] = get_string('diagnostic:calculatedon', 'block_coursecare', $date);
                if ($state->state === diagnostic_state::OUTDATED) {
                    $parts['notes'][] = $sentence('diagnostic:mayoutdated');
                } else if ($state->state === diagnostic_state::FAILED) {
                    $parts['notes'][] = $sentence('diagnostic:updatefailed');
                }
                return $parts;
            default:
                $parts['sentences'][] = $sentence('diagnostic:notavailable');
                return $parts;
        }
    }

    /**
     * The way to the overview page, only when it has something to show. With a suggestion (Feature 004), the action
     * "See the suggestion" opens the page at the suggestions (Feature 008, FR-005); otherwise the usual link.
     *
     * @return array overviewurl and overviewlink (empty strings when not shown).
     */
    private function link(): array {
        if (!$this->state->hasresult || $this->overviewurl === null) {
            return ['overviewurl' => '', 'overviewlink' => ''];
        }
        $url = new \moodle_url($this->overviewurl);
        $total = ($this->hassuggestion ? 1 : 0) + max(0, $this->assignments);
        if ($total > 0) {
            $url->set_anchor('block_coursecare-suggestions');
            $text = get_string($total === 1 ? 'state:idea_action' : 'state:idea_action_plural', 'block_coursecare');
        } else {
            $text = get_string('overview:link', 'block_coursecare');
        }
        return ['overviewurl' => $url->out(false), 'overviewlink' => $text];
    }
}
