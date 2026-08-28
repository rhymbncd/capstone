#!/bin/bash
set -euo pipefail

# Railway pins the start command to this script for every service (see
# railway.json), so the scheduler service flags itself with
# RUN_SCHEDULER=true: run the scheduler once and exit instead of booting
# the web server. The config/route/view cache and migrations belong to
# the web service, not this short-lived cron container.
if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    exec php artisan schedule:run
fi

# A bare command passed to the entrypoint is exec'd as-is (one-off tasks).
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Laravel's caches are built here, at container start, not in the
# Dockerfile — Railway's environment variables only exist at runtime, so
# caching config/routes/views at build time would bake in wrong (or
# missing) values for every deploy.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan migrate --force

# Exec (not run) so FrankenPHP becomes PID 1 and receives signals directly
# — otherwise SIGTERM on deploy/restart would hit this shell instead and
# the server wouldn't get a clean shutdown chance.
#
# Switched from `php-server` (no way to configure headers/compression) to
# a real Caddyfile so /build, /image, and /fonts get long-lived cache
# headers and text responses get compressed — see docker/Caddyfile.
exec frankenphp run --config /app/docker/Caddyfile --adapter caddyfile
