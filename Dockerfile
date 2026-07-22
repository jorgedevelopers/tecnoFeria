FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev \
        unzip \
        git \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

COPY . .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]
