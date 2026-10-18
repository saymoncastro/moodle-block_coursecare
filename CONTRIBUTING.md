# Contributing to CourseCare

Thank you for helping CourseCare (AVA Consciente in Brazilian Portuguese). The project follows the
constitution in `.specify/memory/constitution.md` and the Spec Kit workflow (`specs/`). Every
change starts from a specification and must pass the quality gates below.

## Development environment

Use the reproducible Moodle environment described in
[`specs/001-plugin-foundation/quickstart.md`](specs/001-plugin-foundation/quickstart.md)
(moodle-docker with fictitious demo data). Never use real student data.

## Quality gates

- `moodle-plugin-ci`: phplint, phpcs and phpdoc without warnings, validate, savepoints, mustache, grunt.
- PHPUnit and Behat green, including the automated accessibility checks.
- Translation parity and package checks:

```bash
php tools/i18n/tests/run.php && php tools/i18n/check.php
```

```bash
tools/package/build.sh && php tools/package/check.php build/block_coursecare-*.zip
```

## Strings and translations

- English is the base language: `lang/en/block_coursecare.php` contains every key, in alphabetical
  order, with no `MOODLE_INTERNAL` check and no comments between strings.
- Never write interface text in code or templates. Use the String API (`get_string`, `{{#str}}`).
- Never build sentences by concatenating strings. Each message is one complete string, with `{$a}`
  parameters when needed, so a partial translation never mixes languages in one sentence.
- The officially maintained Brazilian Portuguese translation lives in `translations/pt_br/`, outside
  the distributable package. Keep the same keys, order and `{$a}` parameters as English, and use
  generic Brazilian Portuguese terms, without references to any institution.

### Installing the development translation (development and staging only)

1. Install the official `pt_br` language pack (Site administration > Language > Language packs). If you
   extract the official ZIP into `<dataroot>/lang/` by hand instead, purge all caches before step 2;
   otherwise the import reports success but the check-in silently writes nothing.
2. Import the project translation with the native language customisation tool:

```bash
php admin/tool/customlang/cli/import.php --lang=pt_br --source=/path/to/moodle/blocks/coursecare/translations/pt_br --checkin
```

Local customisations take precedence over the official language pack. Do **not** use this procedure
on production sites to obtain the CourseCare translation.

### Public distribution

The package contains only English. After approval in the Moodle Marketplace, the Brazilian Portuguese
translation is contributed to AMOS and reaches sites through the official `pt_br` language pack.

## Supported versions and compatibility policy

- Each release declares the Moodle versions it was tested on in `version.php` (`supported`) and in the
  README. Version 0.7.0 declares Moodle 4.5 LTS and 5.3 LTS.
- The project follows Moodle LTS releases: a new LTS is added after it passes the full CI matrix and an
  installation and upgrade check; an LTS leaves the list when Moodle ends its security support.
- CI runs every supported version with PostgreSQL and MariaDB.

## Versions

Semantic versioning (`MAJOR.MINOR.PATCH`) with Moodle's `maturity`: 0.x releases are pre-1.0 (alpha,
beta); 1.0.0 will be the first stable release. Every release has an entry in `CHANGES.md`.

## Issues and pull requests

- Report bugs and ideas in GitHub Issues (one problem per issue, with Moodle version, PHP, database and
  steps to reproduce). Never include personal data or screenshots of real courses.
- Security problems: do **not** open a public issue; see [SECURITY.md](SECURITY.md).
- Pull requests: one topic each, with tests for every rule change, green CI, English strings in
  `lang/en` and the Brazilian Portuguese counterpart in `translations/pt_br`, and no new feature without
  a specification discussed in an issue first.
