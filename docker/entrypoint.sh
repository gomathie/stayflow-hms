#!/bin/bash
set -euo pipefail

cd /var/www/html
SETTINGS=/var/www/html/config/settings.inc.php
PERSISTED=/data/settings.inc.php
SEED=/usr/src/salisberg-seed
ADMIN_DIR="${ADMIN_DIR:-admin-salisberg}"

if [ "${1:-}" = "apache2-foreground" ]; then
    # Volumes may arrive empty or root-owned (see coollabsio/coolify#4058):
    # seed them from the image and hand them to Apache before anything else.
    for d in img upload download; do
        if [ ! -e "$d/index.php" ]; then
            echo "Seeding empty volume: $d"
            cp -a "$SEED/$d/." "$d/"
        fi
    done
    chown -R www-data:www-data /data img upload download cache log
    chmod -R u+rwX,go+rX /data img upload download cache log

    # The back office refuses a folder literally named "admin" and would
    # rename it randomly; pin it to a known name instead.
    if [ -d admin ] && [ "$ADMIN_DIR" != "admin" ]; then
        rm -rf "$ADMIN_DIR"
        mv admin "$ADMIN_DIR"
    fi

    # Restore persisted settings after a redeploy
    if [ -f "$PERSISTED" ] && [ ! -e "$SETTINGS" ]; then
        ln -s "$PERSISTED" "$SETTINGS"
    fi

    echo "Waiting for database at ${DB_HOST}..."
    for i in $(seq 1 60); do
        mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --silent 2>/dev/null && break
        sleep 2
    done

    if [ ! -e "$SETTINGS" ] && [ "${AUTO_INSTALL:-1}" = "1" ]; then
        echo "First boot: running installer..."
        HOST="${PUBLIC_URL#*://}"; HOST="${HOST%%/*}"
        su -s /bin/bash www-data -c "php install/index_cli.php \
            --language=en --timezone='${TIMEZONE:-UTC}' --domain='$HOST' \
            --db_server='$DB_HOST' --db_name='$DB_NAME' --db_user='$DB_USER' --db_password='$DB_PASSWORD' \
            --db_clear=1 --prefix=qlo_ --engine=InnoDB \
            --name='${SHOP_NAME:-Salisberg}' --country='${SHOP_COUNTRY:-us}' \
            --firstname='${ADMIN_FIRSTNAME:-Admin}' --lastname='${ADMIN_LASTNAME:-Admin}' \
            --password='$ADMIN_PASSWORD' --email='$ADMIN_EMAIL' --newsletter=0 --license=0"
        if [ ! -s "$SETTINGS" ]; then
            echo "Installer did not produce $SETTINGS" >&2
            exit 1
        fi
        if [ "${PUBLIC_URL#https://}" != "$PUBLIC_URL" ]; then
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e \
                "UPDATE qlo_configuration SET value='1' WHERE name IN ('PS_SSL_ENABLED','PS_SSL_ENABLED_EVERYWHERE');"
        fi
        # Persist generated settings in the data volume
        mv "$SETTINGS" "$PERSISTED"
        ln -s "$PERSISTED" "$SETTINGS"
        echo "Installation complete."
    fi

    # The installer must not be reachable once the app is installed
    if [ -e "$SETTINGS" ]; then
        rm -rf install
    fi
fi

exec docker-php-entrypoint "$@"
