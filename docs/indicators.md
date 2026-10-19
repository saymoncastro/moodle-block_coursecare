# CourseCare — Indicators and methodology

This page defines every number CourseCare shows, exactly as the code calculates it. CourseCare keeps
four kinds of indicator apart and never adds them together:

| Kind | Where | What it is |
| --- | --- | --- |
| Logical storage | Block, page | Sum of the recorded sizes of the course's file records, place by place |
| Unique content | Technical details, history table | Each distinct content counted once in the course |
| Theoretical potential | Potential of assignments | A ceiling set by an assignment's current settings |
| History of measurements | History section | The logical storage, categories and unique content measured on past days |

None of them is the physical disk space used only by the course, a saving, a forecast, a cost, an
environmental impact or a measure of teaching quality.

## 1. Storage diagnostic: what is measured, and what is not

Every number comes from the file records of Moodle (`files` table metadata: context, component,
area, content hash and recorded size). No file content is read and no repository reference is
synchronised. The numbers are **not** the physical disk space used by the course, not space that
only this course uses, and not space that deleting something would free: Moodle stores each
distinct content once for the whole site, and the same content can be used by several courses.
The diagnostic computes no savings, projections or rankings.

The analysed scope is the course context and every context below it (sections, activities,
blocks of the course and of its activities). It excludes directory records, drafts, private files,
other users' areas and transient areas.

| Indicator | Methodology |
| --- | --- |
| A. Logical storage | Sum of the recorded sizes of the records of the main scope. Shown in the block. |
| B. Unique content | Each distinct content (content hash) of the main scope counted once, with its recorded size. The same content may also be counted by other courses. |
| C. By category | Records, logical volume and unique content of Course materials, Activity submissions, Other associated files and Unclassified. The sum of the unique content of the categories can exceed B, because one content can be in more than one category. |
| D. Large files | Records of the main scope with a recorded size at or above the threshold: count, logical volume, distinct contents and their size. Links to at most 1,000 large files are kept, and only for course materials. |
| E. By activity | The same totals per course module (`cmid`). A block inside an activity counts for that activity; the course and its own blocks count as `cmid` 0. |
| F. Freshness | Date of the last completed analysis, date of the last attempt, failure flag and number of consecutive failures. |
| References | Aliases and shortcuts are counted apart and never enter A to E: internal (a file stored in Moodle), synchronised external, and undetermined (only counted, no size). |
| Backups and recycle bin | Course and activity backups stored in the course, and the course recycle bin, form a separate aggregate (records, recorded size, unique content). It is not part of A or B and says nothing about backups kept outside Moodle. |

The classification of areas follows a rule table (the specification has the full matrix):
course summary, sections, File, Folder, Page, Book, Label and the intro of every activity, and
text blocks are **materials**; assignment file and online text submissions and quiz response
attachments are **submissions**; feedback files and comments, grade feedback, forum attachments
and posts, question bank and content bank files are **other associated files**. Areas of any other
plugin are **unclassified**: they are still counted in A and B, and the result is marked as partial
coverage.

## 2. The page "Your course in numbers"

The link of the block opens `blocks/coursecare/overview.php?id=<block instance>`. The page always
shows the course of that block, and only to users who have `block/coursecare:view` on the block
(by default editing teachers and managers of the course). Everyone else gets the standard Moodle
error page, with no data of the diagnostic. The page only reads; it has no actions.

| Section | What it shows |
| --- | --- |
| Opening | The logical volume of the course ("Course files recorded in Moodle") with the date of the calculation, a note when the result may be out of date or the last update failed, and the share of files that could not be classified (partial coverage). |
| Where the files are | The four categories of the course (course materials, files submitted in activities, other associated files, not classified): size and share of the course total, with a short explanation of each. |
| Large files | How many files of the site threshold or more the course has and their size, and the course materials of that size in each activity. The threshold is a screening value set by the site, not a teaching rule. |
| Materials by activity | The ten activities with the most material (name, size and share of all the course materials), then the rest under "Show all activities", plus the course page, sections and blocks. |
| Technical details (collapsed) | Unique content, links to files kept elsewhere, backups and recycle bin recorded in Moodle, files that could not be classified, the threshold used, and what the numbers do not mean. |

Per activity only **course materials** appear, never the files students submitted: submissions
appear only in the total of the course, so the page does not expose how much a group of students
sent in a given activity. Without a result the page shows only the state sentence; a course
without files shows the opening and the technical details.

The numbers are the files Moodle records for the course (logical storage). They are not the disk
space used only by this course (the same file can be shared with other courses) and do not show how
much space deleting files would free. The page makes no recommendation.

Sizes use the rule of Moodle's `display_size()` (powers of 1024, one decimal), with the decimal
separator of the user's language and the unit symbols KB, MB, GB and TB in every language.

Recognised limitations of the page:

- Per activity only the size of the materials is shown, without the number of files: the
  diagnostic does not keep file counts per activity and category.
- Large materials per activity come from the links to large course material files, limited to the
  1,000 largest per course; the totals always include every large file.
- Activity names and visibility are read from the course as it is now, which may have changed since
  the analysis.

## 3. Suggestions

