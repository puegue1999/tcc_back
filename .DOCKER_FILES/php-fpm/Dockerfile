FROM php:8.1-fpm

RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
    curl \
    libmemcached-dev \
    libz-dev \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libssl-dev \
    libwebp-dev \
    libxpm-dev \
    libmcrypt-dev \
    git \
    unzip \
    libzip-dev \
    zip \
    libonig-dev; \
    rm -rf /var/lib/apt/lists/*


RUN set -eux; \
    docker-php-ext-install pdo_mysql sockets;

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install zip \
    && docker-php-ext-install sockets

RUN docker-php-ext-install gd

CMD ["php-fpm"]

WORKDIR "/code"

EXPOSE 9000



