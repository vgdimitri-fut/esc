FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite (optional, but commonly needed)
RUN a2enmod rewrite

# Copy your PHP app
COPY . /app

WORKDIR /app

# Expose port 8080 (Railway default)
EXPOSE 8080