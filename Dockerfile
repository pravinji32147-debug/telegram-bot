FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Enable apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . /var/www/html/

# Copy and install composer
COPY --from=composer:2.6 /usr/bin/composer /usr/local/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ensure logs dir
RUN mkdir -p /var/www/html/logs && chown -R www-data:www-data /var/www/html/logs

EXPOSE 80
CMD ["apache2-foreground"]
