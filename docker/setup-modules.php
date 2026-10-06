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

foreach (array('salisbergpay') as $name) {
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
