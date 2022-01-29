#!/usr/bin/env sh

printenv | sed 's/^\(.*\)$/export \1/g' > /var/www/html/environment
