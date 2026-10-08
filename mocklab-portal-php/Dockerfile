FROM php:8.3-apache

# The app lives in the Apache document root.
COPY . /var/www/html/

# Apache runs as www-data, which must be able to write the demo state (storage/orders.json).
RUN mkdir -p /var/www/html/storage && chown www-data:www-data /var/www/html/storage

EXPOSE 80
