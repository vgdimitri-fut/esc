FROM php:8.2-fpm

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y nginx && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html

COPY nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html

CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]