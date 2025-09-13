FROM php:8.3-fpm-alpine

# Базові пакети й залежності для збірки розширень
RUN apk add --no-cache \
        git unzip bash icu-dev oniguruma-dev libpq-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql intl opcache \
    && apk del --no-network $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
