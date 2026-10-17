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

namespace block_coursecare\output;

use block_coursecare\local\analysis\diagnostic_state;
use block_coursecare\local\analysis\overview_builder;
use block_coursecare\local\analysis\state_resolver;
use block_coursecare\local\collector\course_structure;
use block_coursecare\local\collector\diagnostic_store;

/**
 * The "Your course in numbers" page (contracts/overview-page.md).
 *
 * Receives the course view built by overview_builder and turns it into template data: every sentence comes complete
 * from the string catalogue, sizes from display_size() and dates from userdate(). No file record is read here.
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_overview implements \renderable, \templatable {
    /** @var array Course view from overview_builder::build(). */
    private array $view;

    /** @var \moodle_url Course page, for the way back. */
    private \moodle_url $courseurl;

    /**
     * Constructor.
     *
     * @param array $view Course view.
     * @param \moodle_url $courseurl Course page.
     */
    public function __construct(array $view, \moodle_url $courseurl) {
        $this->view = $view;
        $this->courseurl = $courseurl;
    }

    /**
     * The overview of a course, read from the stored diagnostic and the course structure (never from file records).
     *
     * The caller has already checked the access (overview_access).
     *
     * @param int $courseid Course id.
     * @return self
     */
    public static function for_course(int $courseid): self {
        $store = new diagnostic_store();
        $record = $store->get_summary($courseid);
        $staleness = (int)get_config('block_coursecare', 'stalenessthreshold');
        $state = state_resolver::resolve($record, time(), $staleness > 0 ? $staleness : 3 * DAYSECS);
        $categories = [];
        $activities = [];
        $large = [];
        $structure = [];
        if ($record !== null && $state->hasresult) {
            $categories = $store->get_categories((int)$record->id);
            $activities = $store->get_activities((int)$record->id);
            $large = $store->get_large_materials_by_activity((int)$record->id);
            $structure = (new course_structure())->get_activities($courseid);
        }
        $view = (new overview_builder())->build($state, $record, $categories, $activities, $large, $structure);
        return new self($view, new \moodle_url('/course/view.php', ['id' => $courseid]));
    }

    /**
     * Data for the overview template.
     *
     * @param \renderer_base $output Renderer.
     * @return array
     */
    public function export_for_template(\renderer_base $output): array {
        return [
            'title' => get_string('overview:title', 'block_coursecare'),
            'intro' => get_string('overview:intro', 'block_coursecare'),
            'backurl' => $this->courseurl->out(false),
            'backtext' => get_string('overview:back', 'block_coursecare'),
            'summary' => array_map(fn(string $text): array => ['text' => $text], $this->summary_sentences()),
            'hascategories' => $this->view['categories'] !== null,
            'categories' => $this->categories_data(),
            'hasactivities' => $this->view['activities'] !== null,
            'activities' => $this->activities_data(),
        ];
    }

    /**
     * Template data of the categories of the course.
     *
     * @return array
     */
    private function categories_data(): array {
        $rows = [];
        foreach ($this->view['categories'] ?? [] as $category) {
            $rows[] = [
                'name' => get_string('overview:category:' . $category['key'], 'block_coursecare'),
                'help' => get_string('overview:category:' . $category['key'] . '_help', 'block_coursecare'),
                'url' => null,
                'hasurl' => false,
            ] + self::measure($category['bytes'], $category['share']);
        }
        return [
            'heading' => get_string('overview:categories', 'block_coursecare'),
            'rows' => $rows,
        ];
    }

    /**
     * Template data of the materials per activity: the first ones, the groups, and the rest behind "Show all".
     *
     * @return array
     */
    private function activities_data(): array {
        $list = $this->view['activities'];
        if ($list === null) {
            return [];
        }
        // Every key of the row partial is set, so that Mustache never takes a missing one from the section around it.
        $item = fn(array $row): array => ['name' => $row['name'], 'url' => $row['url'], 'hasurl' => $row['url'] !== null,
            'help' => ''] + self::measure($row['bytes'], $row['share']);
        $groups = [];
        foreach (
            ['courselevel' => 'overview:courselevel', 'others' => 'overview:otheractivities',
                'removed' => 'overview:removed'] as $key => $string
        ) {
            if ($list[$key] !== null) {
                $groups[] = ['name' => get_string($string, 'block_coursecare'), 'url' => null, 'hasurl' => false,
                    'help' => ''] + self::measure($list[$key]['bytes'], $list[$key]['share']);
            }
        }
        return [
            'heading' => get_string('overview:activities', 'block_coursecare'),
            'help' => get_string('overview:activities_help', 'block_coursecare'),
            'first' => array_map($item, $list['first']),
            'groups' => $groups,
            'hasrest' => $list['rest'] !== [],
            'rest' => array_map($item, $list['rest']),
            'showall' => get_string('overview:showall', 'block_coursecare', $list['count']),
        ];
    }

    /**
     * Size, share in text and the width of the decorative bar.
     *
     * @param int $bytes Bytes.
     * @param int $share Result of overview_builder::share().
     * @return array{size: string, share: string, width: int}
     */
    private static function measure(int $bytes, int $share): array {
        return [
            'size' => display_size($bytes),
            'share' => self::share_text($share),
            'width' => $share === overview_builder::LESS_THAN_ONE ? 1 : $share,
        ];
    }

    /**
     * Sentences of the summary for the state of the diagnostic (FR-020).
     *
     * @return string[]
     */
    private function summary_sentences(): array {
        $view = $this->view;
        if (!$view['hasresult']) {
            $key = in_array($view['state'], [diagnostic_state::QUEUED, diagnostic_state::RUNNING], true)
                ? 'diagnostic:preparing' : 'diagnostic:notavailable';
            return [get_string($key, 'block_coursecare')];
        }
        $sentences = [];
        $sentences[] = $view['state'] === diagnostic_state::NO_FILES
            ? get_string('diagnostic:nofiles', 'block_coursecare')
            : get_string('overview:total', 'block_coursecare', display_size($view['total']));
        $date = userdate($view['timecompleted'], get_string('strftimedatetimeshort', 'langconfig'));
        $sentences[] = get_string('diagnostic:calculatedon', 'block_coursecare', $date);
        if ($view['outdated']) {
            $sentences[] = get_string('diagnostic:mayoutdated', 'block_coursecare');
        } else if ($view['failed']) {
            $sentences[] = get_string('diagnostic:updatefailed', 'block_coursecare');
        }
        return $sentences;
    }

    /**
     * Text of a share (proportion), always as words or digits, never only as a shape (FR-016).
     *
     * @param int $share Result of overview_builder::share().
     * @return string
     */
    protected static function share_text(int $share): string {
        return $share === overview_builder::LESS_THAN_ONE
            ? get_string('overview:shareless', 'block_coursecare')
            : get_string('overview:share', 'block_coursecare', $share);
    }
}
