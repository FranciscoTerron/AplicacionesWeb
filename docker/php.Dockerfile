FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpq-dev \
    linux-headers \
    $PHPIZE_DEPS

# Extensiones PHP
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Usuario para desarrollo (opcional, para permisos)
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /var/www/backend

# Asegurar permisos
RUN chown -R app:app /var/www/backend

USER app
