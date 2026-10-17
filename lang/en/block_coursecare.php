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
$string['overview:activities'] = 'Materials by activity';
$string['overview:activities_help'] = 'Only the materials of each activity are shown here.';
$string['overview:back'] = 'Back to the course';
$string['overview:categories'] = 'Where the files are';
$string['overview:category:material'] = 'Course materials';
$string['overview:category:material_help'] = 'Files you and other editors published: resources, folders, pages, books and descriptions.';
$string['overview:category:other'] = 'Other associated files';
$string['overview:category:other_help'] = 'Feedback, forum attachments, question and content banks.';
$string['overview:category:submission'] = 'Files submitted in activities';
$string['overview:category:submission_help'] = 'Files sent by participants in assignments and quizzes, shown only for the whole course.';
$string['overview:category:unclassified'] = 'Not classified';
$string['overview:category:unclassified_help'] = 'Files from areas the overview does not recognise yet; they count in the total.';
$string['overview:copies'] = 'Backups and recycle bin recorded in Moodle for this course';
$string['overview:copies_backup'] = 'Backups: {$a->count} ({$a->size})';
$string['overview:copies_help'] = 'Measured apart, not added to the total; backups kept outside Moodle are not included.';
$string['overview:copies_recyclebin'] = 'Recycle bin: {$a->count} ({$a->size})';
$string['overview:copies_unique'] = 'Unique content of the copies: {$a}';
$string['overview:courselevel'] = 'Course page, sections and blocks';
$string['overview:coverage'] = 'Files that could not be classified: {$a->count} ({$a->size}, {$a->share})';
$string['overview:intro'] = 'Get to know the files of your course: where they are and when they were measured.';
$string['overview:link'] = 'Learn more about the files in your course';
$string['overview:notmeaning'] = 'These numbers show the files Moodle records for this course. They are not the disk space used only by this course and do not show how much space deleting files would free.';
$string['overview:otheractivities'] = 'Other activities';
$string['overview:partial'] = 'Part of the files ({$a->size}, {$a->share}) could not be classified; they are included in the total.';
$string['overview:references'] = 'Links to files kept elsewhere';
$string['overview:references_external'] = 'External: {$a->count} ({$a->size})';
$string['overview:references_help'] = 'Counted apart and not added to the total. External links may point to files outside Moodle.';
$string['overview:references_internal'] = 'Inside Moodle: {$a->count} ({$a->size})';
$string['overview:references_undetermined'] = 'Size not known: {$a}';
$string['overview:removed'] = 'Activities removed after the last analysis';
$string['overview:share'] = '{$a}%';
$string['overview:shareless'] = 'less than 1%';
$string['overview:showall'] = 'Show all activities ({$a})';
$string['overview:technical'] = 'Technical details';
$string['overview:threshold'] = 'Large-file threshold used: {$a}';
$string['overview:title'] = 'Your course in numbers';
$string['overview:total'] = 'Files in this course, as recorded by Moodle: {$a}';
$string['overview:unique'] = 'Unique content: {$a}';
$string['overview:unique_help'] = 'The same file counted once, even when used in several places. The same content may also exist in other courses, so this is not space used only by this course.';
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
