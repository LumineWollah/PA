FROM php:fpm-alpine
RUN apk update && apk add --no-cache \
    php-xml \
    php-cli \
    php-mysqli \
    php-zip \
    unzip \
    git \
    && docker-php-ext-install opcache \
    && docker-php-ext-enable opcache

COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini