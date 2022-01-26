FROM webdevops/php-nginx-dev:8.1

RUN mkdir /app/sessions

COPY ./src/composer.json /app/composer.json
COPY ./src/composer.lock /app/composer.lock

RUN composer install -d /app

COPY ./src/app /app/app
COPY ./src/public /app/public

WORKDIR /app
