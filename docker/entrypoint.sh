#!/bin/bash
# Digital Smart Class container entrypoint.
# Optionally imports the database seed on first boot when DB_IMPORT=1.
set -e

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
