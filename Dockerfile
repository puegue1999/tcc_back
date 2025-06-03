FROM webdevops/php-nginx:8.1

RUN curl -O https://nginx.org/keys/nginx_signing.key && apt-key add ./nginx_signing.key

# Install packages
RUN apt-get update && apt-get install -y \
    git \
    zip \
    curl \
    sudo \
    unzip \
    libicu-dev \
    libbz2-dev \
    libpng-dev \
    libjpeg-dev \
    libmcrypt-dev \
    libreadline-dev \
    libfreetype6-dev \
    g++

# Apache configuration
# ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
# COPY .DOCKER_FILES/php-fpm/000-default.conf /etc/apache2/sites-available
# RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
# RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# RUN a2enmod rewrite headers

# Common PHP Extensions
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
    docker-php-ext-install pdo_mysql;

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN pecl install xdebug-3.4.2 \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-install zip

RUN docker-php-ext-install gd

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Ensure PHP logs are captured by the container
ENV LOG_CHANNEL=stderr

# Copy code and run composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV WEB_DOCUMENT_ROOT /app/public
# ENV APP_ENV production
WORKDIR /app
COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev

# Optimizing Configuration loading
# RUN php artisan config:cache
# Optimizing Route loading
# RUN php artisan route:cache
# Optimizing View loading
RUN php artisan view:cache

RUN chown -R application:application .

# Ensure the entrypoint file can be run
# RUN chmod +x /var/www/tmp/docker-entrypoint.sh
# ENTRYPOINT ["/var/www/tmp/docker-entrypoint.sh"]

# The default apache run command
# CMD ["apache2-foreground"]