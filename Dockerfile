FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libicu-dev libpq-dev libzip-dev unzip \
    && docker-php-ext-install intl pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --no-scripts --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
