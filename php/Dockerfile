FROM webdevops/php-nginx-dev:8.1

COPY ./entrypoint.d /entrypoint.d

# keep in mind: cron service runs as root but delegates jobs to application user
COPY ./cronjobs /cronjobs
RUN crontab /cronjobs/docker-cron

USER application

WORKDIR /app

COPY ./src/composer.json /app/composer.json
COPY ./src/composer.lock /app/composer.lock

RUN composer install -d /app/

COPY ./src/app /app/app
COPY ./src/public /app/public
COPY ./src/scripts /app/scripts

USER root

RUN chown -R application:application /app
