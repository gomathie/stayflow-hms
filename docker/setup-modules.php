<?php
/**
 * Installs and switches on the Salisberg modules, and switches off the payment
 * methods the hotel does not use. Run by docker/entrypoint.sh as www-data
 * whenever MODULES_VERSION changes. Safe to run repeatedly.
 *
 * Lives outside the web root (see Dockerfile); it must never be reachable by URL.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require '/var/www/html/config/config.inc.php';

$failed = false;

// Creating menu entries records permissions against the acting employee, so
// act as the first administrator.
$idAdmin = (int) Db::getInstance()->getValue(
    'SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` WHERE `id_profile` = '.(int) _PS_ADMIN_PROFILE_.' ORDER BY `id_employee`'
);
if ($idAdmin) {
    Context::getContext()->employee = new Employee($idAdmin);
}

function sb_log($message)
{
    echo '[setup-modules] '.$message."\n";
}

// Guests are in Ghana: the country must be active before a payment module is
// installed, because installation grants the module to the active countries.
$idGhana = (int) Country::getByIso('GH');
if ($idGhana) {
    $ghana = new Country($idGhana);
    if (!$ghana->active) {
        $ghana->active = 1;
        $ghana->update();
        sb_log('Ghana activated as a country');
    }
}

foreach (array('salisbergpay', 'salisbergguide') as $name) {
    $module = Module::getInstanceByName($name);
    if (!$module) {
        sb_log("ERROR: module $name not found");
        $failed = true;
        continue;
    }
    if (!Module::isInstalled($name)) {
        if ($module->install()) {
            sb_log("$name installed");
        } else {
            sb_log("ERROR: $name failed to install: ".implode('; ', $module->getErrors()));
            $failed = true;
            continue;
        }
    } elseif (!Module::isEnabled($name)) {
        $module->enable();
        sb_log("$name enabled");
    } else {
        sb_log("$name already installed");
    }
}

// Menu entries and the Hotel Staff profile: created if missing, on every run
$guide = Module::getInstanceByName('salisbergguide');
if ($guide && Module::isInstalled('salisbergguide')) {
    if ($guide->ensureSetup()) {
        sb_log('guide menus and Hotel Staff profile in place');
    } else {
        sb_log('ERROR: guide menus or Hotel Staff profile could not be created');
        $failed = true;
    }
}

// A payment module only shows to guests from countries it is granted to
$idPay = (int) Module::getModuleIdByName('salisbergpay');
if ($idPay && $idGhana) {
    foreach (Shop::getShops(true, null, true) as $idShop) {
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `'._DB_PREFIX_.'module_country` (`id_module`, `id_shop`, `id_country`)
            VALUES ('.(int) $idPay.', '.(int) $idShop.', '.(int) $idGhana.')'
        );
    }
}

// The hotel takes cash and Mobile Money only for now
foreach (array('bankwire', 'cheque') as $name) {
    if (Module::isInstalled($name) && Module::isEnabled($name)) {
        Module::getInstanceByName($name)->disable();
        sb_log("$name disabled");
    }
}

exit($failed ? 1 : 0);
