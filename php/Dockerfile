FROM webdevops/php-nginx-dev:8.1

COPY ./entrypoint.d /entrypoint.d

# keep in mind: cron service runs as root but delegates jobs to application user
COPY cronjobs /cronjobs
RUN crontab /cronjobs/docker-cron

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

RUN composer dump-autoload --optimize && \
    composer clear-cache

# laravel
# ---
RUN php artisan storage:link

# permissions
# ---
USER root

RUN chown -R application:application /app \
    && chown -R application:application /app/storage \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/public \
    && chmod -R 775 /app/bootstrap/cache
