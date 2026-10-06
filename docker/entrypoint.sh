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
# Bump CONTENT_VERSION to re-run the demo-content replacement below.
CONTENT_VERSION=1
BRAND_EMAIL="booking@salisberg.com"
# Bump CURRENCY_VERSION to re-run the currency step below.
CURRENCY_VERSION=2
# Bump MODULES_VERSION whenever docker/setup-modules.php changes.
MODULES_VERSION=5
# Bump SCHEMA_VERSION when a schema/settings step below is added or changed.
SCHEMA_VERSION=1

# Module folders that receive uploads (gallery, amenities, payment icons,
# guest photos). Add any other module upload folder here.
PERSIST_DIRS="modules/wkabouthotelblock/views/img/hotel_interior
modules/wkhotelfeaturesblock/views/img/hotels_features_img
modules/wkfooterpaymentblock/views/img/payment_img
modules/wktestimonialblock/views/img/hotels_testimonials_img
modules/qlohotelreview/views/img/review"
BRAND_FILES="logo.jpg logo_mail.jpg logo_invoice.jpg favicon.ico logo_stores.gif logo_stores.png qloapps@2x.png qloapps-login@2x.png qloapps-login-wink@2x.png prestashop-avatar.png"

# Run one SQL statement through PDO, the same driver the app uses.
# (The bundled MariaDB CLI rejects MySQL 8's self-signed certificate.)
db_query() {
    SQL="$1" php -r '
        $pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_NAME").";charset=utf8mb4", getenv("DB_USER"), getenv("DB_PASSWORD"),
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5));
        $st = $pdo->query(getenv("SQL"));
        if ($st && $st->columnCount()) { echo $st->fetchColumn(); }'
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
    # Some modules keep uploaded pictures inside their own code folder, which
    # is rebuilt from git on every deploy. Keep those folders in the data
    # volume and leave a symlink in their place.
    for p in $PERSIST_DIRS; do
        if [ ! -L "$p" ]; then
            mkdir -p "/data/persist/$p"
            cp -an "$p/." "/data/persist/$p/" || true
            rm -rf "$p"
            ln -s "/data/persist/$p" "$p"
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
        # These values are placed inside a quoted shell command below; a quote,
        # backslash or backtick in one of them would break out of it.
        for v in TIMEZONE HOST DB_HOST DB_NAME DB_USER DB_PASSWORD SHOP_NAME SHOP_COUNTRY \
                 ADMIN_FIRSTNAME ADMIN_LASTNAME ADMIN_PASSWORD ADMIN_EMAIL; do
            case "${!v:-}" in
                *\'*|*\"*|*\\*|*\`*|*\$*)
                    echo "$v contains a quote, backslash, backtick or dollar sign; use letters, digits and simple punctuation" >&2
                    exit 1 ;;
            esac
        done
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

    # One-off repair: installs made before PERSIST_DIRS existed lost the sample
    # pictures on their first redeploy. Rebuild them from each module's
    # dummy_img folder, the same source the installer uses. Folders that
    # already hold pictures are not touched.
    if [ -e "$SETTINGS" ] && [ ! -e /data/.sample-images-restored ]; then
        for p in $PERSIST_DIRS; do
            if ! ls "$p"/*.jpg >/dev/null 2>&1 && [ -d "$(dirname "$p")/dummy_img" ]; then
                SRC="$(dirname "$p")/dummy_img" DST="$p" php -r '
                    foreach (glob(getenv("SRC")."/*.{jpg,png}", GLOB_BRACE) as $f) {
                        $im = @imagecreatefromstring(file_get_contents($f));
                        if (!$im) { continue; }
                        $w = imagesx($im); $h = imagesy($im);
                        $out = imagecreatetruecolor($w, $h);
                        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
                        imagecopy($out, $im, 0, 0, 0, 0, $w, $h);
                        imagejpeg($out, getenv("DST")."/".pathinfo($f, PATHINFO_FILENAME).".jpg", 90);
                    }'
                echo "Restored sample pictures in $p"
            fi
        done
        chown -R www-data:www-data /data/persist
        touch /data/.sample-images-restored
    fi

    # Replace the installer's demo identity ("Hotel Prime", hotelprime@htl.com)
    # with ours. Every statement only touches values that still hold the demo
    # text, so anything already edited in the back office is left alone.
    if [ -e "$SETTINGS" ] && [ "$(cat /data/.content-version 2>/dev/null)" != "$CONTENT_VERSION" ]; then
        for demo in "The Hotel Prime" "Hotel Prime"; do
            for target in \
                "qlo_configuration:value" "qlo_configuration_lang:value" "qlo_meta_lang:title" \
                "qlo_htl_branch_info_lang:hotel_name" "qlo_htl_branch_info_lang:short_description" \
                "qlo_htl_branch_info_lang:description" "qlo_htl_branch_info_lang:policies" \
                "qlo_cms_lang:content" "qlo_category_lang:name" \
                "qlo_address:alias" "qlo_address:company" "qlo_address:lastname" "qlo_address:firstname"; do
                t="${target%%:*}"; c="${target##*:}"
                db_query "UPDATE $t SET $c = REPLACE($c, '$demo', '$BRAND_NAME') WHERE $c LIKE '%$demo%'"
            done
        done
        # The homepage title is "<meta title> - <shop name>"; avoid saying the name twice
        db_query "UPDATE qlo_meta_lang SET title='Hotels & Hospitality' WHERE title='$BRAND_NAME'"
        db_query "UPDATE qlo_category_lang SET link_rewrite='salisberg-hotels' WHERE link_rewrite='the-hotel-prime'"
        db_query "UPDATE qlo_configuration SET value='$BRAND_EMAIL' WHERE name IN ('PS_SHOP_EMAIL','WK_CUSTOMER_SUPPORT_EMAIL') AND value='hotelprime@htl.com'"
        db_query "UPDATE qlo_htl_branch_info SET email='$BRAND_EMAIL' WHERE email='hotelprime@htl.com'"
        echo "$CONTENT_VERSION" > /data/.content-version
        echo "Content v$CONTENT_VERSION applied (demo identity replaced)"
    fi

    # Schema and settings our security backports rely on. This must run before
    # Apache starts: the bcrypt password hashes written by the patched code are
    # 60 characters and would be truncated in the stock 32-character columns.
    if [ -e "$SETTINGS" ] && [ "$(cat /data/.schema-version 2>/dev/null)" != "$SCHEMA_VERSION" ]; then
        for t in qlo_customer qlo_employee qlo_referrer; do
            len="$(db_query "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = 'passwd'")"
            if [ -n "$len" ] && [ "$len" -lt 60 ]; then
                db_query "ALTER TABLE $t MODIFY passwd varchar(60) NOT NULL"
                echo "Widened $t.passwd from $len to 60"
            fi
        done
        # Back office sessions: 12 hours instead of 20 days (only if still at the stock value)
        db_query "UPDATE qlo_configuration SET value='12' WHERE name='PS_COOKIE_LIFETIME_BO' AND value='480'"
        echo "$SCHEMA_VERSION" > /data/.schema-version
        echo "Schema step v$SCHEMA_VERSION done"
    fi

    # Shop currency: Ghana cedi. The installer creates one default currency
    # (US dollar or euro, depending on which localisation pack it found).
    # Relabel that row instead of adding a second one, so carts, payment-module
    # permissions and the default-currency setting keep pointing at it and no
    # exchange rate is involved. Skipped once any order exists, because that
    # would relabel money already charged.
    if [ -e "$SETTINGS" ] && [ "$(cat /data/.currency-version 2>/dev/null)" != "$CURRENCY_VERSION" ]; then
        if [ "$(db_query "SELECT COUNT(*) FROM qlo_orders")" != "0" ]; then
            echo "Currency step skipped: orders already exist"
        else
            ghs_id="$(db_query "SELECT id_currency FROM qlo_currency WHERE iso_code='GHS' AND deleted=0 ORDER BY id_currency LIMIT 1")"
            if [ -z "$ghs_id" ]; then
                ghs_id="$(db_query "SELECT value FROM qlo_configuration WHERE name='PS_CURRENCY_DEFAULT'")"
            fi
            if printf '%s' "$ghs_id" | grep -Eq '^[0-9]+$'; then
                db_query "UPDATE qlo_currency SET name='Ghana Cedi', iso_code='GHS', iso_code_num='936', sign='GH₵', blank=0, format=1, decimals=1, conversion_rate=1, active=1, deleted=0 WHERE id_currency=$ghs_id"
                db_query "UPDATE qlo_currency_shop SET conversion_rate=1 WHERE id_currency=$ghs_id"
                db_query "UPDATE qlo_configuration SET value='$ghs_id' WHERE name='PS_CURRENCY_DEFAULT'"
                # One currency only: a second one would need a maintained exchange rate
                db_query "UPDATE qlo_currency SET active=0 WHERE id_currency<>$ghs_id"
                # Payment modules must be allowed to take it
                db_query "INSERT IGNORE INTO qlo_module_currency (id_module, id_shop, id_currency) SELECT DISTINCT id_module, id_shop, $ghs_id FROM qlo_module_currency"
                db_query "UPDATE qlo_cart SET id_currency=$ghs_id"
            fi
        fi
        echo "$CURRENCY_VERSION" > /data/.currency-version
        echo "Currency step v$CURRENCY_VERSION done; default currency is now: $(db_query "SELECT c.iso_code FROM qlo_currency c JOIN qlo_configuration k ON k.name='PS_CURRENCY_DEFAULT' AND k.value=c.id_currency")"
    fi

    # Install/enable our own modules and switch off unused payment methods.
    # A failure is reported but does not stop the site from starting; the
    # step is retried on the next start until it succeeds.
    if [ -e "$SETTINGS" ] && [ "$(cat /data/.modules-version 2>/dev/null)" != "$MODULES_VERSION" ]; then
        if su -s /bin/bash www-data -c "php /usr/local/share/salisberg/setup-modules.php"; then
            echo "$MODULES_VERSION" > /data/.modules-version
            echo "Modules step v$MODULES_VERSION done"
        else
            echo "WARNING: modules step failed; will retry on next start" >&2
        fi
    fi
fi

exec docker-php-entrypoint "$@"
