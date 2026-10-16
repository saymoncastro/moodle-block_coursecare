#!/bin/zsh
# Generate the 5,001 benchmark courses on one stack: bench-generate.sh pg|maria
S=${0:a:h}; db=$1
G="php blocks/coursecare/tools/dev/bench/generate.php --mode=metadata"
$S/mb $db mkdir -p /var/www/moodledata/bench
run() { # name profile seed courses [files]
  local extra=""; [ -n "$5" ] && extra="--files=$5"
  local t0=$(date +%s)
  $S/mb $db $=G --profile=$2 --seed=$3 --courses=$4 $=extra --manifest=/var/www/moodledata/bench/$1.json | tail -1
  echo "$1: $(( $(date +%s) - t0 )) s"
}
echo "start $(date -u +%FT%TZ)"
for i in 1 2 3 4 5 6 7 8 9; do run small-$i small 10$i 500; done
run medium medium 201 449
run medium-5k medium 202 1 5000
run large large 301 48
run large-20k large 302 1 20000
run large-50k large 303 1 50000
run extreme-200k extreme 401 1 200000
echo "end $(date -u +%FT%TZ)"
$S/mb $db php -r 'define("CLI_SCRIPT",1); require "config.php"; echo "courses with block: ", $DB->count_records_sql("SELECT COUNT(DISTINCT ctx.instanceid) FROM {block_instances} bi JOIN {context} ctx ON ctx.id = bi.parentcontextid WHERE bi.blockname = ?", ["coursecare"]), ", files: ", $DB->count_records("files"), "\n";'
echo "generated $db"
