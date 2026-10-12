<?php
/**
 * Regenerates .htaccess from the database's Friendly URL setting. Run by
 * docker/entrypoint.sh as www-data on every start, unconditionally: unlike
 * the modules/room-types steps, .htaccess lives in the code filesystem, not
 * a persisted volume, so it does not survive a redeploy and must be rebuilt
 * every time, the same way the domain is kept in step on every start.
 *
 * Lives outside the web root (see Dockerfile); it must never be reachable by URL.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require '/var/www/html/config/config.inc.php';

if (!Configuration::get('PS_REWRITING_SETTINGS')) {
    exit(0);
}

if (Tools::generateHtaccess(_PS_ROOT_DIR_.'/.htaccess')) {
    echo "[generate-htaccess] .htaccess written (Friendly URLs)\n";
    exit(0);
}

echo "[generate-htaccess] ERROR: could not write .htaccess\n";
exit(1);
