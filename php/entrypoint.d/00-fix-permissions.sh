#!/bin/bash
set -euo pipefail

# fix permissions for volume mounts
chown -R application:application /app/sessions
