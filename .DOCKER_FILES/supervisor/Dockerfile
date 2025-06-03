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

RUN pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install zip

RUN docker-php-ext-install gd

RUN apt update && apt install supervisor -y

RUN mkdir -p "/etc/supervisor/logs"

COPY supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]