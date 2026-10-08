FROM php:8.3-apache

# The web root is the src folder.
COPY src/ /var/www/html/

# Apache runs as www-data, which must be able to write the demo state (orders.json).
# The storage folder lives next to the web root, so it is not reachable from the browser.
RUN mkdir -p /var/www/storage && chown www-data:www-data /var/www/storage

EXPOSE 80
