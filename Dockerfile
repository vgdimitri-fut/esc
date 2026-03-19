FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Disable any extra MPMs that might be loaded
RUN a2dismod mpm_worker mpm_event 2>/dev/null || true

COPY . /app

WORKDIR /app

CMD ["apache2-foreground"]