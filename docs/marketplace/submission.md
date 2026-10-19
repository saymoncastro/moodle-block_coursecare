# Submitting CourseCare to the Moodle Marketplace

Steps for the maintainer. **Nothing has been submitted.** Submission, tagging and publishing need the
product owner's express approval, and are currently blocked by the open ownership question and by the
items in "Before submitting".

Based on the public sources consulted on 2026-10-18 (Moodle Marketplace, Plugins FAQ, plugin
contribution pages and code pre-checks on moodledev.io). The official submission guidelines and
listing guide require a Moodle Atlassian login and were not read; check them first.

## Before submitting

1. Ownership and licensing answered (see the project's ownership report) and the `@copyright` lines
   confirmed or adjusted.
2. The official submission guidelines and listing guide read, and any difference with this document
   resolved.
3. GitHub private vulnerability reporting enabled (repository Settings > Security), as `SECURITY.md`
   promises.
4. Teacher evaluation and screen reader test done, or formally deferred by the product owner with the
   reason recorded.
5. CI green on every supported version for the commit to be released.

## 1. Account

Sign in to the Moodle Marketplace as a plugin provider. If you have a moodle.org account, sign in with
its e-mail address so that the account is linked.

## 2. Package

Build the ZIP from the release commit:

```bash
tools/package/build.sh <commit>
```

```bash
php tools/package/check.php build/block_coursecare-<release>.zip
```

The ZIP has the root folder `coursecare/` and only English strings. Upload this ZIP, not the archive
GitHub generates.

## 3. Submission form

- Name, type (block), component `block_coursecare`, short and long description, supported versions,
  links and screenshots: copy them from [listing.md](listing.md).
- Documentation and installation instructions: link the `docs/` folder of the repository.
- License: GNU GPL v3 or later.

## 4. Automated checks

The Marketplace validates the archive and runs the plugin pre-checks (phplint, phpcs, phpdoc,
savepoints, mustache, third-party libraries, grunt). The repository CI runs the same checks; fix any
difference the Marketplace reports and upload a new ZIP.

## 5. Manual review

Free plugins get one manual review, tracked in a Jira Service Management ticket; there is no fixed
timeframe. Answer the reviewer in the ticket, fix what is requested in the repository (with a new
version number when code changes), and upload the new ZIP.

## 6. Publishing

After approval, the maintainer publishes the plugin manually. Create the git tag and the GitHub
release for the same commit only with the product owner's approval.

## 7. New versions

Bump `version` and `release` in `version.php`, add the `CHANGES.md` entry, run CI, build the ZIP and
upload it from the Marketplace dashboard (upload by API is limited at launch).

## 8. Translation

After approval, contribute the Brazilian Portuguese strings (`translations/pt_br/block_coursecare.php`)
in AMOS, the Moodle translation system. They then reach sites through the official `pt_br` language
pack. Do not add other languages to the package.
