FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY app/ /var/www/html/

EXPOSE 80
