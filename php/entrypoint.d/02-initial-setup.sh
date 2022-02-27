#!/bin/bash
set -euo pipefail

/usr/local/bin/php /app/artisan migrate
/usr/local/bin/php /app/artisan crawl:active
