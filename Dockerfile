# Dockerfile

FROM php:8.2-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Symfony app
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
