# === STAGE 1: Build Frontend Assets ===
FROM node:22 AS frontend

WORKDIR /app

# Dependency layer first, cached across builds as long as the lockfile
# doesn't change.
COPY package.json package-lock.json ./
RUN npm ci

# Only what Vite actually reads — not the whole repo, so a change to
# app/ or routes/ doesn't bust this layer.
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

# === STAGE 2: Production PHP Environment (FrankenPHP) ===
FROM dunglas/frankenphp:php8.3

# poppler-utils provides the `pdftotext` binary spatie/pdf-to-text shells
# out to at runtime — not a PHP extension, easy to miss.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd zip intl bcmath pcntl opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Kopyahin ang pinakabagong Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependency layer first: only composer.json/composer.lock, so this layer
# stays cached across builds that don't touch dependencies. --no-scripts
# because artisan (and the rest of the app) isn't copied in yet, so any
# post-install hook that shells out to it would fail here.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Now the rest of the source.
COPY . .

# Built frontend assets from the first stage.
COPY --from=frontend /app/public/build ./public/build

# Regenerate the autoloader against the real app now that it's present.
# Deliberately not --no-scripts this time: composer.json's own
# post-autoload-dump hook (`php artisan package:discover --ansi`) needs to
# run so every package's service provider is registered — that hook was
# skipped above precisely because artisan didn't exist yet.
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# CRITICAL: Laravel needs to write to storage/ and bootstrap/cache/ —
# owned by the user the server actually runs as, not just world-writable.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Railway sets $PORT dynamically; 8080 is just the local-testing default.
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
