FROM php:8.4-fpm

RUN apt-get update \
    && apt-get -y install libzip-dev zlib1g-dev git zip unzip libicu-dev \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \

RUN docker-php-ext-configure zip && docker-php-ext-install zip intl

# Install Xdebug only
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Optional: Add Xdebug config (adjust path if needed)
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Raise memory_limit above the 128M default so PHPStan's parallel workers fit
COPY ./docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini


# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

RUN chown -R www-data:www-data /var/www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
