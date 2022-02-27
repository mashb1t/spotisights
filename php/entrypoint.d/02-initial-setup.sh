#!/bin/bash

/usr/local/bin/php /app/artisan migrate
/usr/local/bin/php /app/artisan crawl:active
