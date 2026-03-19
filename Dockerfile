FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Remove all MPM modules
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load

# Enable only prefork
RUN a2enmod mpm_prefork

COPY . /app

WORKDIR /app

CMD ["apache2-foreground"]