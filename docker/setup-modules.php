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

foreach (array('salisbergpay', 'salisbergguide', 'salisbergreports') as $name) {
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

// Settings added to Salisberg Pay in a later version (bank transfer) reach an existing install here
$pay = Module::getInstanceByName('salisbergpay');
if ($pay && Module::isInstalled('salisbergpay')) {
    if ($pay->ensureSettings()) {
        sb_log('payment settings in place');
    } else {
        sb_log('ERROR: payment settings could not be created');
        $failed = true;
    }
}

// Menu entries and the Hotel Staff and Hotel Manager profiles: created if missing, on every run
$guide = Module::getInstanceByName('salisbergguide');
if ($guide && Module::isInstalled('salisbergguide')) {
    if ($guide->ensureSetup()) {
        sb_log('guide menus, Hotel Staff and Hotel Manager profiles in place');
    } else {
        sb_log('ERROR: guide menus or staff profiles could not be created');
        $failed = true;
    }
}

// Bookings > Reports: after the profiles above exist, so the Hotel Manager can be given the page
$reports = Module::getInstanceByName('salisbergreports');
if ($reports && Module::isInstalled('salisbergreports')) {
    if ($reports->ensureSetup()) {
        sb_log('reports page in place');
    } else {
        sb_log('ERROR: reports page could not be created');
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

// Switched off on every deploy:
//  - bankwire, cheque: bank transfer is offered through Salisberg Pay, in the same way as Mobile Money
//  - qlohotelreview: guest reviews are not in use; keeping it off also keeps its
//    upload endpoint closed (CVE-2025-67325). Remove it from this list to use reviews.
foreach (array('bankwire', 'cheque', 'qlohotelreview') as $name) {
    if (Module::isInstalled($name) && Module::isEnabled($name)) {
        Module::getInstanceByName($name)->disable();
        sb_log("$name disabled");
    }
}

// Switched off ONCE, then left to the owner: the homepage "What our guests say"
// block ships with invented sample reviews. It is hidden until real ones are
// entered; an administrator can enable the module and its menu link again in the
// back office, and later deploys will not undo that.
$testimonialsFlag = '/data/.testimonials-hidden';
if (!file_exists($testimonialsFlag)) {
    if (Module::isInstalled('wktestimonialblock') && Module::isEnabled('wktestimonialblock')) {
        Module::getInstanceByName('wktestimonialblock')->disable();
        sb_log('wktestimonialblock disabled (sample reviews hidden)');
    }
    // The menu link that scrolls to the block would now lead nowhere
    Db::getInstance()->execute(
        'UPDATE `'._DB_PREFIX_.'htl_custom_navigation_link` SET `active` = 0
        WHERE `link` LIKE \'%#hotelTestimonialBlock\''
    );
    if (@file_put_contents($testimonialsFlag, date('c')) === false) {
        sb_log('ERROR: could not record that the testimonials block was hidden');
        $failed = true;
    }
}

exit($failed ? 1 : 0);