Right after its opening, the page has a section "Suggestions for your course". This version has one
suggestion, **Large materials**. It is shown only when the latest result has at least one course
material at or above the site's large-file threshold (the setting described below; no other setting
is involved). It has three labelled parts:

- **What we noticed**: how many course materials of the threshold or more there are and their total
  size (submissions are never counted here), the three places with most of them, with links, and how
  many other places exist;
- **Why it may deserve attention**: large files can take longer to open, especially on slow
  connections or on a phone; size alone says nothing about the quality or the importance of a
  material;
- **What you can do, if you wish**: options to consider when reviewing the activity, which may not
  apply to every material: an optimised version that stays readable and accessible, splitting long
  content, or a link to where the content is already published.

The suggestion is optional and can be ignored; there is nothing to dismiss and nothing is recorded.
The plugin never acts on files: the links lead to Moodle's own activity and course pages. With a
result and no suggestion, the section says so in one neutral sentence; without a result there is no
section. Other ideas (backups, recycle bin, repeated content, submission limits, file types) are left
for later because the diagnostic does not yet have the data to support them.

## 4. History of measurements

Each successful analysis also keeps one **history point** for the course: the files recorded
(logical storage), the unique content, the four categories (materials, submissions as the course
total, other, not classified), whether the coverage was partial and the version of the calculation,
with the date of the analysis. The point is written together with the current result, so the two
never differ, and an analysis that fails writes nothing. There is at most one point per course and
day (the site's day): a second analysis on the same day replaces it. No file is read again for the
history, and nothing else is estimated or reconstructed: the history starts with version 0.6.0,
whose upgrade turns the current result of each course into its first point, with its real date.

On the "Your course in numbers" page, the section "How the files of your course have changed", after
"Where the files are", shows the date of the latest measurement, one neutral sentence about the
change since the previous measurement (or that this is the first one, or that the way of measuring
changed), a chart of the recorded files by day over the last 90 days (built with Moodle's own charts;
days without an analysis have no bar) and, collapsed, a table with every measurement kept and its
categories, and the limits of the history. An increase or a decrease, on its own, does not mean a problem or an
improvement; the section names no cause, goal or comparison. The block does not change.

Points older than the retention setting (365 days by default, from 90 days to 5 years) are removed by
the hourly scheduled task, at most 10,000 per run. When a course stops being eligible (deleted, or its
last CourseCare block removed), its history is removed with its current result; a course restored as
a new course, or copied, starts without history.

## 5. Potential of assignments

Before the technical details, the page has a section "Potential of assignments". For each
assignment of the course that accepts file submissions, in the order of the course, it says what the
current settings allow: how many files per participant (or per group), of what size each, which level
sets that size (the assignment, the site default for assignments, the course, the site or the
server), how many attempts, and how many participants or groups can submit. From these it gives the
**theoretical potential**: participants (or groups) × attempts × files per submission × size limit
per file. It is calculated each time the page is shown, from the settings of that moment; nothing is
stored and the background analysis does not change.

The theoretical potential is a ceiling set by the settings. It is not the space used, a forecast, the
amount that will be submitted, a saving or a waste, and different settings serve different teaching
goals. It is never added up into a total of the course, never compared assignment by assignment with
what is recorded, and assignments are not ranked. The files already submitted appear only as the
total of the course ("Files submitted in activities", from the latest analysis).

The rule follows what Moodle applies (verified in the code of Moodle 4.5 and 5.3):

- the size limit per file is the smallest of the server upload limits, the site and course limits
  (when set) and the size of the assignment (or, when it is "site default", the default of the file
  submission plugin);
- one attempt when the assignment never opens another (maximum of one attempt, or reopening "until
  pass" without a grade to pass in its grade item); with unlimited attempts there is no ceiling, and
  the section shows what one attempt can hold;
- participants are the users with an active enrolment who can submit in the assignment (overrides in
  the assignment count); with group submission, the groups as the assignment counts them (only those
  of the grouping, when set), plus the default group when someone without a single group may submit;
- assignments are listed only when Moodle offers file submissions in them (enabled in the assignment
  and the file submission plugin not disabled on the site);
- with no file allowed, nobody who can submit, or a factor that cannot be determined, there is no
  number and the section says why.

A collapsed part explains the calculation and its limits: due dates and access restrictions are not
considered; people allowed to ignore file size limits have no ceiling; files in online text and
feedback files are not included; the server limits are those of the request that showed the page;
each attempt keeps its own files and copies of identical content are stored only once. Only
assignments are covered; the section makes no suggestion and the block does not show it.

## 6. Limitations of the diagnostic

- Backups saved outside Moodle (for example automated backups written to a directory of the server)
  are not counted; only backups stored in the course and its activities are.
- References: the size of an external reference is the size recorded at the last synchronisation;
  undetermined references are only counted. No reference is synchronised or downloaded.
- Forum: teachers' materials and students' posts use the same areas and cannot be told apart, so
  all forum files are "other associated files".
- Coverage: areas of plugins outside the rule table are unclassified and mark the result as partial.
- Large-file links exist only for course materials and are limited to 1,000 per course; the totals
  always include every large file.
