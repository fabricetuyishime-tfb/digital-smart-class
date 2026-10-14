# Digital Smart Class — production image
# PHP 8.2 + Apache + MySQL extensions, serving the project from the web root.
FROM php:8.2-apache

# System dependencies + PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Apache modules required for clean routing and .htaccess rules
RUN a2enmod rewrite headers

# Project files
COPY . /var/www/html/

# Apache virtual host
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Entrypoint helper
COPY docker/entrypoint.sh /usr/local/bin/dsc-entrypoint.sh
RUN chmod +x /usr/local/bin/dsc-entrypoint.sh

# Writable upload locations
RUN mkdir -p /var/www/html/uploads/videos \
             /var/www/html/uploads/documents \
             /var/www/html/uploads/presentations \
             /var/www/html/uploads/course-thumbnails \
             /var/www/html/uploads/teacher-documents \
             /var/www/html/uploads/profiles \
             /var/www/html/uploads/payments \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dsc-entrypoint.sh"]
CMD ["apache2-foreground"]
