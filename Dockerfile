FROM php:8.2-apache

RUN apt-get update -qq \
 && apt-get install -y -qq libpq-dev cron \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
