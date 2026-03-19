FROM php:8.2-apache

# Fix Apache MPM conflict
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load && \
    rm -f /etc/apache2/mods-enabled/mpm_*.conf && \
    ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Install dependencies
RUN set -eux; \
    apt-get update; \
    apt-get install -y git unzip

# Install mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

COPY . /app

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN /usr/bin/composer require --no-interaction firebase/php-jwt

EXPOSE 80

CMD ["apache2-foreground"]