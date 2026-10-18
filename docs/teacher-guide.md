# CourseCare — Teacher guide

CourseCare is a small educational assistant in your Moodle course. It helps you understand the files
your course keeps in Moodle, so you can make informed choices about your materials if you wish. It
never changes, deletes or blocks anything, it does not evaluate your teaching, and you can ignore it.

Your site administrator decides whether CourseCare is available. Teachers who can edit the course
(and managers) see it; students do not.

## 1. The block

Add the block "CourseCare" to the main page of your course (Turn editing on > Add a block). After the
first analysis, which runs in the background and can take a little while, the block shows:

- **Course files recorded in Moodle**, a size such as `8.0 MB`, and **Calculated on** a date;
- a link, **Learn more about the files in your course**, to the page described below;
- sometimes one sentence, **There is a suggestion about the files of your course.**

If the block says *"The file overview for this course is being prepared"*, the first analysis has not
finished yet. *"This information may be out of date"* means the latest calculation is older than your
site expects; the number shown is still the last one measured.

## 2. "Your course in numbers"

The page linked from the block has these parts, from top to bottom.

### Opening: how much your course keeps

The size of all the files Moodle records for your course, with the date of the calculation.

**Example**: your course has a 2 MB PDF used in two activities. The course total counts it twice
(4 MB), because it is recorded in two places. That is **logical storage**: what the course has,
place by place.

### Suggestions for your course

When some course materials are at or above the size your site considers "large" (50 MB by default),
a card shows what was noticed, why it may deserve attention and what you could do, **if you wish**.
Large files are not a problem in themselves: a long video may be exactly what your students need. The
options (an optimised version that stays readable, splitting long content, a link to where the content
is already published) are possibilities, never instructions.

### Where the files are

The total divided into four kinds of files:

- **Course materials**: what you and other editors published (files, folders, pages, books, labels,
  activity descriptions);
- **Files submitted in activities**: what participants sent in assignments and quizzes, shown only as
  the total of the course, never per activity or per student;
- **Other associated files**: feedback, forum attachments, question and content banks;
- **Not classified**: areas CourseCare does not recognise yet; they still count in the total.

### How the files of your course have changed

Each day the course is analysed, CourseCare keeps one measurement. This section shows the latest
measurement, one sentence about the change since the previous one, a chart of the last 90 days and,
collapsed, a table with every measurement kept.

**Example**: "The volume of the files recorded in this course increased by 182.8 KB since the analysis
of 16 October 2026 (from 7.8 MB to 8.0 MB)."

An increase or a decrease, on its own, does **not** mean a problem or an improvement: courses change as
teaching goes on (new materials, a new term, submissions arriving). CourseCare shows what changed in
the recorded files, not why. Days without an analysis have no value; nothing is estimated for them.

### Materials by activity

The activities with the most course materials, with their size and share. Only materials appear here,
never what students submitted.

### Potential of assignments

For each assignment that accepts files, what its **current settings** allow: how many files, of what
size, how many attempts, for how many participants or groups, and the **theoretical potential**.

**Example**: 30 participants, 3 files of up to 10 MB each, 1 attempt → theoretical potential
900 MB (30 × 3 × 10 MB).

The theoretical potential is a ceiling set by the settings. It is not the space used, not a forecast
and not the amount that will be submitted. Different settings serve different teaching goals, and
CourseCare never says a setting is right or wrong.

### Technical details (collapsed)

Unique content, links to files kept elsewhere, backups and recycle bin recorded in Moodle, and what
the numbers do not mean.

**Logical storage and unique content**: in the PDF example above, logical storage counts the PDF twice
(4 MB), unique content counts it once (2 MB). Neither is the disk space your course alone uses: Moodle
stores each distinct file once for the whole site, so the same file can serve several courses.

## 3. What CourseCare does not measure

- The disk space used only by your course, or what deleting files would free.
- Savings, costs, environmental impact or the quality of your teaching.
- Files outside Moodle (for example, videos hosted elsewhere and only linked).
- Anything about individual students.

## 4. Questions

- *Why is my number different from yesterday?* Files were added, changed or removed, or submissions
  arrived; see "How the files of your course have changed".
- *Can I refresh the numbers now?* Not from the page; your site analyses courses regularly (once a day
  by default).
- *Who else sees this?* Editing teachers and managers of the course, by default.

See also the [indicator definitions](indicators.md).
