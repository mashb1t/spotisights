#!/usr/bin/env sh

# run entrypoint scripts
sh /entrypoint.d/01-write-cron-env.sh
sh /entrypoint.d/02-initial-crawl.sh

