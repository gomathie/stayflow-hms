#!/bin/bash
set -euo pipefail

cd /var/www/html
SETTINGS=/var/www/html/config/settings.inc.php
PERSISTED=/data/settings.inc.php
SEED=/usr/src/salisberg-seed
ADMIN_DIR="${ADMIN_DIR:-admin-salisberg}"

# Bump BRANDING_VERSION whenever a file in BRAND_FILES or BRAND_NAME changes.
BRANDING_VERSION=1
BRAND_NAME="Salisberg Hotels"
BRAND_FILES="logo.jpg logo_mail.jpg logo_invoice.jpg favicon.ico logo_stores.gif logo_stores.png qloapps@2x.png qloapps-login@2x.png qloapps-login-wink@2x.png prestashop-avatar.png"

# Run one SQL statement through PDO, the same driver the app uses.
# (The bundled MariaDB CLI rejects MySQL 8's self-signed certificate.)
db_query() {
    SQL="$1" php -r '
        $pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_NAME"), getenv("DB_USER"), getenv("DB_PASSWORD"),
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5));
        $pdo->exec(getenv("SQL"));'
}

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
    db_ready=0
    for i in $(seq 1 60); do
        if db_query "SELECT 1" >/dev/null 2>&1; then db_ready=1; break; fi
        sleep 2
    done
    if [ "$db_ready" != "1" ]; then
        echo "Database not reachable at ${DB_HOST} after 120s:" >&2
        db_query "SELECT 1" || true
        exit 1
    fi

    if [ ! -e "$SETTINGS" ] && [ "${AUTO_INSTALL:-1}" = "1" ]; then
        echo "First boot: running installer..."
        HOST="${PUBLIC_URL#*://}"; HOST="${HOST%%/*}"
        su -s /bin/bash www-data -c "php install/index_cli.php \
            --language=en --timezone='${TIMEZONE:-UTC}' --domain='$HOST' \
            --db_server='$DB_HOST' --db_name='$DB_NAME' --db_user='$DB_USER' --db_password='$DB_PASSWORD' \
            --db_clear=1 --prefix=qlo_ --engine=InnoDB \
            --name='${SHOP_NAME:-Salisberg Hotels}' --country='${SHOP_COUNTRY:-us}' \
            --firstname='${ADMIN_FIRSTNAME:-Admin}' --lastname='${ADMIN_LASTNAME:-Admin}' \
            --password='$ADMIN_PASSWORD' --email='$ADMIN_EMAIL' --newsletter=0 --license=0"
        if [ ! -s "$SETTINGS" ]; then
            echo "Installer did not produce $SETTINGS" >&2
            exit 1
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

    # Keep the shop domain and SSL flags in step with PUBLIC_URL, so moving to
    # a new domain is a variable change plus a redeploy.
    if [ -e "$SETTINGS" ] && [ "${SYNC_DOMAIN:-1}" = "1" ]; then
        HOST="${PUBLIC_URL#*://}"; HOST="${HOST%%/*}"
        if ! printf '%s' "$HOST" | grep -Eq '^[A-Za-z0-9.-]+(:[0-9]+)?$'; then
            echo "PUBLIC_URL has an invalid host: $PUBLIC_URL" >&2
            exit 1
        fi
        SSL=0
        if [ "${PUBLIC_URL#https://}" != "$PUBLIC_URL" ]; then SSL=1; fi
        db_query "UPDATE qlo_shop_url SET domain='$HOST', domain_ssl='$HOST' WHERE main=1"
        db_query "UPDATE qlo_configuration SET value='$HOST' WHERE name IN ('PS_SHOP_DOMAIN','PS_SHOP_DOMAIN_SSL')"
        db_query "UPDATE qlo_configuration SET value='$SSL' WHERE name IN ('PS_SSL_ENABLED','PS_SSL_ENABLED_EVERYWHERE')"
        echo "Shop domain set to $HOST (ssl=$SSL)"
    fi

    # Brand assets live in the img volume, which is only seeded once, so a new
    # logo in the repo would never reach an existing install. Re-apply them
    # whenever BRANDING_VERSION is bumped; between bumps, changes made in the
    # back office are left alone.
    if [ -e "$SETTINGS" ] && [ "$(cat /data/.branding-version 2>/dev/null)" != "$BRANDING_VERSION" ]; then
        for f in $BRAND_FILES; do
            cp -a "$SEED/img/$f" "img/$f"
        done
        chown www-data:www-data img/*.*
        db_query "UPDATE qlo_configuration SET value='$BRAND_NAME' WHERE name='PS_SHOP_NAME'"
        db_query "UPDATE qlo_shop SET name='$BRAND_NAME' WHERE id_shop=1"
        db_query "UPDATE qlo_configuration SET value='486' WHERE name='SHOP_LOGO_WIDTH'"
        db_query "UPDATE qlo_configuration SET value='260' WHERE name='SHOP_LOGO_HEIGHT'"
        # Drop the upstream vendor's links from the storefront
        db_query "UPDATE qlo_configuration SET value='' WHERE name IN ('BLOCKSOCIAL_FACEBOOK','BLOCKSOCIAL_TWITTER','BLOCKADVERT_LINK') AND value LIKE '%qloapps%'"
        # Cache-buster used in logo and favicon URLs
        db_query "UPDATE qlo_configuration SET value=UNIX_TIMESTAMP() WHERE name='PS_IMG_UPDATE_TIME'"
        echo "$BRANDING_VERSION" > /data/.branding-version
        echo "Branding v$BRANDING_VERSION applied ($BRAND_NAME)"
    fi
fi

exec docker-php-entrypoint "$@"
