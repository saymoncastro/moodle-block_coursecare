#!/bin/zsh
# SC-004 on one stack: the real cron every minute until every course with the block has a completed analysis
# and no request is pending. Writes a progress CSV. bench-sc004.sh pg|maria
S=${0:a:h}; db=$1; L=$S/bench-logs/$db
P='define("CLI_SCRIPT",1); require "config.php";
$e = $DB->count_records_sql("SELECT COUNT(DISTINCT ctx.instanceid) FROM {block_instances} bi JOIN {context} ctx ON ctx.id = bi.parentcontextid WHERE bi.blockname = ? AND ctx.contextlevel = 50", ["coursecare"]);
$c = $DB->count_records_select("block_coursecare_diag", "timecompleted IS NOT NULL AND requeststatus = 0");
$q = $DB->count_records("block_coursecare_diag", ["requeststatus" => 1]);
$r = $DB->count_records("block_coursecare_diag", ["requeststatus" => 2]);
$f = $DB->count_records("block_coursecare_diag", ["lastattemptfailed" => 1]);
echo "$e,$c,$q,$r,$f";'
echo "time,eligible,completed,queued,running,failed" > $L/sc004-progress.csv
start=$(date +%s); echo "start $(date -u +%FT%TZ)"
while true; do
  $S/mb $db php admin/cli/cron.php >> $L/sc004-cron.log 2>&1 &
  sleep 60
  line=$($S/mb $db php -r "$P")
  echo "$(date -u +%FT%TZ),$line" >> $L/sc004-progress.csv
  IFS=, read e c q r f <<< "$line"
  if [ "$e" -gt 0 ] && [ "$c" -eq "$e" ] && [ "$q" -eq 0 ] && [ "$r" -eq 0 ]; then break; fi
  if [ $(( $(date +%s) - start )) -gt 108000 ]; then echo "stopped after 30 h"; break; fi
done
wait
echo "end $(date -u +%FT%TZ), elapsed $(( $(date +%s) - start )) s"
