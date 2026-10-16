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

/**
 * Strings for component 'block_coursecare', language 'en'.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cli:alreadypending'] = 'An analysis is already pending for course {$a}.';
$string['cli:help'] = 'Storage diagnostic of the CourseCare block (administrators).

Exactly one mode per run:
  --queue --course=ID   Request the analysis of one course that has the block.
  --queue --all         Request the analysis of every course that has the block and no pending request.
  --run --course=ID     Analyse one course now, in this process (for checks and benchmarks).
  --show --course=ID    Show the current result of one course.

Options:
  --json                With --run or --show: print JSON.
  -h, --help            Print this help.

Exit codes: 0 success, 1 invalid options, 2 course missing or without the block, 3 analysis already running, 4 analysis failed (the previous result was kept).

Example:
  php blocks/coursecare/cli/diagnostics.php --show --course=42';
$string['cli:ineligible'] = 'Course {$a} does not exist or does not have the CourseCare block.';
$string['cli:invalidoptions'] = 'Invalid options. Use --help to see how to use this tool.';
$string['cli:queued'] = 'Analysis requested for course {$a}.';
$string['cli:queuedall'] = 'Analyses requested: {$a}.';
$string['cli:runresult'] = 'Course {$a->courseid}: {$a->outcome} in {$a->seconds} s, peak memory {$a->memory}.';
$string['cli:showheading'] = 'Storage diagnostic of course {$a}';
$string['coursecare:addinstance'] = 'Add a new CourseCare block';
$string['coursecare:view'] = 'View the CourseCare block';
$string['diagnostic:calculatedon'] = 'Calculated on {$a}.';
$string['diagnostic:mayoutdated'] = 'This information may be out of date.';
$string['diagnostic:nofiles'] = 'No files were found in this course\'s materials and activities.';
$string['diagnostic:notavailable'] = 'The file overview for this course is not available yet.';
$string['diagnostic:preparing'] = 'The file overview for this course is being prepared.';
$string['diagnostic:updatefailed'] = 'The latest update could not be completed.';
$string['diagnostic:volume'] = 'Files in this course, as recorded by Moodle: {$a}.';
$string['intro'] = 'Soon you will find guidance here on using your course resources more mindfully.';
$string['pluginname'] = 'CourseCare';
$string['privacy:metadata'] = 'The CourseCare block does not store any personal data. Its storage overview keeps only aggregated measurements of the files of each course and activity, and links to large course material files (file identifier and recorded size).';
$string['settings:largefilethreshold'] = 'Large-file threshold (MB)';
$string['settings:largefilethreshold_desc'] = 'Files of this size or larger are counted as large files in the course storage overview (1 MB = 1,048,576 bytes). It is a neutral screening value, not a teaching limit and not a judgement about how a file is used.';
$string['settings:reanalysisinterval'] = 'Reanalysis interval';
$string['settings:reanalysisinterval_desc'] = 'How often each course that has the block is analysed again in the background.';
$string['settings:stalenessthreshold'] = 'Out-of-date limit';
$string['settings:stalenessthreshold_desc'] = 'When the last completed analysis is older than this, the block says that the information may be out of date. Use a value longer than the reanalysis interval.';
$string['tagline'] = 'Small choices, a better learning space for everyone.';
$string['task:analysecourse'] = 'Analyse the file storage of a course';
$string['task:queueanalyses'] = 'Queue the file storage analyses of the courses';
