#!/bin/bash
set -euo pipefail

xargs -0 bash -c 'printf "export %q\n" "$@"' -- < /proc/1/environ > /etc/environment
