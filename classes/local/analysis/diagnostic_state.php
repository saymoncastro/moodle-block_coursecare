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

namespace block_coursecare\local\analysis;

/**
 * Displayed state of a course diagnostic (FR-045): the state, the partial marker and the values to show.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class diagnostic_state {
    /** @var int Request status stored in the diagnostic record: no pending request. */
    public const REQUEST_NONE = 0;
    /** @var int Request status: queued. */
    public const REQUEST_QUEUED = 1;
    /** @var int Request status: running. */
    public const REQUEST_RUNNING = 2;

    /** @var string A. Not analysed yet (also a failure without any previous result). */
    public const NOT_ANALYSED = 'notanalysed';
    /** @var string B. Waiting to be processed, no previous result. */
    public const QUEUED = 'queued';
    /** @var string C. Running, no previous result. */
    public const RUNNING = 'running';
    /** @var string D. Completed and within the out-of-date limit. */
    public const COMPLETE = 'complete';
    /** @var string E. Completed result older than the out-of-date limit. */
    public const OUTDATED = 'outdated';
    /** @var string F. The last attempt failed; a previous result exists and is kept. */
    public const FAILED = 'failed';
    /** @var string G. Completed analysis that found no file in the main scope. */
    public const NO_FILES = 'nofiles';

    /** @var string One of the state constants. */
    public readonly string $state;

    /** @var bool Whether a completed result exists. */
    public readonly bool $hasresult;

    /** @var bool H marker: unclassified files or undetermined references. */
    public readonly bool $partial;

    /** @var int|null Logical storage of the result, or null without result. */
    public readonly ?int $logicalbytes;

    /** @var int|null Completion time of the result, or null without result. */
    public readonly ?int $timecompleted;

    /** @var int|null Records of the main scope in the result, or null without result. */
    public readonly ?int $filecount;

    /**
     * Constructor.
     *
     * @param string $state One of the state constants.
     * @param bool $hasresult Whether a completed result exists.
     * @param bool $partial H marker.
     * @param int|null $logicalbytes Logical storage of the result.
     * @param int|null $timecompleted Completion time of the result.
     * @param int|null $filecount Records of the main scope in the result.
     */
    public function __construct(
        string $state,
        bool $hasresult,
        bool $partial,
        ?int $logicalbytes,
        ?int $timecompleted,
        ?int $filecount = null
    ) {
        $this->state = $state;
        $this->hasresult = $hasresult;
        $this->partial = $partial;
        $this->logicalbytes = $logicalbytes;
        $this->timecompleted = $timecompleted;
        $this->filecount = $filecount;
    }
}
