# ===========================================================
# Stage 1 — Base : PHP + extensions + Composer
# ===========================================================
FROM php:8.4-fpm-alpine AS base
# 1. Librairies système nécessaires aux extensions PHP
RUN apk add --no-cache \
icu-dev icu-data-full \
libzip-dev zlib-dev \
freetype-dev libjpeg-turbo-dev libpng-dev \
oniguruma-dev \
git acl \
autoconf g++ gcc make linux-headers
# 2. Extensions PHP (adaptez à votre projet)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install -j$(nproc) \
intl \
opcache \
pdo_mysql \
zip \
gd \
mbstring \
&& pecl install apcu \
&& docker-php-ext-enable apcu
# 3. Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# 4. Config PHP personnalisée
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
WORKDIR /app
COPY composer.json composer.lock ./
# ===========================================================
# Stage 2 — Dev (celui qu'on utilise au quotidien)
# ===========================================================
FROM base AS dev
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN composer install --no-scripts --no-interaction --prefer-dist
COPY . .
RUN composer run-script post-install-cmd || true
RUN mkdir -p var/cache var/log \
&& chown -R www-data:www-data var
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
# ===========================================================
# Stage 3 — Prod (pour le déploiement)
# ===========================================================
FROM base AS prod
ENV APP_ENV=prod
RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist
COPY . .
RUN composer run-script post-install-cmd || true \
&& php bin/console cache:warmup
RUN mkdir -p var/cache var/log \
&& chown -R www-data:www-data var
USER www-data
EXPOSE 9000
CMD ["php-fpm"]