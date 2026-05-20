# Sumbungan - PHP/Apache image
FROM php:8.1-apache

LABEL maintainer="Sumbungan"
LABEL description="Barangay Desk Integration System (PHP + MySQL)"

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo \
        pdo_mysql \
        gd \
        zip

RUN a2enmod rewrite headers

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sumbungan.ini

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/assets/uploads

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/ || exit 1

CMD ["apache2-foreground"]
