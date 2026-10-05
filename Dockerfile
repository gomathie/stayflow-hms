# Salisberg (QloApps-based) application image
FROM php:8.1-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
        libxml2-dev libzip-dev libcurl4-openssl-dev libonig-dev libicu-dev \
        unzip curl default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql mysqli soap zip intl mbstring opcache \
    && a2enmod rewrite headers expires deflate remoteip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-salisberg.ini
COPY docker/apache.conf /etc/apache2/conf-enabled/zz-salisberg.conf
COPY docker/entrypoint.sh /usr/local/bin/salisberg-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html

# - strip CRLF in case the script was checked out on Windows
# - keep a pristine copy of the volume-backed dirs so empty mounts can be seeded
RUN sed -i 's/\r$//' /usr/local/bin/salisberg-entrypoint \
    && chmod +x /usr/local/bin/salisberg-entrypoint \
    && mkdir -p /data /usr/src/salisberg-seed \
    && cp -a img upload download /usr/src/salisberg-seed/ \
    && chown www-data:www-data /data

VOLUME ["/data", "/var/www/html/img", "/var/www/html/upload", "/var/www/html/download"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=300s --retries=5 \
    CMD curl -s -o /dev/null http://127.0.0.1/ || exit 1

ENTRYPOINT ["salisberg-entrypoint"]
CMD ["apache2-foreground"]
