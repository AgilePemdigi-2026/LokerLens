# Image aplikasi web LokerLens (Laravel + Apache).
# Dibangun dari folder web/:  docker build -t lokerlens-web ./web
#
# Saat container mulai, docker/entrypoint.sh menjalankan migrate --force, storage:link,
# dan cache konfigurasi. Folder storage/ (termasuk berkas bukti) harus dipasang sebagai
# volume supaya tidak hilang saat deploy ulang. Panduan lengkap: README.md bagian "Deploy".

# ─── Tahap 1: paket PHP (tanpa paket pengembangan) ────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Autoloader dibuat di tahap akhir, setelah seluruh kode tersalin
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# ─── Tahap 2: tampilan (CSS/JS dengan Vite) ───────────────────────────────────
FROM node:24-slim AS aset
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Tailwind juga memindai view paginasi Laravel di vendor/ (lihat tailwind.config.js)
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ─── Tahap 3: image yang dijalankan ───────────────────────────────────────────
FROM php:8.4-apache

# Ekstensi PHP: pdo_mysql (database), opcache (lebih cepat). unzip untuk composer.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

# Pengaturan PHP produksi + batas unggah berkas bukti (aplikasi: maks. 10 MB per berkas)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/lokerlens.ini"

# Apache: folder publik Laravel = public/, aktifkan mod_rewrite untuk route Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Kode aplikasi, paket PHP, dan hasil build tampilan
COPY . .
COPY --from=vendor /app/vendor vendor
COPY --from=aset /app/public/build public/build

# Autoloader + package:discover (script composer). Folder yang ditulis Laravel milik www-data.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/lokerlens-entrypoint
RUN chmod +x /usr/local/bin/lokerlens-entrypoint

EXPOSE 80

# Route /up bawaan Laravel menjawab 200 jika aplikasi berjalan
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/up') === false ? 1 : 0);"

ENTRYPOINT ["lokerlens-entrypoint"]
CMD ["apache2-foreground"]
