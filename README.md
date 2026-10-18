# CourseCare (block_coursecare)

CourseCare is an educational assistant for Moodle that promotes the conscious use of storage
resources in courses. In Brazilian Portuguese it is called **AVA Consciente**. It guides and
supports teachers; it never monitors, blocks or changes their courses.

Version 0.2.0 (alpha) added a **course storage diagnostic**: a background analysis of the file
records of each course that has the block. Version 0.3.0 (alpha) adds the **teacher experience**:
the block shows the logical volume of the course files with the date of the calculation and links
to a page, "Your course in numbers", where the teacher sees where the files are. Nothing is
changed, deleted, downloaded or synchronised.

## Compatibility

- Moodle 4.5 (LTS) is the only version validated and declared (`$plugin->supported = [405, 405]`).
- PHP 8.1 to 8.3, as supported by Moodle 4.5.
- Any database supported by Moodle. Tested with PostgreSQL 17 and MariaDB 11.4. Four plugin tables
  hold the aggregated results.
- Tested with the core Boost and Classic themes; no theme-specific dependency.

## Installation

Use the release ZIP (`block_coursecare-<version>.zip`, root folder `coursecare/`), not the archives
GitHub generates automatically:

1. Site administration > Plugins > Install plugins, upload the ZIP; or extract it into
   `<moodle>/blocks/` so that the plugin is in `<moodle>/blocks/coursecare`.
2. Complete the installation from Site administration > Notifications (or `admin/cli/upgrade.php`).

No core file is modified. The block can be disabled and re-enabled in Site administration >
Plugins > Blocks > Manage blocks, and uninstalled from the same page.

## Where the block appears

- It can be added only to the **course main page**, once per course. It is not offered on the
  Dashboard, the site home page, section pages or activity pages.
- If the native "Display on page types" setting loads it on other pages (sections, activities),
  nothing is rendered there, not even an empty header, in any mode.

## Permissions

| Capability | Default at installation | Purpose |
| --- | --- | --- |
| `block/coursecare:addinstance` | Copied from `moodle/site:manageblocks` on your site (on a standard site: Editing teacher, Manager) | Add the block to a course |
| `block/coursecare:view` | Editing teacher, Manager (archetypes) | See the block content |

`addinstance` follows the core convention for blocks: at installation Moodle clones the current role
permissions of `moodle/site:manageblocks`, so any local change to who can manage blocks is carried
over. Review it after installation if your site grants block management to other roles.

Non-editing teachers, students, guests and visitors do not see the block by default. Configuring,
moving, hiding and deleting the block follow the native block permissions. Site administrators can
adjust every default through the standard role system; the plugin never checks role names.

## Known limitations

- On the course main page, a user who can edit the block but has `block/coursecare:view` removed by a
  local override still sees the block header and the native editing controls in editing mode (but
  not the message). This is the core block management behaviour; without the controls the block
  could not be removed or configured.
- One block per course is guaranteed for user flows (adding through the interface, changing the
  course format, restoring a backup). Blocks added through the administrator's default blocks
  configuration (`$CFG->defaultblocks_override`) or created by code outside the interface are not
  covered by this guarantee.

## Course storage diagnostic

### What is measured, and what is not

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

### What the block shows

Below the welcome message, the block shows the state of the diagnostic:

| State | The block shows |
| --- | --- |
| Never analysed (or failed without a result) | The file overview for this course is not available yet. |
| Queued or running, without a previous result | The file overview for this course is being prepared. |
| Complete | "Course files recorded in Moodle", the size in large type, "Calculated on *date*." and a link "Learn more about the files in your course". |
| No files | No files were found in this course's materials and activities. Calculated on *date*. And the link. |
| Older than the out-of-date limit | As complete, plus: This information may be out of date. |
| Last update failed | The previous result, plus: The latest update could not be completed. |

When a new analysis is pending, the block keeps showing the previous result. The block never
reads the `files` table while rendering; it reads one row of the plugin's own table. Without a
result there is no link and no number.

### The page "Your course in numbers"

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

### When courses are analysed

- Only courses that have the block are analysed. Removing the last block of a course, deleting the
  course or uninstalling the plugin removes its diagnostic. Disabling the block type in Manage
  blocks does not remove anything.
