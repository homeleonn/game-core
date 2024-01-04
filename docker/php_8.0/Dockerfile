FROM php:8.0-fpm-alpine

RUN apk add --update --no-cache libintl build-base zlib-dev imagemagick patch bash htop acl apk-cron \
          curl ca-certificates   gettext-dev libcurl libgcrypt-dev libjpeg-turbo-dev \
        libwebp-tools libpng-dev libjpeg freetype-dev libmcrypt-dev  linux-headers libxml2-dev  \
        mysql-client openssh-client libcurl

RUN apk add --update --no-cache icu-libs icu-dev

RUN apk add --no-cache --virtual .deps \
                imagemagick \
                imagemagick-libs \
                imagemagick-dev


RUN apk add --no-cache --virtual .build-deps \
                $PHPIZE_DEPS  && \
        docker-php-ext-configure bcmath --enable-bcmath && \
        docker-php-ext-configure gettext && \
        docker-php-ext-configure gd --with-freetype --with-jpeg && \
        docker-php-ext-configure pcntl --enable-pcntl && \
        docker-php-ext-configure intl --enable-intl && \
        docker-php-ext-configure opcache --enable-opcache && \
        docker-php-ext-install sockets pdo_mysql opcache bcmath gd gettext intl pcntl calendar mysqli exif && \
        pecl install imagick redis && \
        docker-php-ext-enable sockets pdo_mysql opcache bcmath gd gettext intl pcntl calendar mysqli exif imagick redis
