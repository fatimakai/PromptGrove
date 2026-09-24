#!/bin/sh
set -eu

cd /var/www/html

if [ "${APP_ENV:-}" = production ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo 'APP_KEY must be set in Render.' >&2
        exit 1
    fi
    if [ "${DB_CONNECTION:-}" != mysql ]; then
        echo 'The demo deployment requires DB_CONNECTION=mysql.' >&2
        exit 1
    fi
    if [ "${DEMO_OAUTH_ONLY:-}" != true ]; then
        echo 'The public demo requires DEMO_OAUTH_ONLY=true.' >&2
        exit 1
    fi
    if [ -z "${MYSQL_ATTR_SSL_CA:-}" ]; then
        if [ -z "${AIVEN_CA_CERT_BASE64:-}" ]; then
            echo 'Aiven CA certificate is required: set AIVEN_CA_CERT_BASE64 or MYSQL_ATTR_SSL_CA.' >&2
            exit 1
        fi
        printf '%s' "$AIVEN_CA_CERT_BASE64" | base64 -d > /tmp/aiven-ca.pem
        aiven_pem_header="$(head -n 1 /tmp/aiven-ca.pem | tr -d '\r')"
        if [ "$aiven_pem_header" = '-----BEGIN CERTIFICATE-----' ]; then
            echo 'Aiven CA decode check: PEM certificate header present.'
        else
            echo 'Aiven CA decode check: PEM certificate header missing.' >&2
        fi
        chown www-data:www-data /tmp/aiven-ca.pem
        chmod 600 /tmp/aiven-ca.pem
        export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem
    fi
    if [ ! -s "$MYSQL_ATTR_SSL_CA" ]; then
        echo 'Aiven CA certificate file is missing or empty.' >&2
        exit 1
    fi
fi

php artisan migrate --force
php artisan db:seed --class=DemoDeploymentSeeder --force
php artisan config:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

exec "$@"
