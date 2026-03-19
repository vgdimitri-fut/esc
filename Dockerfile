FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

# Copy to /app instead of /var/www/html
COPY . /app/

# Tell Apache to serve from /app
RUN sed -i 's|/var/www/html|/app|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80