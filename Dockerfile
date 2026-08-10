FROM php:8.1-apache

# Install common PHP extensions often needed by apps
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev zip libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
