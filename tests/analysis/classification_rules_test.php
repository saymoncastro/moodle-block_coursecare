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

namespace block_coursecare\analysis;

use block_coursecare\local\analysis\classification_rules as rules;

/**
 * Tests of the classification rule table (contracts/coverage-matrix.md, FR-021 to FR-025).
 *
 * @package    block_coursecare
 * @category   test
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_coursecare\local\analysis\classification_rules
 */
final class classification_rules_test extends \basic_testcase {
    /**
     * One matching case for each of the 30 rules, in table order.
     *
     * @return array
     */
    public static function matching_provider(): array {
        $crs = CONTEXT_COURSE;
        $mod = CONTEXT_MODULE;
        return [
            'r1 folder temp' => ['mod_folder', 'temp', $mod, rules::EXCLUDED],
            'r2 book import temp' => ['mod_book', 'importhtmltemp', $mod, rules::EXCLUDED],
            'r3 feedback batch' => ['assignfeedback_file', 'feedback_files_batch', $mod, rules::EXCLUDED],
            'r4 feedback import' => ['assignfeedback_file', 'feedback_files_import', $mod, rules::EXCLUDED],
            'r5 editpdf tmp jpg' => ['assignfeedback_editpdf', 'tmp_jpg_to_pdf', $mod, rules::EXCLUDED],
            'r6 editpdf tmp rotated' => ['assignfeedback_editpdf', 'tmp_rotated_jpg', $mod, rules::EXCLUDED],
            'r7 editpdf importhtml' => ['assignfeedback_editpdf', 'importhtml', $mod, rules::EXCLUDED],
            'r8 backup course' => ['backup', 'course', $crs, rules::BACKUP],
            'r8 backup section' => ['backup', 'section', $crs, rules::BACKUP],
            'r8 backup automated' => ['backup', 'automated', $crs, rules::BACKUP],
            'r9 backup activity' => ['backup', 'activity', $mod, rules::BACKUP],
            'r10 recycle bin' => ['tool_recyclebin', 'recyclebin_course', $crs, rules::RECYCLEBIN],
            'r11 course summary' => ['course', 'summary', $crs, rules::MATERIAL],
            'r11 course image' => ['course', 'overviewfiles', $crs, rules::MATERIAL],
            'r11 course section' => ['course', 'section', $crs, rules::MATERIAL],
            'r12 resource' => ['mod_resource', 'content', $mod, rules::MATERIAL],
            'r13 folder' => ['mod_folder', 'content', $mod, rules::MATERIAL],
            'r14 page' => ['mod_page', 'content', $mod, rules::MATERIAL],
            'r15 book chapter' => ['mod_book', 'chapter', $mod, rules::MATERIAL],
            'r16 assign intro attachment' => ['mod_assign', 'introattachment', $mod, rules::MATERIAL],
            'r16 assign activity attachment' => ['mod_assign', 'activityattachment', $mod, rules::MATERIAL],
            'r17 any module intro' => ['mod_lesson', 'intro', $mod, rules::MATERIAL],
            'r18 text block' => ['block_html', 'content', CONTEXT_BLOCK, rules::MATERIAL],
            'r19 file submission' => ['assignsubmission_file', 'submission_files', $mod, rules::SUBMISSION],
            'r20 online text' => ['assignsubmission_onlinetext', 'submissions_onlinetext', $mod, rules::SUBMISSION],
            'r21 question response' => ['question', 'response_attachments', $mod, rules::SUBMISSION],
            'r22 feedback files' => ['assignfeedback_file', 'feedback_files', $mod, rules::OTHER],
            'r23 feedback comments' => ['assignfeedback_comments', 'feedback', $mod, rules::OTHER],
            'r24 annotated pdf' => ['assignfeedback_editpdf', 'download', $mod, rules::OTHER],
            'r24 readonly pages' => ['assignfeedback_editpdf', 'readonlypages', $mod, rules::OTHER],
            'r24 stamps' => ['assignfeedback_editpdf', 'stamps', $mod, rules::OTHER],
            'r25 combined' => ['assignfeedback_editpdf', 'combined', $mod, rules::OTHER],
            'r25 partial' => ['assignfeedback_editpdf', 'partial', $mod, rules::OTHER],
            'r25 pages' => ['assignfeedback_editpdf', 'pages', $mod, rules::OTHER],
            'r26 grade feedback' => ['grade', 'feedback', $mod, rules::OTHER],
            'r26 grade history in course' => ['grade', 'historyfeedback', $crs, rules::OTHER],
            'r27 forum attachment' => ['mod_forum', 'attachment', $mod, rules::OTHER],
            'r27 forum post' => ['mod_forum', 'post', $mod, rules::OTHER],
            'r28 question text' => ['question', 'questiontext', $crs, rules::OTHER],
            'r29 qtype area' => ['qtype_ddmarker', 'bgimage', $mod, rules::OTHER],
            'r30 content bank' => ['contentbank', 'public', $crs, rules::OTHER],
        ];
    }

