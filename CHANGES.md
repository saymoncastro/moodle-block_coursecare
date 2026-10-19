# Changelog

## 0.8.0 BETA (unreleased)

- Educational experience: the block shows the identity of the plugin, an educational state ("All good
  here!" only when the latest analysis is complete, current and fully classified and no suggestion
  applies; "An idea for your course" when a suggestion applies), the volume of the course files with
  the date, discreet notes (update failed, may be out of date, partly classified) and one action.
- The "Your course in numbers" page opens with the plugin identity, its tagline, an original
  illustration and the volume, and organises its content in two accessible tabs, "Suggestions for you"
  and "Details", with links to sections, browser history, printing and use without JavaScript.
- The large materials suggestion card answers what was noticed, why it may deserve attention, what you
  can do if you wish, and where the related activities are.
- No new data, setting, capability, recommendation or indicator; no database change.

## 0.7.0 BETA (unreleased)

- Moodle 5.3 LTS supported alongside Moodle 4.5 LTS (`supported` = [405, 503]), verified on PHP 8.3
  and 8.4 with PostgreSQL 17 and MariaDB 11.4; on 5.3 the plugin lives in `public/blocks/coursecare`.
- Text on the block's green panel keeps its contrast when Moodle 5.3 shows the block in the block
  drawer.
- The privacy declaration also describes the history.
- International documentation (installation, upgrading, configuration, teacher guide, indicators,
  privacy, troubleshooting), security policy and contribution guide; the README describes the product
  for the public.
- Maturity BETA. No new feature, setting, capability, table or data; no database change.

## 0.6.0 (unreleased)

- History of the files of a course: each successful analysis keeps one point per course and day
  (files recorded, unique content, the four categories, partial coverage, method version), written
  together with the current result; failures write nothing; no file is read again.
- The "Your course in numbers" page shows "How the files of your course have changed": the latest
  measurement, a neutral sentence about the change in volume, a chart of the last 90 days with
  Moodle's own charts (no bar on days without an analysis) and, collapsed, the table of every
  measurement kept and the limits.
- New setting "History retention" (365 days by default, 90 days to 5 years); old points are removed
  by the hourly task; the history is removed with the diagnostic when a course stops being eligible.
- The upgrade turns the current result of each course into its first point; nothing older is created.
- No new capability, personal data, recommendation or change to the block.

## 0.5.0 (unreleased)

- Potential of assignments: on the "Your course in numbers" page, for each assignment that accepts
  files, what its current settings allow (files per submission, size limit per file and where it
  comes from, attempts, participants or groups who can submit) and the theoretical potential, or why
  there is no number. Calculated when the page is shown; nothing is stored.
- No total of the course, no ranking, no suggestion, and no submission data per assignment; the block
  is unchanged.

## 0.4.0 (unreleased)

- First suggestion, "Large materials": on the "Your course in numbers" page, a section of
  suggestions with what was noticed (course materials at or above the large-file threshold, never
  submissions), why it may deserve attention and options the teacher may consider; a neutral
  sentence when no suggestion applies.
- The block announces a suggestion with one sentence, without any number, and links to it.
- No new setting, data, analysis or capability; the plugin never acts on files.

## 0.3.0 (unreleased)

- Teacher experience: the block links to a new page, "Your course in numbers", with the logical
  storage of the course, its categories, the materials of each activity, the large files and the
  technical details (collapsed), for every state of the diagnostic.
- Student submissions appear only in the total of the course; per activity, only the materials of
  the course are shown.
- The block no longer announces upcoming guidance.
- The block shows the volume of the course in large type, with its label, the date and the link.
- Sizes on the block and the page use the decimal separator of the user's language and the unit
  symbols KB, MB, GB and TB in every language (the core `pt_br` pack writes "Kb" and "Mb").
- Approved visual direction (soft greens and neutrals, rounded corners, the sprout), scoped to the
  block and the page; keyboard access, headings in order and every number in text.

## 0.2.0 (unreleased)

- Course storage diagnostic: background analysis of the file records of each course that has the
  block, with logical storage, unique content, categories (materials, submissions, other,
  unclassified), large files, totals per activity, references and a separate backup and recycle
  bin aggregate. Only file metadata is read; nothing is changed, downloaded or synchronised.
- The block shows one sentence with the logical volume of the course and the date of the
  calculation, or the state of the diagnostic (not available, being prepared, no files, may be out
  of date, last update failed).
- Hourly scheduled task that requests at most 500 due analyses per run, and an ad hoc task per
  course with a lock and at most two analyses at a time; failures keep the previous result.
- Settings: large-file threshold (50 MB), reanalysis interval (1 day), out-of-date limit (3 days).
- Administrator command line tool `cli/diagnostics.php` (queue, run, show, JSON output).
- The diagnostic of a course is removed with its last block, with the course and on uninstall.

## 0.1.0 (unreleased)

- Foundation of the CourseCare block for Moodle 4.5: installable block, own capabilities for adding
  and viewing, course main page only, initial educational message with a decorative icon, English
  strings with a separately maintained pt_BR translation, and automated translation parity and
  package checks.
