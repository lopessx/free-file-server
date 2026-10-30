FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install mysqli mbstring \
    && a2enmod rewrite \
    && echo "upload_max_filesize = 5M\npost_max_size = 5M\nmax_execution_time = 30" > /usr/local/etc/php/conf.d/free-file-server.ini \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/files
