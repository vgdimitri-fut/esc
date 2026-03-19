FROM php:8.2-apache

RUN set -eux; \

apt-get update; \

apt-get install -y git unzip;

COPY . /app

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN /usr/bin/composer require --no-interaction firebase/php-jwt

EXPOSE 80

CMD ["apache2-foreground"]