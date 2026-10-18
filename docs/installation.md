# CourseCare — Installation

## Requirements

| Moodle | PHP | Databases tested |
| --- | --- | --- |
| 4.5 LTS | 8.1 to 8.3 | PostgreSQL 17, MariaDB 11.4 |
| 5.3 LTS | 8.3 to 8.4 | PostgreSQL 17, MariaDB 11.4 |

Any database supported by your Moodle version should work; the plugin uses only Moodle's database API.
The site needs a working **cron** (scheduled and ad hoc tasks): without it no course is analysed and the
block keeps saying the overview is being prepared. No other software, service or library is needed.

## Install from the ZIP (recommended)

Use the release ZIP `block_coursecare-<version>.zip` (root folder `coursecare/`), not the archives GitHub
generates automatically from the repository.

1. Site administration > Plugins > Install plugins, and upload the ZIP; or extract it into the blocks
   folder of your Moodle code: `<moodle>/blocks/coursecare` (Moodle 4.5) or
   `<moodle>/public/blocks/coursecare` (Moodle 5.1 and later).
2. Complete the installation in Site administration > Notifications (or run `admin/cli/upgrade.php`).
3. Review the settings in Site administration > Plugins > Blocks > CourseCare
   ([configuration guide](configuration.md)).

## Install from the source code

Clone or copy the repository into the same folder as above, then step 2. The repository also contains
development tools, specifications and the Brazilian Portuguese translation source; Moodle ignores them,
and none is needed in production.

## What the installation creates

- Five tables (`block_coursecare_diag`, `_cat`, `_act`, `_large`, `_hist`) holding aggregated results
  only.
- Four settings (large-file threshold, reanalysis interval, out-of-date limit, history retention).
- Two capabilities (`block/coursecare:addinstance`, `block/coursecare:view`).
- One scheduled task (`\block_coursecare\task\queue_analyses`, hourly) and ad hoc analysis tasks.
- No core file is modified.

## Where the block can be added

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

## Uninstalling

Site administration > Plugins > Plugins overview > CourseCare > Uninstall removes the plugin's tables
(every diagnostic and the whole history), its settings, its tasks and its capabilities. Nothing outside
the plugin is touched (course files are never changed by CourseCare). Disabling the block type in
Manage blocks hides it without removing data.
