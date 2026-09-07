#!/bin/bash
set -euo pipefail

# Entrypoint scripts run as root. Drop to the application user so files created
# here (bootstrap cache, daily log files, ...) are owned by that user, matching
# the cron and web runtimes. Otherwise the first crawl:active creates a
# root-owned storage/logs/crawler-<date>.log that the hourly cron (which runs as
# application) then cannot append to -> "could not be opened in append mode".
gosu application /usr/local/bin/php /app/artisan package:discover --ansi
gosu application /usr/local/bin/php /app/artisan storage:link
gosu application /usr/local/bin/php /app/artisan migrate --force
gosu application /usr/local/bin/php /app/artisan crawl:active
