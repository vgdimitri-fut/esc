FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Disable all MPM modules first
RUN a2dismod mpm_worker mpm_event mpm_prefork 2>/dev/null || true

# Then enable only prefork
RUN a2enmod mpm_prefork

COPY . /app

WORKDIR /app

CMD ["apache2-foreground"]