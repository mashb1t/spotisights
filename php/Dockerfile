FROM webdevops/php-nginx-dev:8.1

RUN mkdir /app/sessions

COPY ./entrypoint.d /entrypoint.d

COPY ./src/composer.json /app/composer.json
COPY ./src/composer.lock /app/composer.lock

RUN composer install -d /app

COPY ./cronjobs /cronjobs
RUN crontab /cronjobs/docker-cron

COPY ./src/app /app/app
COPY ./src/public /app/public
COPY ./src/scripts /app/scripts

WORKDIR /app
