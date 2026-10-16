# CourseCare (block_coursecare)

CourseCare is an educational assistant for Moodle that promotes the conscious use of storage
resources in courses. In Brazilian Portuguese it is called **AVA Consciente**. It guides and
supports teachers; it never monitors, blocks or changes their courses.

This first version (0.1.0, alpha) is the foundation of the plugin. It shows a short welcoming
message announcing that guidance will be available soon. It does **not** analyse files or storage
yet, and it shows no indicators.

## Compatibility

- Moodle 4.5 (LTS) is the only version validated and declared (`$plugin->supported = [405, 405]`).
- PHP 8.1 to 8.3, as supported by Moodle 4.5.
- Any database supported by Moodle; no plugin tables.
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

| Capability | Default (archetypes) | Purpose |
| --- | --- | --- |
| `block/coursecare:addinstance` | Editing teacher, Manager | Add the block to a course |
| `block/coursecare:view` | Editing teacher, Manager | See the block content |

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

## Privacy and external services

The block stores no personal data (Privacy API null provider), reads no user data and makes no
network requests. It has no external dependencies.

## Languages

The package contains only English. A Brazilian Portuguese translation is maintained in this
repository and will be contributed to AMOS (the Moodle translation system) after publication in
the Moodle Plugins directory, reaching sites through the official `pt_br` language pack. See
[CONTRIBUTING.md](CONTRIBUTING.md).

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
