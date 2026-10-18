<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Distributable package checker for block_coursecare (repository tool, not part of the package).
 *
 * Reads the real content of a ZIP and validates it against an allowlist, independently of how the ZIP
 * was produced. Contract: specs/001-plugin-foundation/contracts/dev-tools-cli.md.
 *
 * Usage: php tools/package/check.php <file.zip>
 * Exit codes: 0 valid, 1 invalid, 2 usage or read error.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Root directory required by the Moodle plugin installer. */
const COURSECARE_PACKAGE_ROOT = 'coursecare/';

/** Files that must be present. */
const COURSECARE_PACKAGE_REQUIRED = ['version.php', 'block_coursecare.php', 'db/access.php', 'lang/en/block_coursecare.php'];

/** Allowed files (fnmatch patterns relative to the root; "tests/**" allows anything under tests/). */
const COURSECARE_PACKAGE_ALLOWED = [
    'version.php', 'block_coursecare.php', 'overview.php', 'settings.php', 'cli/diagnostics.php', 'db/access.php',
    'db/install.xml', 'db/upgrade.php', 'db/upgradelib.php', 'db/tasks.php', 'classes/**', 'lang/en/block_coursecare.php',
    'templates/*.mustache', 'pix/*.svg', 'styles.css', 'tests/**', 'README.md', 'CHANGES.md', 'LICENSE',
];

/** Development directories that must never be packaged. */
const COURSECARE_PACKAGE_FORBIDDEN = [
    'translations/', 'tools/', 'specs/', '.specify/', '.claude/', '.github/', 'build/', 'vendor/', 'node_modules/',
];

/**
 * Validate the entries of a package.
 *
 * @param string[] $entries ZIP entry names.
 * @return array<int, array{0: string, 1: string}> Problems as [rule, path].
 */
function coursecare_package_problems(array $entries): array {
    $problems = [];
    $files = [];
    foreach ($entries as $entry) {
        if (strpos($entry, COURSECARE_PACKAGE_ROOT) !== 0) {
            $problems[] = ['outside-root', $entry];
            continue;
        }
        $path = substr($entry, strlen(COURSECARE_PACKAGE_ROOT));
        if ($path === '') {
            continue;
        }
        foreach (explode('/', rtrim($path, '/')) as $segment) {
            if ($segment !== '' && $segment[0] === '.') {
                $problems[] = ['hidden-file', $entry];
                continue 2;
            }
        }
        foreach (COURSECARE_PACKAGE_FORBIDDEN as $forbidden) {
            if (strpos($path . '/', $forbidden) === 0) {
                $problems[] = ['forbidden-path', $entry];
                continue 2;
            }
        }
        if (preg_match('#^lang/([^/]+)(/|$)#', $path, $matches) && $matches[1] !== 'en') {
            $problems[] = ['extra-language', $entry];
            continue;
        }
        if (substr($path, -1) === '/') {
            continue;
        }
        $files[] = $path;
        if (!coursecare_package_allowed($path)) {
            $problems[] = ['not-allowed', $entry];
        }
    }
    foreach (COURSECARE_PACKAGE_REQUIRED as $required) {
        if (!in_array($required, $files, true)) {
            $problems[] = ['missing-required', COURSECARE_PACKAGE_ROOT . $required];
        }
    }

    return $problems;
}

/**
 * Whether a file path (relative to the package root) is in the allowlist.
 *
 * @param string $path Relative path.
 * @return bool
 */
function coursecare_package_allowed(string $path): bool {
    foreach (COURSECARE_PACKAGE_ALLOWED as $pattern) {
        if (substr($pattern, -3) === '/**') {
            if (strpos($path, substr($pattern, 0, -2)) === 0) {
                return true;
            }
        } else if (fnmatch($pattern, $path, FNM_PATHNAME)) {
            return true;
        }
    }

    return false;
}

/**
 * Run the checker.
 *
 * @param array $argv Command line arguments.
 * @return int Exit code.
 */
function coursecare_package_main(array $argv): int {
    if (count($argv) !== 2) {
        fwrite(STDERR, "Usage: php tools/package/check.php <file.zip>\n");
        return 2;
    }
    if (!class_exists('ZipArchive')) {
        fwrite(STDERR, "ERROR: the PHP zip extension is required\n");
        return 2;
    }
    $zip = new ZipArchive();
    if (!is_file($argv[1]) || $zip->open($argv[1]) !== true) {
        fwrite(STDERR, "ERROR: cannot read {$argv[1]}\n");
        return 2;
    }
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entries[] = $zip->getNameIndex($i);
    }
    $zip->close();

    $problems = coursecare_package_problems($entries);
    foreach ($problems as [$rule, $path]) {
        echo "ERROR {$rule} {$path}\n";
    }
    $files = count(array_filter($entries, static function (string $entry): bool {
        return substr($entry, -1) !== '/';
    }));
    echo 'package: ' . count($problems) . " error(s), {$files} file(s)\n";

    return $problems ? 1 : 0;
}

exit(coursecare_package_main($argv));
