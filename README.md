# CourseCare (block_coursecare)

CourseCare is a block for Moodle courses that helps teachers understand the files their course keeps
in Moodle, so they can make informed choices about their materials if they wish. In Brazilian
Portuguese it is called **AVA Consciente**.

CourseCare never changes, deletes, downloads or blocks anything, does not evaluate teaching, and can be
ignored. It is meant for teachers who edit a course and for the site administrators who make it
available.

Status: **0.9.0 BETA**.

## Features

- **Block** on the course main page: a short educational welcome ("An idea for your course" when a
  suggestion applies; "All good here!" only when the latest analysis is complete, recent and fully
  classified and finds nothing to suggest), the size of the files Moodle records for the course with the
  date of the calculation, discreet notes, and one button to the course's page.
- **"Your course in numbers"**: the CourseCare identity and two accessible tabs, "Suggestions for you" and
  "Details" (they also work without JavaScript and when printed). The details: where the files are
  (course materials, files submitted in activities, other associated files, not classified), the
  materials of each activity, large files, and technical details (unique content, references, backups
  and recycle bin).
- **Suggestion about large materials**: what was noticed, why it may deserve attention and options the
  teacher may consider; never an instruction.
- **Potential of assignments**: what the current settings of each assignment that accepts files allow
  (a theoretical ceiling, not a forecast), with a collapsed guide to read and review those settings in
  Moodle, without any suggested value.
- **Suggestion about attempts**: when an assignment with file submissions allows new attempts without a
  maximum, a card explains it and invites the teacher to consider it, if they wish.
- **History**: one measurement per course and day, a chart of the last 90 days and the full table.
- Background analysis of file **metadata** only (never file contents), by Moodle's own scheduled and ad
  hoc tasks; a command-line tool for administrators.

## Compatibility

Verified for this release (CI and installation, upgrade and uninstall checks):

| Moodle | PHP | Databases | Themes |
| --- | --- | --- | --- |
| 4.5 LTS | 8.1, 8.3 | PostgreSQL 17, MariaDB 11.4 | Boost, Classic |
| 5.3 LTS | 8.3, 8.4 | PostgreSQL 17, MariaDB 11.4 | Boost (Classic is no longer part of Moodle 5.3) |

`$plugin->supported = [405, 503]`. Other Moodle versions are not tested and not declared. The plugin
uses only Moodle's database API, so other databases supported by Moodle are expected to work but were
not tested.

## Installation

Use the release ZIP `block_coursecare-<version>.zip` (root folder `coursecare/`): upload it in Site
administration > Plugins > Install plugins, or extract it into `blocks/coursecare` (Moodle 4.5) or
`public/blocks/coursecare` (Moodle 5.3), then complete the installation in Site administration >
Notifications. The site needs a working cron. No core file is modified.

## Documentation

- [Installation](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/installation.md)
  and [upgrading](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/upgrading.md)
- [Configuration](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/configuration.md)
  (settings, analysis schedule, permissions)
- [Teacher guide](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/teacher-guide.md)
  ([Guia do professor, pt_BR](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/pt_br/guia-do-professor.md))
- [Indicator definitions](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/indicators.md)
- [Privacy and data handling](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/privacy.md)
- [Troubleshooting](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/docs/troubleshooting.md)
- [Changelog](CHANGES.md)

## What CourseCare does not measure

The disk space used only by one course (Moodle stores each distinct file once for the whole site), what
deleting files would free, savings, costs, environmental impact, the quality of teaching, files outside
Moodle, or anything about individual students.

## Known limitations

- Backups written outside Moodle (for example automated backups to a server directory) are not counted.
- External references count the size recorded at their last synchronisation; nothing is synchronised.
- Forum files of teachers and students share the same areas, so all forum files are "other associated
  files".
- Areas of plugins CourseCare does not recognise are "not classified" and mark the result as partial.
- Links to large files exist only for course materials, up to 1,000 per course; totals include every
  file.

## Privacy

CourseCare stores no personal data (Privacy API null provider): only aggregated numbers per course,
category and activity, links to large course material files (file id, activity and recorded size) and
one row of course totals per course and day for the history. It makes no network request and has no
external dependency.

## Languages

The package contains English only. A Brazilian Portuguese translation is maintained in the repository
and will be contributed to AMOS, the Moodle translation system, after approval in the Moodle
Marketplace.

## Support

- Questions and bug reports: [GitHub Issues](https://github.com/saymoncastro/moodle-block_coursecare/issues)
- Security issues: please report privately, as described in
  [SECURITY.md](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/SECURITY.md)
- Contributing: [CONTRIBUTING.md](https://github.com/saymoncastro/moodle-block_coursecare/blob/main/CONTRIBUTING.md)

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
