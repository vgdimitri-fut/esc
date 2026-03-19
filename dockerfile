FROM php:8.2-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo pdo_mysql

# Enable extensions
RUN docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80