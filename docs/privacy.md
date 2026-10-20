# CourseCare — Privacy and data handling

CourseCare stores **no personal data** and declares this to Moodle's Privacy API (null provider). It makes
no network request and sends nothing outside your site.

## What CourseCare reads

- File **metadata** of each course with the block (context, component, area, content hash, recorded
  size), during the background analysis. It never opens file contents and never synchronises external
  files.
- When the "Your course in numbers" page is opened: the plugin's own tables, the course structure, and,
  for the potential of assignments, the assignments' settings, the number of participants or groups who
  can submit, and the site's upload limits.
- During the background analysis (since 0.9.0), for assignments whose maximum of attempts is unlimited:
  the same settings and counts as the page, to find the suggestion about attempts. Only the identifiers
  of the assignments found are stored.

## What CourseCare stores

| Table | Content | Personal data |
| --- | --- | --- |
| `block_coursecare_diag` | One row per course: totals, dates, state of the last analysis and, since 0.9.0, the identifiers of the assignments whose settings were the basis of a suggestion | No |
| `block_coursecare_cat` | Totals per category of the course | No |
| `block_coursecare_act` | Totals per activity (`cmid`) | No |
| `block_coursecare_large` | Up to 1,000 links to large **course material** files: file identifier, `cmid`, recorded size | No (materials, never submissions) |
| `block_coursecare_hist` | One row per course and day: the course totals of that day | No |

No user id, name, file name, path or content hash is stored.

## Retention and removal

- History: kept for the retention set by the administrator (365 days by default, 90 days to 5 years) and
  removed by the hourly task.
- A course's diagnostic and history are removed when the course is deleted or its last CourseCare block
  is removed.
- Uninstalling the plugin removes all its tables and settings.

## Who sees what

Editing teachers and managers of a course (by default) see that course's numbers, and only that course's.
Files submitted by students appear only as the **total of the course**, never per activity or per
student. The per-activity totals that the analysis stores (including submitted files) are shown only to
site administrators, in the command-line tool (`cli/diagnostics.php --show`).

### Small classes

In a course with very few students, the course total of submitted files (and its evolution in the
history) can correspond to the submissions of one or two people. This is information the same teachers
already see, in more detail, in Moodle's own assignment and quiz pages, and CourseCare never names anyone.
Sites with stricter rules can restrict `block/coursecare:view`.

## Uninstall and data subject requests

Because no personal data is stored, there is nothing to export or delete for a data subject request.
