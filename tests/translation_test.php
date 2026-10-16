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

namespace block_coursecare;

/**
 * Tests the development pt_BR translation through the real string manager, and the English fallback.
 *
 * The translation is applied as a local language customisation (pt_br_local), as tool_customlang does.
 * The test is skipped when translations/ is absent, which is the case for installations from the package.
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class translation_test extends \advanced_testcase {
    /** @var string Path of the versioned development translation. */
    private string $source;

    /**
     * Prepare a minimal pt_br language and skip when the development translation is not available.
     */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();

        $this->source = $CFG->dirroot . '/blocks/coursecare/translations/pt_br/block_coursecare.php';
        if (!is_readable($this->source)) {
            $this->markTestSkipped('translations/pt_br is not present (installation from the distributable package).');
        }

        $langroot = $CFG->dataroot . '/lang';
        make_writable_directory($langroot . '/pt_br');
        make_writable_directory($langroot . '/pt_br_local');
        file_put_contents($langroot . '/pt_br/langconfig.php', "<?php\n" .
            "\$string['thislanguage'] = 'Português - Brasil';\n" .
            "\$string['thislanguageint'] = 'Portuguese - Brazil';\n" .
            "\$string['parentlanguage'] = '';\n");
    }

    /**
     * Reset the string manager so that other tests do not see the temporary language.
     */
    protected function tearDown(): void {
        get_string_manager(true)->reset_caches();
        parent::tearDown();
    }

    /**
     * Install the given content as the local pt_br customisation and return a fresh string manager.
     *
     * @param string $content PHP language file content.
     * @return \core_string_manager
     */
    private function install_customisation(string $content): \core_string_manager {
        global $CFG;

        file_put_contents($CFG->dataroot . '/lang/pt_br_local/block_coursecare.php', $content);
        $manager = get_string_manager(true);
        $manager->reset_caches();

        return $manager;
    }

    /**
     * With the translation installed, every string is shown in Brazilian Portuguese.
     */
    public function test_translation_is_used(): void {
        $manager = $this->install_customisation(file_get_contents($this->source));

        $this->assertTrue($manager->translation_exists('pt_br'));
        $this->assertSame('AVA Consciente', $manager->get_string('pluginname', 'block_coursecare', null, 'pt_br'));
        $this->assertSame('Pequenas escolhas, um AVA melhor para todos.',
            $manager->get_string('tagline', 'block_coursecare', null, 'pt_br'));
        $this->assertSame(
            'Em breve, você encontrará aqui orientações para utilizar os recursos da sua sala de forma mais consciente.',
            $manager->get_string('intro', 'block_coursecare', null, 'pt_br'));
    }

    /**
     * A key missing from the translation falls back to the complete English string; the others stay translated.
     */
    public function test_missing_key_falls_back_to_english(): void {
        $content = preg_replace('/^\$string\[\'intro\'\].*$\n/m', '', file_get_contents($this->source));
        $this->assertStringNotContainsString("\$string['intro']", $content);

        $manager = $this->install_customisation($content);

        $this->assertSame(get_string('intro', 'block_coursecare'),
            $manager->get_string('intro', 'block_coursecare', null, 'pt_br'));
        $this->assertSame('Pequenas escolhas, um AVA melhor para todos.',
            $manager->get_string('tagline', 'block_coursecare', null, 'pt_br'));
    }

    /**
     * Without the customisation, pt_br and languages without a CourseCare translation show English.
     */
    public function test_no_translation_shows_english(): void {
        $manager = get_string_manager(true);
        $manager->reset_caches();

        $english = get_string('tagline', 'block_coursecare');
        $this->assertSame('Small choices, a better learning space for everyone.', $english);
        $this->assertSame($english, $manager->get_string('tagline', 'block_coursecare', null, 'pt_br'));
        $this->assertSame($english, $manager->get_string('tagline', 'block_coursecare', null, 'es'));
    }
}
