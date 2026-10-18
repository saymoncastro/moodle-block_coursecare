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
use block_coursecare\local\recommendation\large_materials;

/**
 * The "Your course in numbers" page (contracts/overview-page.md).
 *
 * Receives the course view built by overview_builder and turns it into template data: every sentence comes complete
 * from the string catalogue, sizes from diagnostic_line::size() and dates from userdate(). No file record is read
 * here.
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
            'hero' => $this->hero_data(),
            'hassuggestionsection' => (bool)$this->view['hasresult'],
            'suggestions' => $this->suggestions_data(),
            'hascategories' => $this->view['categories'] !== null,
            'categories' => $this->categories_data(),
            'hasactivities' => $this->view['activities'] !== null,
            'activities' => $this->activities_data(),
            'haslarge' => $this->view['large'] !== null,
            'large' => $this->large_data(),
            'hastechnical' => $this->view['technical'] !== null,
            'technical' => $this->technical_data(),
        ];
    }

    /**
     * The suggestions section (Feature 004): suggestion S1 when it applies, otherwise one neutral sentence. Only with
     * a result; without one the section is not shown.
     *
     * @return array heading, and either the suggestion (basedon, title, labels, observed, places, more, truncated,
     *     why, todo, options) or none.
     */
    private function suggestions_data(): array {
        if (!$this->view['hasresult']) {
            return [];
        }
        $str = fn(string $key, $a = null): string => get_string($key, 'block_coursecare', $a);
        $data = ['heading' => $str('suggestions:heading'), 'hassuggestion' => false, 'none' => $str('suggestions:none')];
        $suggestion = large_materials::evaluate($this->view['large']);
        if ($suggestion === null) {
            return $data;
        }
        $places = [];
        foreach ($suggestion['places'] as $place) {
            $places[] = $place['kind'] === 'courselevel'
                ? ['name' => $str('overview:courselevel'), 'url' => $this->courseurl->out(false), 'hasurl' => true]
                : ['name' => $place['name'], 'url' => $place['url'], 'hasurl' => $place['url'] !== null];
        }
        $threshold = diagnostic_line::size($suggestion['threshold']);
        $size = diagnostic_line::size($suggestion['bytes']);
        $date = userdate($this->view['timecompleted'], get_string('strftimedatetimeshort', 'langconfig'));
        return [
            'hassuggestion' => true,
            'none' => '',
            'basedon' => $str('suggestions:basedon', $date),
            'title' => $str('suggestion:large:title'),
            'observedlabel' => $str('suggestion:observed'),
            'whylabel' => $str('suggestion:why'),
            'todolabel' => $str('suggestion:todo'),
            'observed' => $suggestion['count'] === 1
                ? $str('suggestion:large:observed_one', (object)['threshold' => $threshold, 'size' => $size])
                : $str('suggestion:large:observed', (object)['count' => $suggestion['count'], 'threshold' => $threshold,
                    'size' => $size]),
            'places' => $places,
            'more' => match ($suggestion['more']) {
                0 => '',
                1 => $str('suggestion:large:more_one'),
                default => $str('suggestion:large:more', $suggestion['more']),
            },
            'truncated' => $suggestion['truncated'] ? $str('overview:large_truncated') : '',
            'why' => $str('suggestion:large:why'),
            'todo' => $str('suggestion:large:todo'),
            'options' => [
                ['text' => $str('suggestion:large:option1')],
                ['text' => $str('suggestion:large:option2')],
                ['text' => $str('suggestion:large:option3')],
            ],
        ] + $data;
    }

    /**
     * Template data of the large files: totals of the course and large materials per activity.
     *
     * @return array
     */
    private function large_data(): array {
        $large = $this->view['large'];
        if ($large === null) {
            return [];
        }
        $row = fn(string $name, ?string $url, array $values): array => ['name' => $name, 'url' => $url,
            'hasurl' => $url !== null, 'size' => diagnostic_line::size($values['bytes']),
            'count' => get_string('overview:large_count', 'block_coursecare', $values['count'])];
        $rows = [];
        foreach ($large['materials'] as $item) {
            $rows[] = $row($item['name'], $item['url'], $item);
        }
        foreach (
            ['courselevel' => 'overview:courselevel', 'others' => 'overview:otheractivities',
                'removed' => 'overview:removed'] as $key => $string
        ) {
            if ($large[$key] !== null) {
                $rows[] = $row(get_string($string, 'block_coursecare'), null, $large[$key]);
            }
        }
        return [
            'heading' => get_string('overview:large', 'block_coursecare'),
            'summary' => $large['count'] > 0
                ? get_string('overview:large_summary', 'block_coursecare', (object)[
                    'threshold' => diagnostic_line::size($large['threshold']), 'count' => $large['count'],
                    'size' => diagnostic_line::size($large['bytes']),
                ])
                : get_string('overview:large_none', 'block_coursecare', diagnostic_line::size($large['threshold'])),
            'help' => get_string('overview:large_help', 'block_coursecare'),
            'hasmaterials' => $rows !== [],
            'materialsheading' => get_string('overview:large_materials', 'block_coursecare'),
            'materials' => $rows,
            'truncated' => $large['truncated'] ? get_string('overview:large_truncated', 'block_coursecare') : '',
        ];
    }

    /**
     * Template data of the technical details (collapsed by default).
     *
     * @return array
     */
    private function technical_data(): array {
        $technical = $this->view['technical'];
        if ($technical === null) {
            return [];
        }
        $str = fn(string $key, $a = null): string => get_string($key, 'block_coursecare', $a);
        $counted = fn(array $values): \stdClass => (object)['count' => $values['count'],
            'size' => diagnostic_line::size($values['bytes'])];
        $references = $technical['references'];
        $copies = $technical['copies'];
        $coverage = $technical['coverage'];
        return [
            'summary' => $str('overview:technical'),
            'unique' => $str('overview:unique', diagnostic_line::size($technical['unique']['bytes'])),
            'uniquehelp' => $str('overview:unique_help'),
            'references' => $str('overview:references'),
            'referenceshelp' => $str('overview:references_help'),
            'referencelines' => [
                ['text' => $str('overview:references_internal', $counted($references['internal']))],
                ['text' => $str('overview:references_external', $counted($references['external']))],
                ['text' => $str('overview:references_undetermined', $references['undetermined']['count'])],
            ],
            'copies' => $str('overview:copies'),
            'copieshelp' => $str('overview:copies_help'),
            'copylines' => [
                ['text' => $str('overview:copies_backup', $counted($copies['backup']))],
                ['text' => $str('overview:copies_recyclebin', $counted($copies['recyclebin']))],
                ['text' => $str('overview:copies_unique', diagnostic_line::size($copies['uniquebytes']))],
            ],
            'coverage' => $str('overview:coverage', (object)['count' => $coverage['count'],
                'size' => diagnostic_line::size($coverage['bytes']), 'share' => self::share_text($coverage['share'])]),
            'threshold' => $str('overview:threshold', diagnostic_line::size($technical['threshold'])),
            'notmeaning' => $str('overview:notmeaning'),
        ];
    }

    /**
     * Template data of the categories of the course.
     *
     * @return array
     */
    private function categories_data(): array {
        $rows = [];
        $total = (int)$this->view['total'];
        foreach ($this->view['categories'] ?? [] as $category) {
            $rows[] = [
                'key' => $category['key'],
                // Width of the segment in the stacked bar (decorative): the exact share, so the segments add up.
                'segment' => $total > 0 ? round(100 * $category['bytes'] / $total, 2) : 0,
                'name' => get_string('overview:category:' . $category['key'], 'block_coursecare'),
                'help' => get_string('overview:category:' . $category['key'] . '_help', 'block_coursecare'),
                'url' => null,
                'hasurl' => false,
            ] + self::measure($category['bytes'], $category['share']);
        }
        return [
            'heading' => get_string('overview:categories', 'block_coursecare'),
            'help' => get_string('overview:categories_help', 'block_coursecare'),
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
            'size' => diagnostic_line::size($bytes),
            'share' => self::share_text($share),
            'width' => $share === overview_builder::LESS_THAN_ONE ? 1 : $share,
        ];
    }

    /**
     * The opening of the page: the same parts as the block line (diagnostic_line::parts()), so that the volume is
     * seen at once, plus the coverage note of state H (FR-020).
     *
     * @return array
     */
    private function hero_data(): array {
        $view = $this->view;
        $state = new diagnostic_state(
            $view['state'],
            $view['hasresult'],
            $view['partial'],
            $view['total'],
            $view['timecompleted'],
            $view['filecount']
        );
        $hero = diagnostic_line::parts($state) + ['coverage' => ''];
        if ($view['partial'] && $view['technical'] !== null && $view['technical']['coverage']['bytes'] > 0) {
            $coverage = $view['technical']['coverage'];
            $hero['coverage'] = get_string('overview:partial', 'block_coursecare', (object)[
                'size' => diagnostic_line::size($coverage['bytes']),
                'share' => self::share_text($coverage['share']),
            ]);
        }
        return $hero;
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
