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
 * Tests for the repository tools tools/i18n/check.php and tools/package/check.php (plain PHP, no Moodle).
 *
 * Usage: php tools/i18n/tests/run.php
 * Exit code: 0 only when every case passes.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run a PHP script and return its exit code and output.
 *
 * @param string $script Script path.
 * @param string[] $args Arguments.
 * @return array{0: int, 1: string}
 */
function coursecare_tools_run(string $script, array $args): array {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg($arg);
    }
    exec($command . ' 2>&1', $output, $code);

    return [$code, implode("\n", $output)];
}

/**
 * Build a ZIP with the given entries (directories end with "/").
 *
 * @param string $path ZIP path.
 * @param string[] $entries Entry names.
 */
function coursecare_tools_zip(string $path, array $entries): void {
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $entry) {
        if (substr($entry, -1) === '/') {
            $zip->addEmptyDir(rtrim($entry, '/'));
        } else {
            $zip->addFromString($entry, "content\n");
        }
    }
    $zip->close();
}

$root = dirname(__DIR__, 3);
$i18n = $root . '/tools/i18n/check.php';
$package = $root . '/tools/package/check.php';
$fixtures = __DIR__ . '/fixtures';

// Each case: [script, arguments, expected exit code, lines that must be present, lines that must be absent].
$cases = [
    'i18n valid' => [$i18n, ["--root={$fixtures}/valid"], 0, ['i18n: 0 error(s), 0 warning(s) in 1 language(s)'], ['ERROR']],
    'i18n missing-key' => [$i18n, ["--root={$fixtures}/missing-key"], 1,
        ['ERROR missing-key pt_br block_fixture.php:title'], []],
    'i18n extra-key' => [$i18n, ["--root={$fixtures}/extra-key"], 1, ['ERROR extra-key pt_br block_fixture.php:unused'], []],
    'i18n placeholder-mismatch' => [$i18n, ["--root={$fixtures}/placeholder-mismatch"], 1,
        ['ERROR placeholder-mismatch pt_br block_fixture.php:greeting en [{$a->name}] vs pt_br [{$a}]'], []],
    'i18n empty-value' => [$i18n, ["--root={$fixtures}/empty-value"], 1, ['ERROR empty-value pt_br block_fixture.php:title'], []],
    'i18n invalid-syntax (concatenation)' => [$i18n, ["--root={$fixtures}/invalid-syntax-concat"], 1,
        ['ERROR invalid-syntax pt_br block_fixture.php:line 3'], []],
    'i18n invalid-syntax (heredoc)' => [$i18n, ["--root={$fixtures}/invalid-syntax-heredoc"], 1,
        ['ERROR invalid-syntax pt_br block_fixture.php:line 3'], []],
    'i18n invalid-syntax (variable)' => [$i18n, ["--root={$fixtures}/invalid-syntax-variable"], 1,
        ['ERROR invalid-syntax pt_br block_fixture.php:line 3'], []],
    'i18n invalid-syntax (function call)' => [$i18n, ["--root={$fixtures}/invalid-syntax-call"], 1,
        ['ERROR invalid-syntax pt_br block_fixture.php:line 3'], []],
    'i18n duplicate-key' => [$i18n, ["--root={$fixtures}/duplicate-key"], 1,
        ['ERROR duplicate-key pt_br block_fixture.php:title'], []],
    'i18n missing-file' => [$i18n, ["--root={$fixtures}/missing-file"], 1,
        ['ERROR missing-file pt_br block_fixture_extra.php'], []],
    'i18n same-as-english (warning)' => [$i18n, ["--root={$fixtures}/same-as-english"], 0,
        ['WARNING same-as-english pt_br block_fixture.php:title', 'i18n: 0 error(s), 1 warning(s) in 1 language(s)'], []],
    'i18n same-as-english (--strict)' => [$i18n, ["--root={$fixtures}/same-as-english", '--strict'], 1,
        ['ERROR same-as-english pt_br block_fixture.php:title'], []],
    'i18n extra-file' => [$i18n, ["--root={$fixtures}/extra-file"], 1,
        ['ERROR extra-file pt_br block_fixture_old.php no English file with this name'], ['ERROR missing']],
    'i18n key-order' => [$i18n, ["--root={$fixtures}/key-order"], 1,
        ["ERROR key-order pt_br block_fixture.php:title expected 'greeting' here (same order as English)"], ['missing-key']],
    'i18n multi-language' => [$i18n, ["--root={$fixtures}/multi-language"], 1,
        ['ERROR missing-key es block_fixture.php:title', 'i18n: 1 error(s), 0 warning(s) in 2 language(s)'],
        ['ERROR missing-key pt_br']],
    'i18n usage: missing root' => [$i18n, ["--root={$fixtures}/does-not-exist"], 2, [], []],
    'i18n usage: no English strings' => [$i18n, ["--root={$fixtures}/no-english"], 2, [], []],
    'i18n usage: unknown option' => [$i18n, ['--unknown'], 2, [], []],
];

