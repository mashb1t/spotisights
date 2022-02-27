#!/bin/bash
set -euo pipefail

/usr/local/bin/php /app/artisan migrate --force
/usr/local/bin/php /app/artisan crawl:active
