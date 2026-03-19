FROM php:8.2-fpm

# Install mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install nginx
RUN apt-get update && apt-get install -y nginx

# Nginx config
RUN echo 'server { \
    listen 80; \
    root /app; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

# Copy to /app
COPY . /app/

# Set permissions
RUN chown -R www-data:www-data /app/

# Better start script - wait for PHP-FPM to be ready
RUN printf '#!/bin/bash\n\
php-fpm -D\n\
sleep 2\n\
nginx -g "daemon off;"' > /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]