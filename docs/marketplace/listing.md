# Moodle Marketplace listing — CourseCare

Prepared text for the Marketplace product page. Nothing here has been submitted. Check every field
against the official listing guide (which requires a Moodle Atlassian login) before submission.

## Name

CourseCare

## Plugin type and component

Block — `block_coursecare`

## Short description

A course block that helps teachers understand the files their course keeps in Moodle, with plain
numbers, a history and optional suggestions. It never changes or deletes anything.

## Long description

CourseCare adds a small block to the main page of a course. It welcomes the teacher with a short
educational state ("An idea for your course" when a suggestion applies, "All good here!" only when the
latest analysis is complete, recent and finds nothing to suggest), shows the size of the files Moodle
records for the course with the date of the calculation, and leads to a page, "Your course in numbers",
organised in two accessible tabs, "Suggestions for you" and "Details", where teachers who edit the
course can see:

- where the files are: course materials, files submitted in activities (only as the total of the
  course, never per student), other associated files and files not classified;
- the materials of each activity and the large files;
- an optional suggestion about large course materials, with what was noticed, why it may deserve
  attention and options the teacher may consider;
- the potential of each assignment that accepts files: what its current settings allow, as a
  theoretical ceiling rather than a forecast;
- how the files of the course have changed: one measurement per day, a chart of the last 90 days and
  the full table;
- technical details: unique content, references to files kept elsewhere, backups and the recycle bin.

The analysis runs in the background with Moodle's own scheduled and ad hoc tasks and reads only file
metadata, never file contents. CourseCare never changes, deletes, downloads or blocks files, does not
evaluate teaching, and stores no personal data. An increase or a decrease in the numbers is not
presented as a problem or an improvement.

Administrators choose the large-file threshold, how often courses are analysed, when a result is
shown as out of date and how long the history is kept. By default, editing teachers and managers see
the block; students do not.

In Brazilian Portuguese the plugin is called "AVA Consciente"; the translation will be contributed to
AMOS after approval.

## What it does not do

It does not measure the disk space used only by one course (Moodle stores each distinct file once for
the whole site), what deleting files would free, savings, costs, environmental impact or the quality of
teaching, files outside Moodle, or anything about individual students.

## Supported Moodle versions

4.5 LTS and 5.3 LTS (`$plugin->supported = [405, 503]`), verified on PHP 8.1 and 8.3 (4.5) and PHP 8.3
and 8.4 (5.3), with PostgreSQL 17 and MariaDB 11.4.

## Maturity and version

0.8.0 BETA.

## Links

- Source code: https://github.com/saymoncastro/moodle-block_coursecare
- Bug tracker: https://github.com/saymoncastro/moodle-block_coursecare/issues
- Documentation: https://github.com/saymoncastro/moodle-block_coursecare/tree/main/docs
- Security policy: https://github.com/saymoncastro/moodle-block_coursecare/blob/main/SECURITY.md

## Known limitations

- Backups written outside Moodle are not counted.
- External references count the size recorded at their last synchronisation.
- Forum files of teachers and students cannot be told apart.
- Areas of plugins CourseCare does not recognise are counted as "not classified".

## Support

Community support through GitHub Issues, on a best-effort basis. No commercial support.

## License

GNU GPL v3 or later.

## Screenshots

Real screenshots of 0.8.0 (approved by the product owner on 2026-10-19) on demonstration sites with
fictitious courses and people, interface in English, in [`screenshots/`](screenshots/). The fictitious
course content is in Portuguese ("Biologia celular"). They show no personal data.

| File | Shows |
| --- | --- |
| `01-block.png` | The block with a suggestion, "An idea for your course" (Moodle 5.3, Boost) |
| `02-page.png` | "Your course in numbers", tab "Suggestions for you" (Moodle 5.3, Boost) |
| `03-details.png` | Tab "Details": where the files are, large files, history, materials, potential (Moodle 5.3) |
| `04-block-allgood.png` | The block when the latest analysis finds nothing to suggest, "All good here!" (Moodle 5.3) |
| `05-mobile.png` | The page on a phone-sized screen (Moodle 5.3) |
| `06-moodle45.png` | The page on Moodle 4.5 (Boost) |

## Claims checked

The text above makes no claim of storage reduction, savings, carbon or environmental impact,
optimisation, teaching evaluation or official endorsement by Moodle. "Moodle" appears only to name the
platform the plugin works with.
