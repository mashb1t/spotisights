FROM composer AS composer

# copying the source directory and install the dependencies with composer
COPY ./src/composer.json ./composer.json
COPY ./src/composer.lock ./composer.lock

# run composer install to install the dependencies
RUN composer install \
  --optimize-autoloader \
  --no-interaction \
  --no-progress

# continue stage build with the desired image and copy the source including the
# dependencies downloaded by composer
FROM trafex/php-nginx:2.4.0

COPY ./image-files /

COPY --chown=nginx --from=composer /app /var/www/html

WORKDIR /var/www/html

RUN mkdir ./sessions

COPY ./src/app ./app
COPY ./src/public ./public
COPY ./src/scripts ./scripts

USER root

RUN cat /cronjobs/docker-cron > /etc/crontabs/root

USER nobody
