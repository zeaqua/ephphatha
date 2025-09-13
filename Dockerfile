# ===== base =====
FROM php:8.3-fpm-alpine AS base
RUN apk add --no-cache git unzip bash icu-dev oniguruma-dev libpq-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql intl opcache \
    && apk del --no-network $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# ===== dev =====
FROM base AS dev

# ===== prod =====
FROM base AS prod
ENV APP_ENV=prod \
    COMPOSER_ALLOW_SUPERUSER=1

COPY ./app /var/www/html/app
WORKDIR /var/www/html/app

RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --optimize-autoloader \
 && php bin/console cache:clear --env=prod \
 && php bin/console cache:warmup --env=prod \
 && chown -R www-data:www-data var

FROM php:8.3-fpm-alpine AS runtime
RUN apk add --no-cache icu-dev oniguruma-dev libpq-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql intl opcache
WORKDIR /var/www/html/app
COPY --from=prod /var/www/html/app /var/www/html/app
USER www-data
CMD ["php-fpm"]
