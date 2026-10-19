# CourseCare — Troubleshooting

| What you see | Likely cause | What to do |
| --- | --- | --- |
| The block always says "The file overview for this course is being prepared." | Cron is not running, or ad hoc tasks are waiting | Check Site administration > Server > Tasks; run `admin/cli/cron.php`; or run `blocks/coursecare/cli/diagnostics.php --run --course=<id>` |
| "The file overview for this course is not available yet." | The course has not been analysed yet (or the first analysis failed) | Wait for the next hourly run or request it with `--queue --course=<id>` |
| "The latest update could not be completed." | The last analysis failed; the previous result is kept | `cli/diagnostics.php --show --course=<id>` shows the failure code; retries happen automatically with an increasing wait |
| "This information may be out of date." | The last result is older than the out-of-date limit | Check cron; review the reanalysis interval and the out-of-date limit |
| Numbers differ from the disk usage of the server | CourseCare shows logical storage of the course's file records, not physical disk usage | See the [indicators](indicators.md) |
| A large part is "Not classified" | Files from plugin areas CourseCare does not recognise yet | They are still counted; the result is marked as partial coverage |
| The history has gaps | Days without an analysis (cron stopped, plugin disabled) | Nothing is estimated for those days; gaps are expected |
| Potential of assignments shows "cannot be calculated" | A setting could not be determined (for example no upload limit known) | Check the upload limits of the server, site, course and assignment |
| Non-editing teachers do not see the block | Default permissions | Grant `block/coursecare:view` if your site wants them to see it |
| The block shows on other pages but is empty | It is rendered only on the course main page | Use "Display on page types" = course main page |

On Moodle 5.1 and later, command-line paths start with `public/` (for example
`php public/blocks/coursecare/cli/diagnostics.php --show --course=42`).

Still stuck? Open an issue: https://github.com/saymoncastro/moodle-block_coursecare/issues
