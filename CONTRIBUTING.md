# Contributing to CourseCare

Thank you for helping CourseCare (AVA Consciente in Brazilian Portuguese). The project follows the
constitution in `.specify/memory/constitution.md` and the Spec Kit workflow (`specs/`). Every
change starts from a specification and must pass the quality gates below.

## Development environment

Use the reproducible Moodle 4.5 environment described in
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

1. Install the official `pt_br` language pack (Site administration > Language > Language packs).
2. Import the project translation with the native language customisation tool:

```bash
php admin/tool/customlang/cli/import.php --lang=pt_br --source=/path/to/moodle/blocks/coursecare/translations/pt_br --checkin
```

Local customisations take precedence over the official language pack. Do **not** use this procedure
on production sites to obtain the CourseCare translation.

### Public distribution

The package contains only English. After publication in the Moodle Plugins directory (Marketplace),
the Brazilian Portuguese translation is contributed to AMOS and reaches sites through the official
`pt_br` language pack.
