#!/bin/zsh
# SC-003 on one stack: 3 runs per course size (the first after restarting the database container) and the
# per-query measurement with plans. bench-sc003.sh pg|maria
S=${0:a:h}; db=$1; L=$S/bench-logs/$db
source /Users/saymoncastro/Projetos/coursecare-devenv/env-bench-$db.sh
for size in medium-5k large-20k large-50k extreme-200k; do
  id=$($S/mb $db php -r "echo array_key_first(json_decode(file_get_contents('/var/www/moodledata/bench/$size.json'), true));")
  echo "$size: course $id"
  $S/mbc $db restart db > /dev/null
  (cd /Users/saymoncastro/Projetos/coursecare-devenv/moodle-docker && bin/moodle-docker-wait-for-db > /dev/null 2>&1)
  for run in 1 2 3; do
    $S/mb $db php blocks/coursecare/cli/diagnostics.php --run --course=$id --json > $L/sc003-$size-run$run.json
    echo "  run $run: $(tr -d '\n ' < $L/sc003-$size-run$run.json)"
  done
  $S/mb $db php blocks/coursecare/tools/dev/bench/measure.php --course=$id --explain > $L/sc003-$size-measure.json
  $S/mb $db php blocks/coursecare/cli/diagnostics.php --show --course=$id --json > $L/sc003-$size-show.json
done
echo "done sc003 $db"
