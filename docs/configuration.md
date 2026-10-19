# CourseCare — Configuration (administrators)

## Settings

Site administration > Plugins > Blocks > CourseCare:

| Setting | Default | Meaning |
| --- | --- | --- |
| Large-file threshold (MB) | 50 | Files of this size or larger are large files (1 MB = 1,048,576 bytes, so 50 MB = 52,428,800 bytes). It is a neutral screening value for the whole site, not a teaching limit. A result keeps the threshold it was calculated with; a new value applies from the next analysis. |
| Reanalysis interval | 1 day | How often each course with the block is analysed again. Minimum 1 hour. |
| Out-of-date limit | 3 days | When the last completed analysis is older than this, the block says the information may be out of date. Use a value longer than the reanalysis interval. |
| History retention | 365 days | How long the history points of each course are kept, from 90 days to 5 years. At most one point per course and day, so 5,000 courses keep at most about 1.8 million points with the default. |

## How courses are analysed

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

## Command line

`blocks/coursecare/cli/diagnostics.php` (on Moodle 5.1+, `public/blocks/coursecare/cli/diagnostics.php`) requests, runs and shows analyses. It prints identifiers
(`cmid`) and totals, never file names, activity names, paths or users.

```bash
php blocks/coursecare/cli/diagnostics.php --queue --course=42
php blocks/coursecare/cli/diagnostics.php --queue --all
php blocks/coursecare/cli/diagnostics.php --run --course=42 --json
php blocks/coursecare/cli/diagnostics.php --show --course=42
```

Exit codes: 0 success, 1 invalid options, 2 course missing or without the block, 3 analysis already
running, 4 analysis failed (the previous result was kept).

## Scalability

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

## Permissions

| Capability | Default | Purpose |
| --- | --- | --- |
| `block/coursecare:addinstance` | Copied from `moodle/site:manageblocks` (standard site: Editing teacher, Manager) | Add the block to a course |
| `block/coursecare:view` | Editing teacher, Manager | See the block and the page |

Non-editing teachers, students and guests do not see CourseCare by default. Adjust the defaults with
the standard role system if your site needs to.
