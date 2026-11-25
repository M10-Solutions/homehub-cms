FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libzip-dev libxml2-dev unzip git pandoc weasyprint fonts-dejavu-core \
    && docker-php-ext-install mbstring zip dom \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Match ownership of bind-mounted HomeHub files.
# Apache keeps running as www-data; only its numeric UID/GID change.
ARG HOMEHUB_UID=1000
ARG HOMEHUB_GID=1001
RUN set -eu; \
    groupmod -g "${HOMEHUB_GID}" www-data; \
    usermod -u "${HOMEHUB_UID}" -g "${HOMEHUB_GID}" www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/homehub
COPY composer.* ./
COPY src/lib/ ./src/lib/
RUN if [ ! -f composer.lock ]; then echo "WARNING: composer.lock missing; build is not reproducible" >&2; fi \
    && composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
COPY src/ ./src/
COPY docs/templates/ ./docs/templates/
COPY docker/create-admin.php ./docker/create-admin.php
COPY bin/create-admin.php ./bin/create-admin.php
COPY docker/entrypoint.sh /usr/local/bin/homehub-entrypoint
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini "$PHP_INI_DIR/conf.d/homehub.ini"
RUN chmod 755 /usr/local/bin/homehub-entrypoint \
    && find /var/www/homehub/src -type d -exec chmod 755 {} + \
    && find /var/www/homehub/src -type f -exec chmod 644 {} + \
    && mkdir -p data/config data/content data/upload \
    && chown -R www-data:www-data data
ENTRYPOINT ["/usr/local/bin/homehub-entrypoint"]
CMD ["apache2-foreground"]
EXPOSE 80