- Adding the block requests a first analysis. An hourly scheduled task
  (`\block_coursecare\task\queue_analyses`, at a random minute) requests the analysis of at most
  500 due courses per run: courses without a result, results older than the reanalysis interval,
  results made with an older methodology, and failed analyses after a waiting time that doubles
  from 1 h up to 24 h.
- Each course is analysed by an ad hoc task (`\block_coursecare\task\analyse_course`), at most
  two at a time, with a lock per course. A failure keeps the previous result.
- There is no refresh button for teachers in this version.

### Settings

Site administration > Plugins > Blocks > CourseCare:

| Setting | Default | Meaning |
| --- | --- | --- |
| Large-file threshold (MB) | 50 | Files of this size or larger are large files (1 MB = 1,048,576 bytes, so 50 MB = 52,428,800 bytes). It is a neutral screening value for the whole site, not a teaching limit. A result keeps the threshold it was calculated with; a new value applies from the next analysis. |
| Reanalysis interval | 1 day | How often each course with the block is analysed again. Minimum 1 hour. |
| Out-of-date limit | 3 days | When the last completed analysis is older than this, the block says the information may be out of date. Use a value longer than the reanalysis interval. |

### Command line (administrators)

`blocks/coursecare/cli/diagnostics.php` requests, runs and shows analyses. It prints identifiers
(`cmid`) and totals, never file names, activity names, paths or users.

```bash
php blocks/coursecare/cli/diagnostics.php --queue --course=42
php blocks/coursecare/cli/diagnostics.php --queue --all
php blocks/coursecare/cli/diagnostics.php --run --course=42 --json
php blocks/coursecare/cli/diagnostics.php --show --course=42
```

Exit codes: 0 success, 1 invalid options, 2 course missing or without the block, 3 analysis already
running, 4 analysis failed (the previous result was kept).

### Scalability

Measured on Moodle 4.5.15 with PostgreSQL 17 and MariaDB 11.4 in their default configuration, on a
site with 5,001 courses and 5.7 million file records (details in
`specs/002-course-storage-diagnostics/evidence/benchmark.md`):

| File records in the course | PostgreSQL | MariaDB | PHP peak memory |
| --- | --- | --- | --- |
| 5,000 | 0.04 s | 0.07 s | 14 MB |
| 50,000 | 0.13 s | 0.57 s | 15 MB |
| 200,000 | 0.52 s | 3.9 s | 16 MB |

The analysis reads the course with indexed aggregate queries (`context.path` and `files.contextid`)
and keeps only aggregates in memory, so its memory stays almost flat as the course grows. Figures are
for one analysis with a cold database cache; they depend on the server.

With the default schedule (cron every minute, the scheduled task hourly, at most 500 courses queued
per run and two analyses at a time), a site with 5,001 eligible courses had every course analysed in
about 9 h 20 min on both databases. The batch of 500 per hour, not the queries, sets that pace: the
nominal ceiling is about 12,000 courses per day, a limit of the configuration rather than a measured
capacity at that scale.

### Limitations

- Backups saved outside Moodle (for example automated backups written to a directory of the server)
  are not counted; only backups stored in the course and its activities are.
- References: the size of an external reference is the size recorded at the last synchronisation;
  undetermined references are only counted. No reference is synchronised or downloaded.
- Forum: teachers' materials and students' posts use the same areas and cannot be told apart, so
  all forum files are "other associated files".
- Coverage: areas of plugins outside the rule table are unclassified and mark the result as partial.
- Large-file links exist only for course materials and are limited to 1,000 per course; the totals
  always include every large file.

## Privacy and external services

The plugin stores no personal data (Privacy API null provider): its tables hold aggregated numbers
per course, per category and per activity, and links to large course material files (file id,
`cmid` and recorded size). It stores no user id, file name, path or content hash, reads no user
data beyond file metadata and makes no network requests. It has no external dependencies.

## Languages

The package contains only English. A Brazilian Portuguese translation is maintained in this
repository and will be contributed to AMOS (the Moodle translation system) after publication in
the Moodle Plugins directory, reaching sites through the official `pt_br` language pack. See
[CONTRIBUTING.md](CONTRIBUTING.md).

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
