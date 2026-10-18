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
use block_coursecare\local\analysis\history_view;
use block_coursecare\local\analysis\overview_builder;
use block_coursecare\local\analysis\state_resolver;
use block_coursecare\local\analysis\storage_potential;
use block_coursecare\local\collector\assign_settings;
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

    /** @var array|null Potential of the assignments with file submissions (Feature 005); null without assignments. */
    private ?array $potential;

    /** @var array History points of the course within the retention, oldest first (Feature 006). */
    private array $history;

    /**
     * Constructor.
     *
     * @param array $view Course view.
     * @param \moodle_url $courseurl Course page.
     * @param array|null $potential Assignments with file submissions, in the order of the course: name (formatted),
     *     url, visible (to students) and result (storage_potential::evaluate()); null when the course has no
     *     assignment the user can see.
     * @param array $history History points (diagnostic_store::get_history()), oldest first.
     */
    public function __construct(array $view, \moodle_url $courseurl, ?array $potential = null, array $history = []) {
        $this->view = $view;
        $this->courseurl = $courseurl;
        $this->potential = $potential;
        $this->history = $history;
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
        $structure = (new course_structure())->get_activities($courseid);
        if ($record !== null && $state->hasresult) {
            $categories = $store->get_categories((int)$record->id);
            $activities = $store->get_activities((int)$record->id);
            $large = $store->get_large_materials_by_activity((int)$record->id);
        }
        $view = (new overview_builder())->build(
            $state,
            $record,
            $categories,
            $activities,
            $large,
            $state->hasresult ? $structure : []
        );
        $since = diagnostic_store::history_day(time()) - diagnostic_store::history_retention();
        return new self(
            $view,
            new \moodle_url('/course/view.php', ['id' => $courseid]),
            self::potential_for($courseid, $structure),
            $store->get_history($courseid, $since)
        );
    }

    /**
     * Potential of the assignments of the course that the user can see and that accept files, calculated now from
     * their current settings (Feature 005, research R1). Nothing is stored.
     *
     * @param int $courseid Course id.
     * @param array $structure Activities from course_structure::get_activities().
     * @return array|null As the constructor expects.
     */
    private static function potential_for(int $courseid, array $structure): ?array {
        $assigns = array_filter($structure, fn(\stdClass $cm): bool => $cm->modname === 'assign' && $cm->uservisible);
        if (!$assigns) {
            return null;
        }
        $settings = (new assign_settings())->for_course($courseid, array_keys($assigns));
        $potential = [];
        foreach ($assigns as $cmid => $cm) {
            $assign = $settings['assigns'][$cmid] ?? null;
            if ($assign !== null && $assign['enabled']) {
                $potential[] = ['name' => $cm->name, 'url' => $cm->url, 'visible' => $cm->visible,
                    'result' => storage_potential::evaluate($assign, $settings)];
            }
        }
        return $potential;
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
            'hashistory' => $this->history !== [],
            'history' => $this->history_data($output),
            'haspotential' => $this->potential !== null,
            'potential' => $this->potential_data(),
            'hastechnical' => $this->view['technical'] !== null,
            'technical' => $this->technical_data(),
        ];
    }

    /**
     * What was noticed by the suggestion S1, as one complete sentence: before the list of places when there is one,
     * otherwise pointing to the list under Large files (all the large materials are in activities without a name).
     * The count and the total size are emphasised, so that the facts are read at once; the sentence is HTML.
     *
     * @param int $count Large course materials.
     * @param string $threshold Formatted large-file threshold.
     * @param string $size Formatted total size.
     * @param bool $hasplaces Whether named places follow.
     * @return string
     */
    private function observed_sentence(int $count, string $threshold, string $size, bool $hasplaces): string {
        $key = 'suggestion:large:observed' . ($hasplaces ? '' : '_unnamed') . ($count === 1 ? '_one' : '');
        $a = (object)['count' => \html_writer::tag('strong', $count), 'threshold' => s($threshold),
            'size' => \html_writer::tag('strong', s($size))];
        return get_string($key, 'block_coursecare', $a);
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
            $places[] = ($place['kind'] === 'courselevel'
                ? ['name' => $str('overview:courselevel'), 'url' => $this->courseurl->out(false), 'hasurl' => true]
                : ['name' => $place['name'], 'url' => $place['url'], 'hasurl' => $place['url'] !== null])
                + ['size' => diagnostic_line::size($place['bytes'])];
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
            'observed' => $this->observed_sentence($suggestion['count'], $threshold, $size, $places !== []),
            'hasplaces' => $places !== [],
            'places' => $places,
            // Without a named place, the sentence itself points to the list under Large files.
            'more' => match ($places === [] ? 0 : $suggestion['more']) {
                0 => '',
                1 => $str('suggestion:large:more_one'),
                default => $str('suggestion:large:more', $suggestion['more']),
            },
            'truncated' => $suggestion['truncated'] ? $str('overview:large_truncated') : '',
            'why' => $str('suggestion:large:why'),
            'todo' => $str('suggestion:large:todo'),
            'options' => [
                ['text' => $str('suggestion:large:option1'), 'example' => $str('suggestion:large:option1_example')],
                ['text' => $str('suggestion:large:option2'), 'example' => ''],
                ['text' => $str('suggestion:large:option3'), 'example' => ''],
            ],
        ] + $data;
    }

    /**
     * The "How the files of your course have changed" section (Feature 006, contracts/history-ui.md): the latest
     * measurement, one neutral sentence about the change since the previous one, a chart of the recorded files by day
     * (no value on days without a measurement) and, collapsed, every measurement with its categories and the limits.
     *
     * @param \renderer_base $output Renderer (for the chart).
     * @return array heading, intro, meaning, latest, change, methodnote, haschart, chart, chartperiod, datatitle,
     *     columns, rows, limitstitle, limits; empty without points.
     */
    private function history_data(\renderer_base $output): array {
        $view = history_view::build($this->history);
        if ($view === null) {
            return [];
        }
        $str = fn(string $key, $a = null): string => get_string('history:' . $key, 'block_coursecare', $a);
        $date = fn(array $point): string => userdate((int)$point['timecompleted'], get_string('strftimedate', 'langconfig'));
        $size = fn($bytes): string => diagnostic_line::size((int)$bytes);
        $latest = $view['latest'];
        $previous = $view['previous'];
        $change = match ($view['change']) {
            'first' => $str('first'),
            'methodchanged' => $str('methodchanged', $date($latest)),
            'unchanged' => $str('unchanged', (object)['date' => $date($previous), 'to' => $size($latest['logicalbytes'])]),
            default => $str($view['change'], (object)['delta' => $size($view['delta']), 'date' => $date($previous),
                'from' => $size($previous['logicalbytes']), 'to' => $size($latest['logicalbytes'])]),
        };

        // A change of method earlier in the period is also said, after the sentence of the latest change.
        $methodnote = $view['methodchange'] !== null && $view['change'] !== 'methodchanged'
            ? $str('methodchanged', $date($view['methodchange'])) : '';

        $categories = array_keys(diagnostic_store::HISTORY_CATEGORIES);
        $columns = array_merge(
            [$str('col_date'), $str('col_total')],
            array_map(fn(string $key): string => get_string('overview:category:' . $key, 'block_coursecare'), $categories),
            [$str('col_unique')]
        );
        $rows = [];
        foreach ($view['points'] as $point) {
            $cells = [$size($point['logicalbytes'])];
            foreach (diagnostic_store::HISTORY_CATEGORIES as $field) {
                $cells[] = $size($point[$field] ?? 0);
            }
            $cells[] = $size($point['uniquebytes'] ?? 0);
            $rows[] = ['date' => $date($point), 'partial' => empty($point['partial']) ? '' : $str('partial'),
                'cells' => array_map(fn(string $cell): array => ['value' => $cell], $cells)];
        }

        $retention = format_time(diagnostic_store::history_retention());
        // A chart needs two measured days within its window.
        $haschart = count(array_filter($view['chart']['days'], fn($bytes): bool => $bytes !== null)) > 1;
        $latestdate = userdate((int)$latest['timecompleted'], get_string('strftimedatetimeshort', 'langconfig'));
        return [
            'heading' => $str('heading'),
            'intro' => $str('intro'),
            'meaning' => $str('meaning'),
            'latest' => $str('latest', $latestdate),
            'change' => $change,
            'methodnote' => $methodnote,
            'haschart' => $haschart,
            'chart' => $haschart ? $this->history_chart($output, $view) : '',
            'chartperiod' => $haschart && $view['chartcut'] ? $str('chartperiod') : '',
            'datatitle' => $str('data'),
            'columns' => array_map(fn(string $column): array => ['name' => $column], $columns),
            'rows' => $rows,
            'limitstitle' => $str('limits'),
            'limits' => array_map(fn(string $text): array => ['text' => $text], [$str('limit_start'), $str('limit_gaps'),
                $str('limit_day'), $str('limit_cause'), $str('limit_retention', $retention)]),
        ];
    }

    /**
     * The chart of the history: one series of bars, the recorded files of each day of the last 90 days up to the latest
     * measurement (history_view::CHART_DAYS), in the unit of the largest value; a day without a measurement has no
     * bar. Bars start at zero (the Chart.js of Moodle 4.5 ignores the minimum that core sends for a line chart, which
     * would start the axis near the values and enlarge small changes).
     *
     * @param \renderer_base $output Renderer.
     * @param array $view history_view::build().
     * @return string HTML of the core chart, without its automatic table (the section has its own).
     */
    private function history_chart(\renderer_base $output, array $view): string {
        $days = $view['chart']['days'];
        $max = max(1, max(array_map('intval', array_filter($days, fn($bytes) => $bytes !== null))));
        $units = ['kb', 'mb', 'gb', 'tb'];
        $power = min(count($units), max(1, (int)floor(log($max, 1024))));
        $divisor = 1024 ** $power;
        // First day of the chart: the first point plus the offset where the chart window starts.
        $first = (int)$view['points'][count($view['points']) - 1]['timeday'] + $view['chart']['start'] * DAYSECS;
        // Day and month while the history stays within one year; with the year when it crosses years, so that a
        // label never repeats (the retention goes up to 5 years).
        $last = (int)$view['points'][0]['timeday'];
        $sameyear = userdate($first + 12 * HOURSECS, '%Y') === userdate($last + 12 * HOURSECS, '%Y');
        $format = get_string($sameyear ? 'strftimedateshort' : 'strftimedatefullshort', 'langconfig');
        $labels = [];
        $values = [];
        foreach ($days as $offset => $bytes) {
            // Noon of the day, so that the label is the right date across a change of daylight saving time.
            $labels[] = userdate($first + $offset * DAYSECS + 12 * HOURSECS, $format);
            $values[] = $bytes === null ? null : round($bytes / $divisor, 2);
        }
        $chart = new \core\chart_bar();
        $chart->set_title(get_string('history:chart', 'block_coursecare'));
        $series = new \core\chart_series(get_string('history:series', 'block_coursecare'), $values);
        // The green of the course materials of the page (--block-coursecare-material).
        $series->set_color('#3f7d52');
        $chart->add_series($series);
        $chart->set_labels($labels);
        $yaxis = $chart->get_yaxis(0, true);
        $unit = get_string('size:' . $units[$power - 1], 'block_coursecare');
        $yaxis->set_label(get_string('history:axis', 'block_coursecare', $unit));
        $yaxis->set_min(0);
        return $output->render_chart($chart, false);
    }

    /**
     * The "Potential of assignments" section (Feature 005, contracts/potential-ui.md): what the settings of each
     * assignment allow, in the order of the course, with what the potential means and its limits. No total, no
     * ranking and no submission per assignment; the recorded storage only as the total of the course.
     *
     * @return array heading, intro, meaning, scope, recorded, hasitems, items, none and how.
     */
    private function potential_data(): array {
        if ($this->potential === null) {
            return [];
        }
        $str = fn(string $key, $a = null): string => get_string('potential:' . $key, 'block_coursecare', $a);
        $recorded = '';
        if ($this->view['hasresult']) {
            foreach ($this->view['categories'] ?? [] as $category) {
                if ($category['key'] === 'submission' && $category['bytes'] > 0) {
                    $date = userdate($this->view['timecompleted'], get_string('strftimedatetimeshort', 'langconfig'));
                    $recorded = $str('recorded', (object)['size' => diagnostic_line::size($category['bytes']),
                        'date' => $date]);
                }
            }
        }
        $items = array_map(fn(array $item): array => $this->potential_item($item), $this->potential);
        $how = ['how_formula', 'how_attempts', 'limit_dates', 'limit_ignore', 'limit_other', 'how_server',
            'limit_change'];
        return [
            'heading' => $str('heading'),
            'intro' => $str('intro'),
            'meaning' => $str('meaning'),
            'scope' => $str('scope'),
            'recorded' => $recorded,
            'hasitems' => $items !== [],
            'items' => $items,
            'none' => $items === [] ? $str('none') : '',
            'howtitle' => $str('how'),
            'how' => array_map(fn(string $key): array => ['text' => $str($key)], $how),
        ];
    }

    /**
     * One assignment of the potential section: its name, one sentence per factor and the result. A factor that is
     * not known, or that does not apply to the status, is left out.
     *
     * @param array $item name, url, visible and result.
     * @return array name, url, hasurl, hidden, factors and result.
     */
    private function potential_item(array $item): array {
        $str = fn(string $key, $a = null): string => get_string('potential:' . $key, 'block_coursecare', $a);
        $result = $item['result'];
        $status = $result['status'];
        $group = $result['unit'] === 'group';
        $filesize = diagnostic_line::size($result['filebytes']);
        $factors = [];
        if (in_array($status, ['potential', 'unlimited', 'nobody'], true)) {
            $key = 'files_' . ($group ? 'group' : 'participant') . ($result['files'] === 1 ? '_one' : '');
            $factors[] = $str($key, (object)['files' => $result['files'], 'size' => $filesize]);
            $factors[] = $str('limit_' . $result['limitsource']);
            // With nobody, an unknown number of attempts never reaches here: no attempts means unlimited.
            $factors[] = match ($result['attempts']) {
                null => $str('attempts_unlimited'),
                1 => $str('attempts_one'),
                default => $str('attempts', $result['attempts']),
            };
        }
        if ($status === 'potential' || $status === 'unlimited') {
            $key = 'units_' . ($group ? 'groups' : 'participants') . ($result['units'] === 1 ? '_one' : '');
            $factors[] = $str($key, $result['units']);
            $formula = (object)['units' => $result['units'], 'files' => $result['files'], 'size' => $filesize,
                'attempts' => $result['attempts']];
            $perattempt = $str('formula', $formula);
        }
        $text = match ($status) {
            'potential' => $str('value', (object)['size' => diagnostic_line::size($result['potential']),
                'formula' => $result['attempts'] > 1 ? $str('formula_attempts', $formula) : $perattempt]),
            'unlimited' => $str('unlimited', (object)['size' => diagnostic_line::size($result['perattempt']),
                'formula' => $perattempt]),
            default => $str($status),
        };
        return [
            'name' => $item['name'],
            'url' => $item['url'],
            'hasurl' => $item['url'] !== null,
            'hidden' => $item['visible'] ? '' : $str('hidden'),
            'factors' => array_map(fn(string $factor): array => ['text' => $factor], $factors),
            'result' => $text,
        ];
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
