<?php
// Temporary SC-004 report (benchmark only; removed after use). Read-only.
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');
global $DB;

$start = (int)($argv[1] ?? 0); // Loop start (Unix time).
$pct = function (array $v, float $p): float {
    sort($v);
    return $v ? $v[(int)min(count($v) - 1, max(0, ceil($p * count($v)) - 1))] : 0.0;
};
$stats = function (array $v) use ($pct): array {
    return $v ? ['n' => count($v), 'mean' => round(array_sum($v) / count($v), 4), 'median' => round($pct($v, 0.5), 4),
        'p95' => round($pct($v, 0.95), 4), 'p99' => round($pct($v, 0.99), 4), 'max' => round(max($v), 4)] : ['n' => 0];
};

// Profiles from the manifests.
$profile = [];
$expected = [];
foreach (glob('/var/www/moodledata/bench/*.json') as $file) {
    $name = basename($file, '.json');
    $group = preg_replace('/-\d+$|-\d+k$/', '', $name);
    foreach (json_decode(file_get_contents($file), true) as $courseid => $values) {
        $profile[(int)$courseid] = $group;
        $expected[(int)$courseid] = $values;
    }
}

// Eligibility and final states.
$eligible = $DB->get_fieldset_sql("SELECT DISTINCT ctx.instanceid FROM {block_instances} bi JOIN {context} ctx
    ON ctx.id = bi.parentcontextid AND ctx.contextlevel = 50 WHERE bi.blockname = 'coursecare'");
$eligible = array_map('intval', $eligible);
$diag = $DB->get_records('block_coursecare_diag', null, 'courseid', '*');
$bycourse = [];
foreach ($diag as $d) {
    $bycourse[(int)$d->courseid] = $d;
}
$out = ['eligible' => count($eligible), 'diagnostics' => count($diag)];
$out['states'] = [
    'completed' => count(array_filter($diag, fn($d) => $d->timecompleted !== null && (int)$d->requeststatus === 0)),
    'queued' => count(array_filter($diag, fn($d) => (int)$d->requeststatus === 1)),
    'running' => count(array_filter($diag, fn($d) => (int)$d->requeststatus === 2)),
    'lastattemptfailed' => count(array_filter($diag, fn($d) => (int)$d->lastattemptfailed === 1)),
    'everfailed' => count(array_filter($diag, fn($d) => (int)$d->failurecount > 0)),
    'nofiles' => count(array_filter($diag, fn($d) => $d->filecount !== null && (int)$d->filecount === 0)),
    'partial' => count(array_filter($diag, fn($d) => (int)$d->partial === 1)),
];
$out['eligible_without_diagnostic'] = count(array_diff($eligible, array_keys($bycourse)));
$out['diagnostics_not_eligible'] = count(array_diff(array_keys($bycourse), $eligible));

// Timeline from timecompleted.
$completed = array_filter(array_map(fn($d) => $d->timecompleted === null ? null : (int)$d->timecompleted, $diag));
if ($completed) {
    $out['first_completed'] = gmdate('c', min($completed));
    $out['last_completed'] = gmdate('c', max($completed));
    if ($start) {
        $out['elapsed_seconds_to_last'] = max($completed) - $start;
    }
    $perhour = [];
    foreach ($completed as $t) {
        $h = gmdate('Y-m-d H:00', $t);
        $perhour[$h] = ($perhour[$h] ?? 0) + 1;
    }
    ksort($perhour);
    $out['completed_per_hour'] = $perhour;
}

// Ad hoc tasks from the task log.
$durations = [];
$byprofile = [];
$outcomes = [];
$done = [];
$intervals = [];
$reads = [];
$writes = [];
$failedtasks = 0;
$rs = $DB->get_recordset_select('task_log', "classname = :c", ['c' => 'block_coursecare\\task\\analyse_course'], 'timestart',
    'id, timestart, timeend, dbreads, dbwrites, result, output');
foreach ($rs as $r) {
    if ((int)$r->result !== 0) {
        $failedtasks++;
    }
    if (!preg_match('/block_coursecare: course (\d+), outcome (\w+)/', $r->output, $m)) {
        continue;
    }
    [$courseid, $outcome] = [(int)$m[1], $m[2]];
    $outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;
    $seconds = (float)$r->timeend - (float)$r->timestart;
    $intervals[] = [(float)$r->timestart, (float)$r->timeend, $courseid];
    if ($outcome === 'done') {
        $done[$courseid] = ($done[$courseid] ?? 0) + 1;
        $durations[] = $seconds;
        $byprofile[$profile[$courseid] ?? 'other'][] = $seconds;
        $reads[] = (int)$r->dbreads;
        $writes[] = (int)$r->dbwrites;
    }
}
$rs->close();
$out['adhoc'] = ['outcomes' => $outcomes, 'failed_tasks' => $failedtasks,
    'courses_analysed' => count($done), 'courses_analysed_more_than_once' => count(array_filter($done, fn($n) => $n > 1)),
    'task_seconds' => $stats($durations), 'dbreads' => $stats($reads), 'dbwrites' => $stats($writes)];
foreach ($byprofile as $p => $v) {
    $out['adhoc']['task_seconds_by_profile'][$p] = $stats($v);
}
// Overlap: maximum simultaneous analyses, and any overlap for the same course.
$events = [];
foreach ($intervals as [$s, $e, $c]) {
    $events[] = [$s, 1];
    $events[] = [$e, -1];
}
usort($events, fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
$cur = 0;
$max = 0;
foreach ($events as [$t, $d]) {
    $cur += $d;
    $max = max($max, $cur);
}
$samecourse = 0;
$percourse = [];
foreach ($intervals as [$s, $e, $c]) {
    $percourse[$c][] = [$s, $e];
}
foreach ($percourse as $list) {
    usort($list, fn($a, $b) => $a[0] <=> $b[0]);
    for ($i = 1; $i < count($list); $i++) {
        if ($list[$i][0] < $list[$i - 1][1]) {
            $samecourse++;
        }
    }
}
$out['adhoc']['max_simultaneous_analyses'] = $max;
$out['adhoc']['same_course_overlaps'] = $samecourse;

// Scheduled task runs.
$runs = [];
foreach ($DB->get_records_select('task_log', "classname = :c", ['c' => 'block_coursecare\\task\\queue_analyses'], 'timestart',
        'id, timestart, timeend, result, output') as $r) {
    preg_match('/(\d+) queued/', $r->output, $m);
    $runs[] = ['start' => gmdate('H:i:s', (int)$r->timestart), 'seconds' => round((float)$r->timeend - (float)$r->timestart, 3),
        'queued' => (int)($m[1] ?? -1), 'result' => (int)$r->result];
}
$out['queue_analyses'] = ['runs' => count($runs), 'max_seconds' => $runs ? max(array_column($runs, 'seconds')) : 0,
    'detail' => $runs];
$out['all_task_failures_in_log'] = $DB->count_records_select('task_log', 'result <> 0');

// Integrity of the stored indicators.
$bad = [];
$cats = $DB->get_records_sql("SELECT diagid, SUM(filecount) AS fc, SUM(logicalbytes) AS lb FROM {block_coursecare_cat}
    WHERE category IN ('material', 'submission', 'other', 'unclassified') GROUP BY diagid");
$acts = $DB->get_records_sql("SELECT diagid, SUM(filecount) AS fc, SUM(logicalbytes) AS lb, SUM(largecount) AS lc
    FROM {block_coursecare_act} GROUP BY diagid");
$links = $DB->get_records_sql("SELECT diagid, COUNT(1) AS n FROM {block_coursecare_large} GROUP BY diagid");
$compared = 0;
$mismatch = [];
foreach ($diag as $d) {
    if ($d->timecompleted === null) {
        continue;
    }
    $id = $d->id;
    $fc = (int)$d->filecount;
    $lb = (int)$d->logicalbytes;
    if ((int)$d->uniquebytes > $lb || (int)$d->uniquecount > $fc || (int)$d->largecount > $fc
            || (int)$d->largeuniquecount > (int)$d->largecount) {
        $bad['unique_or_large_above_total'][] = (int)$d->courseid;
    }
    if ($fc > 0 && (!isset($cats[$id]) || (int)$cats[$id]->fc !== $fc || (int)$cats[$id]->lb !== $lb)) {
        $bad['categories_differ_from_total'][] = (int)$d->courseid;
    }
    if ($fc > 0 && (!isset($acts[$id]) || (int)$acts[$id]->fc !== $fc || (int)$acts[$id]->lb !== $lb
            || (int)$acts[$id]->lc !== (int)$d->largecount)) {
        $bad['activities_differ_from_total'][] = (int)$d->courseid;
    }
    if (isset($links[$id]) && ((int)$links[$id]->n > 1000 || (int)$links[$id]->n > (int)$d->largecount)) {
        $bad['links_above_limit'][] = (int)$d->courseid;
    }
    if ((int)$d->methodversion !== 1) {
        $bad['methodversion'][] = (int)$d->courseid;
    }
    // Exactness against the generator manifest (every course, not a sample).
    $c = (int)$d->courseid;
    if (isset($expected[$c])) {
        $compared++;
        $e = $expected[$c];
        if ($fc !== (int)$e['files'] || $lb !== (int)$e['logicalbytes'] || (int)$d->uniquebytes !== (int)$e['uniquebytes']) {
            $mismatch[] = $c;
        }
    }
}
$out['integrity'] = array_map(fn($l) => ['count' => count($l), 'first' => array_slice($l, 0, 5)], $bad) ?: 'all checks hold';
$out['manifest'] = ['courses_in_manifests' => count($expected), 'compared' => $compared, 'mismatches' => count($mismatch),
    'first_mismatches' => array_slice($mismatch, 0, 10)];
$compareby = [];
foreach ($expected as $c => $e) {
    if (isset($bycourse[$c]) && $bycourse[$c]->timecompleted !== null) {
        $compareby[$profile[$c]] = ($compareby[$profile[$c]] ?? 0) + 1;
    }
}
$out['manifest']['compared_by_profile'] = $compareby;

// The files table was not changed by the analyses.
$out['files'] = ['count' => $DB->count_records('files'), 'maxid' => (int)$DB->get_field_sql('SELECT MAX(id) FROM {files}'),
    'maxtimemodified' => gmdate('c', (int)$DB->get_field_sql('SELECT MAX(timemodified) FROM {files}')),
    'sumfilesize' => (string)$DB->get_field_sql('SELECT SUM(filesize) FROM {files}')];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