$tmp = sys_get_temp_dir() . '/coursecare-package-tests-' . getmypid();
@mkdir($tmp, 0777, true);
$valid = [
    'coursecare/', 'coursecare/version.php', 'coursecare/block_coursecare.php', 'coursecare/db/access.php',
    'coursecare/classes/privacy/provider.php', 'coursecare/lang/en/block_coursecare.php', 'coursecare/styles.css',
    'coursecare/templates/content.mustache', 'coursecare/pix/sprout.svg', 'coursecare/tests/behat/x.feature',
    'coursecare/tests/block_coursecare_test.php', 'coursecare/README.md', 'coursecare/CHANGES.md', 'coursecare/LICENSE',
];
$zips = [
    'valid' => [$valid, 0, ['package: 0 error(s), 13 file(s)']],
    'outside-root' => [array_merge($valid, ['other/version.php']), 1, ['ERROR outside-root other/version.php']],
    'translations' => [array_merge($valid, ['coursecare/translations/pt_br/block_coursecare.php']), 1,
        ['ERROR forbidden-path coursecare/translations/pt_br/block_coursecare.php']],
    'extra-language' => [array_merge($valid, ['coursecare/lang/pt_br/block_coursecare.php']), 1,
        ['ERROR extra-language coursecare/lang/pt_br/block_coursecare.php']],
    'tools' => [array_merge($valid, ['coursecare/tools/i18n/check.php']), 1,
        ['ERROR forbidden-path coursecare/tools/i18n/check.php']],
    'specs' => [array_merge($valid, ['coursecare/specs/001/spec.md']), 1, ['ERROR forbidden-path coursecare/specs/001/spec.md']],
    'hidden-file' => [array_merge($valid, ['coursecare/.gitattributes']), 1, ['ERROR hidden-file coursecare/.gitattributes']],
    'not-allowed' => [array_merge($valid, ['coursecare/notes.txt']), 1, ['ERROR not-allowed coursecare/notes.txt']],
    'missing-required' => [array_diff($valid, ['coursecare/db/access.php']), 1,
        ['ERROR missing-required coursecare/db/access.php']],
];
foreach ($zips as $name => [$entries, $code, $lines]) {
    $path = "{$tmp}/{$name}.zip";
    coursecare_tools_zip($path, $entries);
    $cases["package {$name}"] = [$package, [$path], $code, $lines, $name === 'valid' ? ['ERROR'] : []];
}
$cases['package usage: missing file'] = [$package, ["{$tmp}/does-not-exist.zip"], 2, [], []];
$cases['package usage: no argument'] = [$package, [], 2, [], []];

$failures = 0;
foreach ($cases as $name => [$script, $args, $expectedcode, $present, $absent]) {
    [$code, $output] = coursecare_tools_run($script, $args);
    $reasons = [];
    if ($code !== $expectedcode) {
        $reasons[] = "exit code {$code}, expected {$expectedcode}";
    }
    foreach ($present as $line) {
        if (strpos($output, $line) === false) {
            $reasons[] = "missing output: {$line}";
        }
    }
    foreach ($absent as $line) {
        if (strpos($output, $line) !== false) {
            $reasons[] = "unexpected output: {$line}";
        }
    }
    if ($reasons) {
        $failures++;
        echo "FAIL {$name}: " . implode('; ', $reasons) . "\n" . preg_replace('/^/m', '    ', $output) . "\n";
    } else {
        echo "PASS {$name}\n";
    }
}

array_map('unlink', glob("{$tmp}/*.zip") ?: []);
@rmdir($tmp);

echo count($cases) - $failures . ' passed, ' . $failures . ' failed, ' . count($cases) . " total\n";
exit($failures > 0 ? 1 : 0);
