FROM php:8.3-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend
