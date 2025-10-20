FROM php:8.4-fpm-alpine3.20

# Security hardening: Update all packages and remove unnecessary packages
RUN apk upgrade --no-cache && \
    apk add --no-cache --upgrade openssl ca-certificates && \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Install system dependencies
RUN apk add --no-cache \
    git curl zip unzip libzip-dev libpng-dev oniguruma-dev libxml2-dev \
    && rm -rf /var/cache/apk/*

# PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    autoconf g++ make \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apk del .build-deps

# Install Composer securely
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

# Create user for the application and clean up
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www && \
    # Clean up unnecessary files
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /usr/share/man /usr/share/doc

# Create and set working directory
WORKDIR /var/www
RUN chown -R www:www /var/www

# Switch to non-root user
USER www

# Copy composer files first for better caching
COPY --chown=www:www composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-autoloader --no-scripts --no-progress --no-interaction

# Copy app
COPY --chown=www:www . .

# Generate autoload
RUN composer dump-autoload --optimize

# Set permissions for Laravel directories
RUN chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Security: Remove sensitive information and set secure defaults
RUN rm -f /var/www/.env.example && \
    find /var/www -name "*.md" -delete && \
    find /var/www -name ".git*" -delete 2>/dev/null || true

EXPOSE 9000

# Use non-root user and secure PHP-FPM
USER www
CMD ["php-fpm"]
