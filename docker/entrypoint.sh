#!/bin/bash
# Digital Smart Class container entrypoint.
# - Repairs Apache MPM configuration (some base images enable two MPMs).
# - Honors the platform-provided PORT (Railway/Render/etc. inject one).
# - Optionally imports the database seed on first boot when DB_IMPORT=1.
set -e

# --- Apache hardening -------------------------------------------------------
# mod_php only works with mpm_prefork. If any other MPM sneaks in (for example
# from a base image or platform layer), Apache aborts with:
#   AH00534: apache2: Configuration error: More than one MPM loaded.
# Disabling the alternatives here makes the container self-healing.
if command -v a2dismod >/dev/null 2>&1; then
    a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
    a2enmod mpm_prefork >/dev/null 2>&1 || true
fi

# --- Port handling ----------------------------------------------------------
# Apache's stock config listens on 80. Hosting platforms usually expose the app
# on the port given by $PORT, so rewrite the listeners to match.
APACHE_PORT="${PORT:-80}"
if [ -f /etc/apache2/ports.conf ]; then
    sed -i "s/^Listen .*/Listen ${APACHE_PORT}/" /etc/apache2/ports.conf
fi
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
    sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${APACHE_PORT}>/" \
        /etc/apache2/sites-available/000-default.conf
fi
export APACHE_PORT
echo "Apache will listen on port ${APACHE_PORT}."

# --- Optional database seed import -----------------------------------------
if [ -n "$DB_HOST" ] && [ "$DB_IMPORT" = "1" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        if mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; then
            echo "MySQL is ready."
            break
        fi
        sleep 2
    done

    mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" \
        -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME:-digital_smart_class}\` CHARACTER SET utf8mb4;"

    TABLES=$(mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" \
        -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME:-digital_smart_class}';")

    if [ "${TABLES:-0}" -eq 0 ]; then
        echo "Importing database seed..."
        mysql -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -p"$DB_PASS" \
            "${DB_NAME:-digital_smart_class}" < /var/www/html/database/digital_smart_class.sql
    else
        echo "Database already contains tables; skipping seed import."
    fi
fi

exec "$@"
