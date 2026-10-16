# Changelog

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
