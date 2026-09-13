# StarLoco-Web runtime: PHP 8.3 + Apache + Composer dependencies.
# The application code is bind-mounted at /var/www/html/dofus by StarLoco-Game/docker-compose.yml;
# dependencies live outside it (/opt/starloco-web/vendor) so the mount does not hide them.

FROM composer:2 AS vendor
WORKDIR /opt/starloco-web
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl --ignore-platform-req=ext-pdo_mysql \
    && composer dump-autoload --no-dev --optimize

FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl opcache pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-starloco.ini
COPY docker/apache.conf /etc/apache2/conf-available/starloco.conf
RUN a2enconf starloco

COPY --from=vendor /opt/starloco-web/vendor /opt/starloco-web/vendor
ENV STARLOCO_VENDOR_DIR=/opt/starloco-web/vendor

WORKDIR /var/www/html/dofus
