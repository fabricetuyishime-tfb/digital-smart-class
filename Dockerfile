# Digital Smart Class — production image
# PHP 8.2 CLI + built-in server.
#
# Apache is intentionally NOT used here: generic Apache buildpacks enable two
# Multi-Processing Modules (mpm_event + mpm_prefork) and abort with
# "AH00534: More than one MPM loaded". PHP's built-in server has no MPM concept,
# so that failure mode cannot happen. docker/router.php provides the same
# routing and access rules that .htaccess gave us under Apache.
FROM php:8.2-cli

# System dependencies + PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mysqli

# Remove any Apache left over from a base image or platform layer, so Apache can
# never be started accidentally (belt-and-braces against the MPM error).
RUN (command -v a2dismod >/dev/null 2>&1 && a2dismod mpm_event mpm_worker >/dev/null 2>&1) || true

WORKDIR /var/www/html

# Project files
COPY . /var/www/html/

# Router + entrypoint
COPY docker/router.php /var/www/html/router.php
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

# A few workers so the built-in server can handle concurrent requests.
ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dsc-entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html", "/var/www/html/router.php"]
