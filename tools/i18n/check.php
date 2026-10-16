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
 * Translation parity checker for block_coursecare (repository tool, not part of the package).
 *
 * Compares every language in translations/ with the canonical English strings in lang/en, without
 * executing the files and without a Moodle installation. Contract: specs/001-plugin-foundation/contracts/dev-tools-cli.md.
 *
 * Usage: php tools/i18n/check.php [--root=<dir>] [--strict]
 * Exit codes: 0 no errors (warnings allowed), 1 errors found, 2 usage or read error.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Parse a language file as pure data, the way the Moodle language tools (AMOS) expect it.
 *
 * Accepts the opening tag, comments, an optional MOODLE_INTERNAL check and statements of the form
 * $string['key'] = 'literal'; with a single or double quoted literal without interpolation.
 *
 * @param string $path File path.
 * @return array{strings: array<string, string>, problems: array<int, array{0: string, 1: string, 2: string}>}
 */
function coursecare_i18n_parse(string $path): array {
    $tokens = token_get_all((string) file_get_contents($path));
    $tokens = array_values(array_filter($tokens, static function ($token): bool {
        return !is_array($token) || !in_array($token[0], [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }));

    $strings = [];
    $problems = [];
    $count = count($tokens);
    $i = 0;
    while ($i < $count) {
        $line = coursecare_i18n_line($tokens, $i);

        // Optional: defined('MOODLE_INTERNAL') || die();.
        if (coursecare_i18n_match($tokens, $i, [[T_STRING, 'defined'], '(', [T_CONSTANT_ENCAPSED_STRING, null], ')',
                [T_BOOLEAN_OR, null], [T_EXIT, null], '(', ')', ';'])) {
            $i += 9;
            continue;
        }

        // Expected: $string['key'] = 'value';.
        if (coursecare_i18n_match($tokens, $i, [[T_VARIABLE, '$string'], '[', [T_CONSTANT_ENCAPSED_STRING, null], ']', '=',
                [T_CONSTANT_ENCAPSED_STRING, null], ';'])) {
            $key = coursecare_i18n_literal($tokens[$i + 2][1]);
            $value = coursecare_i18n_literal($tokens[$i + 5][1]);
            if (array_key_exists($key, $strings)) {
                $problems[] = ['duplicate-key', $key, "line {$line}"];
            }
            $strings[$key] = $value;
            $i += 7;
            continue;
        }

        // Anything else is not pure data: report it and skip to the end of the statement.
        $problems[] = ['invalid-syntax', "line {$line}", 'only $string[\'key\'] = \'literal\'; statements are allowed'];
        while ($i < $count && $tokens[$i] !== ';') {
            $i++;
        }
        $i++;
    }

    return ['strings' => $strings, 'problems' => $problems];
}

/**
 * Check whether the tokens starting at $i match a pattern.
 *
 * @param array $tokens Tokens without whitespace and comments.
 * @param int $i Start position.
 * @param array $pattern Characters, or [token id, exact text or null for any].
 * @return bool
 */
function coursecare_i18n_match(array $tokens, int $i, array $pattern): bool {
    foreach ($pattern as $offset => $expected) {
        $token = $tokens[$i + $offset] ?? null;
        if ($token === null) {
            return false;
        }
        if (is_string($expected)) {
            if ($token !== $expected) {
                return false;
            }
            continue;
        }
        if (!is_array($token) || $token[0] !== $expected[0] || ($expected[1] !== null && $token[1] !== $expected[1])) {
            return false;
        }
    }

    return true;
}

/**
 * Get the line number of the token at $i, or of the previous token with a line number.
 *
 * @param array $tokens Tokens.
 * @param int $i Position.
 * @return int
 */
function coursecare_i18n_line(array $tokens, int $i): int {
    for ($j = $i; $j >= 0; $j--) {
        if (is_array($tokens[$j] ?? null)) {
            return $tokens[$j][2];
        }
    }

    return 0;
}

/**
 * Decode a constant PHP string literal (no interpolation is possible in T_CONSTANT_ENCAPSED_STRING).
 *
 * @param string $literal Literal including its quotes.
 * @return string
 */
function coursecare_i18n_literal(string $literal): string {
    $quote = $literal[0];
    $body = substr($literal, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
    }

    return stripcslashes($body);
}

/**
 * Get the sorted, unique {$a} and {$a->name} placeholders of a string.
 *
 * @param string $value String value.
 * @return string[]
 */
function coursecare_i18n_placeholders(string $value): array {
    preg_match_all('/\{\$a(?:->[A-Za-z0-9_]+)?\}/', $value, $matches);
    $placeholders = array_values(array_unique($matches[0]));
    sort($placeholders);

    return $placeholders;
}

/**
 * Run the checker.
 *
 * @param array $argv Command line arguments.
 * @return int Exit code.
 */
function coursecare_i18n_main(array $argv): int {
    $root = dirname(__DIR__, 2);
    $strict = false;
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, '--root=') === 0) {
            $root = substr($arg, strlen('--root='));
        } else if ($arg === '--strict') {
            $strict = true;
        } else {
            fwrite(STDERR, "Usage: php tools/i18n/check.php [--root=<dir>] [--strict]\n");
            return 2;
        }
    }

    $enfiles = glob(rtrim($root, '/') . '/lang/en/*.php') ?: [];
    if (!is_dir($root) || !$enfiles) {
        fwrite(STDERR, "ERROR: no English strings found in {$root}/lang/en\n");
        return 2;
    }
    sort($enfiles);

    $issues = [];
    $english = [];
    foreach ($enfiles as $file) {
        $name = basename($file);
        $parsed = coursecare_i18n_parse($file);
        $english[$name] = $parsed['strings'];
        foreach ($parsed['problems'] as [$code, $where, $detail]) {
            $issues[] = ['ERROR', $code, 'en', "{$name}:{$where}", $detail];
        }
    }

    $languages = glob(rtrim($root, '/') . '/translations/*', GLOB_ONLYDIR) ?: [];
    sort($languages);
    foreach ($languages as $dir) {
        $lang = basename($dir);
        foreach ($english as $name => $enstrings) {
            $file = "{$dir}/{$name}";
            if (!is_file($file)) {
                $issues[] = ['ERROR', 'missing-file', $lang, $name, 'no translation file for this English file'];
                continue;
            }
            $parsed = coursecare_i18n_parse($file);
            foreach ($parsed['problems'] as [$code, $where, $detail]) {
                $issues[] = ['ERROR', $code, $lang, "{$name}:{$where}", $detail];
            }
            $strings = $parsed['strings'];
            foreach ($enstrings as $key => $envalue) {
                if (!array_key_exists($key, $strings)) {
                    $issues[] = ['ERROR', 'missing-key', $lang, "{$name}:{$key}", 'key missing from the translation'];
                    continue;
                }
                $value = $strings[$key];
                if (trim($value) === '') {
                    $issues[] = ['ERROR', 'empty-value', $lang, "{$name}:{$key}", 'empty translation'];
                    continue;
                }
                $enplaceholders = coursecare_i18n_placeholders($envalue);
                $placeholders = coursecare_i18n_placeholders($value);
                if ($enplaceholders !== $placeholders) {
                    $issues[] = ['ERROR', 'placeholder-mismatch', $lang, "{$name}:{$key}",
                        'en [' . implode(' ', $enplaceholders) . '] vs ' . $lang . ' [' . implode(' ', $placeholders) . ']'];
                }
                if ($value === $envalue) {
                    $issues[] = [$strict ? 'ERROR' : 'WARNING', 'same-as-english', $lang, "{$name}:{$key}", 'identical to English'];
                }
            }
            foreach (array_diff_key($strings, $enstrings) as $key => $unused) {
                $issues[] = ['ERROR', 'extra-key', $lang, "{$name}:{$key}", 'key does not exist in English'];
            }
        }
    }

    usort($issues, static function (array $a, array $b): int {
        return [$a[2], $a[3], $a[1]] <=> [$b[2], $b[3], $b[1]];
    });

    $errors = 0;
    $warnings = 0;
    foreach ($issues as [$severity, $code, $lang, $where, $detail]) {
        echo "{$severity} {$code} {$lang} {$where} {$detail}\n";
        $severity === 'ERROR' ? $errors++ : $warnings++;
    }
    echo "i18n: {$errors} error(s), {$warnings} warning(s) in " . count($languages) . " language(s)\n";

    return $errors > 0 ? 1 : 0;
}

exit(coursecare_i18n_main($argv));
