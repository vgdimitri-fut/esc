FROM php:8.2-apache

# Aggressively fix MPM conflict
RUN a2dismod mpm_event mpm_worker mpm_prefork || true \
    && a2enmod mpm_prefork

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html/

EXPOSE 80