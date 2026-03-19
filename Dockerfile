FROM php:8.2-apache  # or your preferred PHP version

# Install MySQLi extension
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ... rest of your config