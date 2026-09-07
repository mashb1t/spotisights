FROM webdevops/php-nginx-dev:8.1

COPY ./entrypoint.d /entrypoint.d

# cron service runs as root but delegates jobs to the application user via the
# username field in the crontab. The file must be installed as a *system* crontab
# (/etc/cron.d, populated from /opt/docker/etc/cron by the base image) — using
# `crontab <file>` installs it as root's personal crontab, which ignores the
# username field and runs every job as root.
COPY cronjobs/docker-cron /opt/docker/etc/cron/spotisights

USER application

WORKDIR /app

COPY src/composer.json ./composer.json
COPY src/composer.lock ./composer.lock

RUN composer install --prefer-dist --no-scripts --no-autoloader

COPY src/app/ ./app/
COPY src/bootstrap/ ./bootstrap/
COPY src/config/ ./config/
COPY src/database/ ./database/
COPY src/lang/ ./lang/
COPY src/public/ ./public/
COPY src/resources/ ./resources/
COPY src/routes/ ./routes/
COPY src/storage/ ./storage/
COPY src/artisan ./artisan

# permissions
USER root

RUN composer dump-autoload --optimize --no-scripts && \
    composer clear-cache

RUN chown -R application:application /app \
    && chown -R application:application /app/storage \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/public \
    && chmod -R 775 /app/bootstrap/cache
