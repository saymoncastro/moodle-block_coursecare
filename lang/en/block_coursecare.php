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

$string['block:suggestion'] = 'There is a suggestion about the files of your course.';
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
$string['overview:activities'] = 'Materials by activity';
$string['overview:activities_help'] = 'Only the materials of each activity, as a share of all the materials of the course.';
$string['overview:back'] = 'Back to the course';
$string['overview:categories'] = 'Where the files are';
$string['overview:categories_help'] = 'How much of all the files of the course each kind of file represents.';
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
$string['overview:files'] = 'Course files recorded in Moodle';
$string['overview:intro'] = 'See where the files of your course are and when they were measured.';
$string['overview:large'] = 'Large files';
$string['overview:large_count'] = 'Files: {$a}';
$string['overview:large_help'] = 'The threshold is a screening value set by the site, not a teaching rule.';
$string['overview:large_materials'] = 'Large materials by activity';
$string['overview:large_none'] = 'No file of {$a} or more in this course.';
$string['overview:large_summary'] = 'Files of {$a->threshold} or more: {$a->count} ({$a->size}).';
$string['overview:large_truncated'] = 'This list considers the 1,000 largest materials.';
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
$string['overview:unique'] = 'Unique content: {$a}';
$string['overview:unique_help'] = 'The same file counted once, even when used in several places. The same content may also exist in other courses, so this is not space used only by this course.';
$string['pluginname'] = 'CourseCare';
$string['potential:attempts'] = 'Up to {$a} attempts.';
$string['potential:attempts_one'] = '1 attempt.';
$string['potential:attempts_unlimited'] = 'Unlimited attempts.';
$string['potential:files_group'] = 'Up to {$a->files} files per group, of up to {$a->size} each.';
$string['potential:files_group_one'] = 'Up to 1 file per group, of up to {$a->size}.';
$string['potential:files_participant'] = 'Up to {$a->files} files per participant, of up to {$a->size} each.';
$string['potential:files_participant_one'] = 'Up to 1 file per participant, of up to {$a->size}.';
$string['potential:formula'] = '{$a->units} × {$a->files} × {$a->size}';
$string['potential:formula_attempts'] = '{$a->units} × {$a->attempts} attempts × {$a->files} × {$a->size}';
$string['potential:heading'] = 'Potential of assignments';
$string['potential:hidden'] = 'Hidden from students: nobody can submit while it is hidden.';
$string['potential:how'] = 'How the potential is calculated, and its limits';
$string['potential:how_attempts'] = 'Each attempt keeps its own files. When a student starts from the previous submission, the files are copied: the copies count in the files recorded by Moodle, but identical content is stored only once.';
$string['potential:how_formula'] = 'Participants (or groups) × attempts × files per submission × size limit per file, with the current settings.';
$string['potential:how_server'] = 'Server limits are the ones in force when this page was shown.';
$string['potential:intro'] = 'What the current settings of the assignments allow to be stored in submitted files. Calculated now, with the current settings.';
$string['potential:limit_activity'] = 'The size limit per file is set in the assignment.';
$string['potential:limit_change'] = 'Participants, groups and settings can change; the potential is valid for now.';
$string['potential:limit_course'] = 'The size limit per file comes from the course settings.';
$string['potential:limit_dates'] = 'Due dates and access restrictions are not considered.';
$string['potential:limit_default'] = 'The size limit per file is the site default for assignments.';
$string['potential:limit_ignore'] = 'People allowed to ignore file size limits (usually not students) have no ceiling.';
$string['potential:limit_other'] = 'Files inserted in online text and feedback files are not included.';
$string['potential:limit_server'] = 'The size limit per file comes from the server settings.';
$string['potential:limit_site'] = 'The size limit per file comes from the site settings.';
$string['potential:meaning'] = 'The theoretical potential is a ceiling set by the settings: it is not the space used, a forecast or the amount that will be submitted. Different settings serve different teaching goals.';
$string['potential:nobody'] = 'Nobody can submit in this assignment at the moment.';
$string['potential:nofiles'] = 'No file can be submitted with the current settings.';
$string['potential:none'] = 'No assignment in this course accepts files at the moment.';
$string['potential:recorded'] = 'In this course, files already submitted in activities add up to {$a->size} (analysis of {$a->date}). This total is not compared assignment by assignment with the potential.';
$string['potential:scope'] = 'This section covers assignments only.';
$string['potential:units_groups'] = '{$a} groups can submit.';
$string['potential:units_groups_one'] = '1 group can submit.';
$string['potential:units_participants'] = '{$a} participants can submit.';
$string['potential:units_participants_one'] = '1 participant can submit.';
$string['potential:unknown'] = 'The potential of this assignment cannot be calculated with the available information.';
$string['potential:unlimited'] = 'No defined ceiling: with unlimited attempts, each attempt can hold up to {$a->size} ({$a->formula}).';
$string['potential:value'] = 'Theoretical potential: {$a->size} ({$a->formula}).';
$string['privacy:metadata'] = 'The CourseCare block does not store any personal data. Its storage overview keeps only aggregated measurements of the files of each course and activity, and links to large course material files (file identifier and recorded size).';
$string['settings:largefilethreshold'] = 'Large-file threshold (MB)';
$string['settings:largefilethreshold_desc'] = 'Files of this size or larger are counted as large files in the course storage overview (1 MB = 1,048,576 bytes). It is a neutral screening value, not a teaching limit and not a judgement about how a file is used.';
$string['settings:reanalysisinterval'] = 'Reanalysis interval';
$string['settings:reanalysisinterval_desc'] = 'How often each course that has the block is analysed again in the background.';
$string['settings:stalenessthreshold'] = 'Out-of-date limit';
$string['settings:stalenessthreshold_desc'] = 'When the last completed analysis is older than this, the block says that the information may be out of date. Use a value longer than the reanalysis interval.';
$string['size:gb'] = 'GB';
$string['size:kb'] = 'KB';
$string['size:mb'] = 'MB';
$string['size:tb'] = 'TB';
$string['suggestion:large:more'] = 'and {$a} more places, listed under Large files.';
$string['suggestion:large:more_one'] = 'and 1 more place, listed under Large files.';
$string['suggestion:large:observed'] = '{$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in:';
$string['suggestion:large:observed_one'] = '1 course material of {$a->threshold} or more, {$a->size}, in:';
$string['suggestion:large:observed_unnamed'] = '{$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in activities listed under Large files.';
$string['suggestion:large:observed_unnamed_one'] = '1 course material of {$a->threshold} or more, {$a->size}, in an activity listed under Large files.';
$string['suggestion:large:option1'] = 'an optimised version of the file that stays readable and accessible;';
$string['suggestion:large:option1_example'] = 'for example, a PDF with images at a resolution suited to the screen, or a video at the resolution it needs.';
$string['suggestion:large:option2'] = 'splitting long content into shorter parts;';
$string['suggestion:large:option3'] = 'a link to where the content is already published.';
$string['suggestion:large:title'] = 'Large materials';
$string['suggestion:large:todo'] = 'When you review the activity, consider whether any of these options suits your material. Not all of them apply to every case:';
$string['suggestion:large:why'] = 'Large files can take longer to open, especially on slow connections or on a phone. Size alone says nothing about the quality or the importance of a material.';
$string['suggestion:observed'] = 'What we noticed';
$string['suggestion:todo'] = 'What you can do, if you wish';
$string['suggestion:why'] = 'Why it may deserve attention';
$string['suggestions:basedon'] = 'These suggestions use the analysis of {$a}.';
$string['suggestions:heading'] = 'Suggestions for your course';
$string['suggestions:none'] = 'There are no suggestions for this course at the moment.';
$string['tagline'] = 'Small choices, a better learning space for everyone.';
$string['task:analysecourse'] = 'Analyse the file storage of a course';
$string['task:queueanalyses'] = 'Queue the file storage analyses of the courses';
