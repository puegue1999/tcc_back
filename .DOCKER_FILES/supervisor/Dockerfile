FROM php:8.1-fpm

ENV VIRTUAL_ENV="/opt/venv"
ENV PATH="${VIRTUAL_ENV}/bin:$PATH"

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
    python3 \
    python3-venv \
    python3-dev \
    python3-pip \
    libonig-dev; \
    rm -rf /var/lib/apt/lists/*

# Criar venv e instalar qiskit dentro dele
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --upgrade pip setuptools wheel \
    && /opt/venv/bin/pip install qiskit qiskit-aer psycopg2-binary requests

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