# StarLoco-Web image (PHP 8.4).
#
#   runtime     PHP 8.4 + Apache + Composer dependencies; code bind-mounted (docker compose, development)
#   tailwind    Tailwind CSS standalone CLI, builds public/assets/app.css (starloco_web_assets service)
#   production  runtime + code + built CSS, self-contained
#   sprites     JPEXS FFDec + ImageMagick: exports shop item images from the client (starloco_web_sprites)
#   dev         runtime + Composer + dev dependencies (tests, PHPStan, php-cs-fixer, Rector): starloco_web_tools
#
# Dependencies live in /opt/starloco-web/vendor, outside the code directory, so a bind mount never hides them.

ARG TAILWIND_VERSION=4.3.3

FROM composer:2 AS vendor
WORKDIR /opt/starloco-web
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader \
        --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl --ignore-platform-req=ext-pdo_mysql \
    && composer dump-autoload --no-dev --optimize

FROM composer:2 AS vendor-dev
WORKDIR /opt/starloco-web
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts \
        --ignore-platform-req=ext-gd --ignore-platform-req=ext-intl --ignore-platform-req=ext-pdo_mysql \
    && composer dump-autoload

FROM debian:bookworm-slim AS tailwind
ARG TAILWIND_VERSION
ARG TARGETARCH
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl \
    && rm -rf /var/lib/apt/lists/* \
    && case "$TARGETARCH" in arm64) arch=arm64 ;; *) arch=x64 ;; esac \
    && curl -fsSL -o /usr/local/bin/tailwindcss "https://github.com/tailwindlabs/tailwindcss/releases/download/v${TAILWIND_VERSION}/tailwindcss-linux-${arch}" \
    && chmod +x /usr/local/bin/tailwindcss
COPY docker/build-assets.sh /usr/local/bin/build-assets
RUN chmod +x /usr/local/bin/build-assets
WORKDIR /app
ENTRYPOINT ["build-assets"]

FROM tailwind AS assets
COPY assets ./assets
COPY templates ./templates
COPY public/assets/app.js ./public/assets/app.js
RUN build-assets

FROM eclipse-temurin:21-jre AS sprites
ARG FFDEC_VERSION=26.2.1
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl unzip imagemagick \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL -o /tmp/ffdec.zip "https://github.com/jindrapetrik/jpexs-decompiler/releases/download/version${FFDEC_VERSION}/ffdec_${FFDEC_VERSION}.zip" \
    && unzip -q /tmp/ffdec.zip -d /opt/ffdec \
    && rm /tmp/ffdec.zip
COPY docker/export-item-images.sh /usr/local/bin/export-item-images
RUN chmod +x /usr/local/bin/export-item-images
ENTRYPOINT ["export-item-images"]

FROM php:8.4-apache AS runtime
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
ENV STARLOCO_VENDOR_DIR=/opt/starloco-web/vendor \
    APP_BASE_PATH=/dofus
WORKDIR /var/www/starloco-web

FROM runtime AS production
COPY docker/php-production.ini /usr/local/etc/php/conf.d/zzz-starloco-production.ini
COPY . /var/www/starloco-web
COPY --from=assets /app/public/assets/app.css /var/www/starloco-web/public/assets/app.css

FROM runtime AS dev
RUN apt-get update && apt-get install -y --no-install-recommends git unzip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=vendor-dev /opt/starloco-web/vendor /opt/starloco-web/vendor
ENV COMPOSER_VENDOR_DIR=/opt/starloco-web/vendor \
    PATH="/opt/starloco-web/vendor/bin:${PATH}"
ENTRYPOINT ["composer"]
CMD ["check"]