    /**
     * Each rule classifies its area.
     *
     * @dataProvider matching_provider
     * @param string $component Component.
     * @param string $filearea File area.
     * @param int $level Context level.
     * @param string $expected Expected result.
     */
    public function test_rule_matches(string $component, string $filearea, int $level, string $expected): void {
        $this->assertSame($expected, rules::classify($component, $filearea, $level));
    }

    /**
     * Cases that must not match a rule: other area, other context level, unknown component or area.
     *
     * @return array
     */
    public static function unclassified_provider(): array {
        return [
            'unknown component' => ['local_fake', 'content', CONTEXT_MODULE],
            'unknown area of a known component' => ['mod_resource', 'something', CONTEXT_MODULE],
            'resource content in course context' => ['mod_resource', 'content', CONTEXT_COURSE],
            'backup course area in module context' => ['backup', 'course', CONTEXT_MODULE],
            'recycle bin in module context' => ['tool_recyclebin', 'recyclebin_course', CONTEXT_MODULE],
            'text block content outside a block context' => ['block_html', 'content', CONTEXT_COURSE],
            'other block' => ['block_fake', 'content', CONTEXT_BLOCK],
            'module intro in course context' => ['mod_lesson', 'intro', CONTEXT_COURSE],
            'mixed module area' => ['mod_lesson', 'essay_answers', CONTEXT_MODULE],
            'workshop submission' => ['mod_workshop', 'submission_attachment', CONTEXT_MODULE],
            'course legacy files' => ['course', 'legacy', CONTEXT_COURSE],
            'group picture' => ['group', 'icon', CONTEXT_COURSE],
            'question in a block context' => ['question', 'questiontext', CONTEXT_BLOCK],
            'component prefix without underscore' => ['modx', 'intro', CONTEXT_MODULE],
            'empty component' => ['', '', CONTEXT_MODULE],
        ];
    }

    /**
     * Anything that no rule matches is unclassified, never a course material (FR-023).
     *
     * @dataProvider unclassified_provider
     * @param string $component Component.
     * @param string $filearea File area.
     * @param int $level Context level.
     */
    public function test_unmatched_is_unclassified(string $component, string $filearea, int $level): void {
        $this->assertSame(rules::UNCLASSIFIED, rules::classify($component, $filearea, $level));
    }

    /**
     * The more specific rule wins: transient areas before materials, responses before question bank areas.
     */
    public function test_precedence(): void {
        $this->assertSame(rules::EXCLUDED, rules::classify('mod_folder', 'temp', CONTEXT_MODULE));
        $this->assertSame(rules::MATERIAL, rules::classify('mod_folder', 'content', CONTEXT_MODULE));
        $this->assertSame(rules::EXCLUDED, rules::classify('mod_book', 'importhtmltemp', CONTEXT_MODULE));
        $this->assertSame(rules::EXCLUDED, rules::classify('assignfeedback_file', 'feedback_files_batch', CONTEXT_MODULE));
        $this->assertSame(rules::OTHER, rules::classify('assignfeedback_file', 'feedback_files', CONTEXT_MODULE));
        $this->assertSame(rules::EXCLUDED, rules::classify('assignfeedback_editpdf', 'importhtml', CONTEXT_MODULE));
        $this->assertSame(rules::SUBMISSION, rules::classify('question', 'response_answer', CONTEXT_MODULE));
        $this->assertSame(rules::OTHER, rules::classify('question', 'response_answer', CONTEXT_COURSE));
        $this->assertSame(rules::OTHER, rules::classify('question', 'hint', CONTEXT_MODULE));
    }

    /**
     * The table has the 30 rules of the coverage matrix and only known results.
     */
    public function test_table_shape(): void {
        $rules = rules::rules();
        $this->assertCount(30, $rules);
        $known = array_merge(rules::MAIN_CATEGORIES, rules::BACKUP_CATEGORIES, [rules::EXCLUDED]);
        foreach ($rules as $rule) {
            $this->assertContains($rule['result'], $known);
        }
    }
}
