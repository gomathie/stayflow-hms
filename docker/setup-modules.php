<?php
/**
 * Installs and switches on the Salisberg modules, switches off the payment
 * methods the hotel does not use, and keeps Friendly URLs (no .php in
 * website links) switched on. Run by docker/entrypoint.sh as www-data
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

foreach (array('salisbergpay', 'salisbergguide', 'salisbergreports', 'salisbergfrontdesk', 'salisbergnotify', 'salisbergcleanup') as $name) {
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

// Bookings > Room board, guided walk-in, Today and Housekeeping: after the profiles exist
$desk = Module::getInstanceByName('salisbergfrontdesk');
if ($desk && Module::isInstalled('salisbergfrontdesk')) {
    if ($desk->ensureSetup()) {
        sb_log('front desk pages in place');
    } else {
        sb_log('ERROR: front desk pages could not be created');
        $failed = true;
    }
}

// Advanced Parameters > Data clean-up. SuperAdmin only, so no other profile is granted it.
$cleanup = Module::getInstanceByName('salisbergcleanup');
if ($cleanup && Module::isInstalled('salisbergcleanup')) {
    if ($cleanup->ensureSetup()) {
        sb_log('data clean-up page in place');
    } else {
        sb_log('ERROR: data clean-up page could not be created');
        $failed = true;
    }
}

// Administration > Integrations: the mNotify key. SuperAdmin only, so no other profile is granted it.
$notify = Module::getInstanceByName('salisbergnotify');
if ($notify && Module::isInstalled('salisbergnotify')) {
    if ($notify->ensureSetup()) {
        sb_log('integrations page in place');
    } else {
        sb_log('ERROR: integrations page could not be created');
        $failed = true;
    }
}

// Rooms & Services > Charges & Tax: after the profiles exist, so the Hotel Manager can be given it
if ($pay && Module::isInstalled('salisbergpay')) {
    if ($pay->ensureChargesPage()) {
        sb_log('Charges & Tax page in place');
    } else {
        sb_log('ERROR: Charges & Tax page could not be created');
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

// Modules deleted from the code (CLEANUP-PLAN.md batch 2): their database rows are
// removed here, because a module whose folder is gone cannot uninstall itself.
// Acts only when the folder is gone and the row still exists, and logs what it did
// (rule 22). Bookings keep their payment name: OrderHistory falls back to it.
$forget = array(
    'bankwire' => array('config' => array('BANK_WIRE_DETAILS', 'BANK_WIRE_OWNER', 'BANK_WIRE_ADDRESS', 'BANK_WIRE_CURRENCIES'), 'tables' => array()),
    'cheque' => array('config' => array('CHEQUE_NAME', 'CHEQUE_ADDRESS', 'CHEQUE_CURRENCIES'), 'tables' => array()),
    'qlochannelmanagerconnector' => array('config' => array(), 'tables' => array('qcmc_channel_manager_booking')),
    // Batch 4: Dashboard boxes for a web shop, not drawn on the Dashboard since 2026-11-09
    'dashactivity' => array('config' => array('DASHACTIVITY_CART_ABANDONED_MAX', 'DASHACTIVITY_CART_ABANDONED_MIN', 'DASHACTIVITY_CART_ACTIVE', 'DASHACTIVITY_VISITOR_ONLINE'), 'tables' => array()),
    'dashproducts' => array('config' => array('DASHPRODUCT_NBR_SHOW_BEST_SELLER', 'DASHPRODUCT_NBR_SHOW_LAST_ORDER', 'DASHPRODUCT_NBR_SHOW_MOST_VIEWED', 'DASHPRODUCT_NBR_SHOW_TOP_SEARCH'), 'tables' => array()),
    'dashgoals' => array('config' => array('PS_DASHGOALS_CURRENT_YEAR'), 'tables' => array()),
    'dashinsights' => array('config' => array(), 'tables' => array()),
    // Batch 3: shop statistics (the Stats menu has been hidden since 2026-11-09).
    // The graph and grid engines (graphnvd3, gridhtml) stay: the Stats screens load them by name.
    'statsbestcategories' => array('config' => array(), 'tables' => array()),
    'statsbestcustomers' => array('config' => array(), 'tables' => array()),
    'statsbestproducts' => array('config' => array(), 'tables' => array()),
    'statsbestvouchers' => array('config' => array(), 'tables' => array()),
    'statscatalog' => array('config' => array(), 'tables' => array()),
    'statscheckup' => array('config' => array(), 'tables' => array()),
    'statsdata' => array('config' => array('PS_STATSDATA_CUSTOMER_PAGESVIEWS', 'PS_STATSDATA_PAGESVIEWS', 'PS_STATSDATA_PLUGINS'), 'tables' => array()),
    'statsequipment' => array('config' => array(), 'tables' => array()),
    'statsforecast' => array('config' => array(), 'tables' => array()),
    'statslive' => array('config' => array(), 'tables' => array()),
    'statsnewsletter' => array('config' => array(), 'tables' => array()),
    'statsorigin' => array('config' => array(), 'tables' => array()),
    'statspersonalinfos' => array('config' => array(), 'tables' => array()),
    'statsproduct' => array('config' => array(), 'tables' => array()),
    'statsregistrations' => array('config' => array(), 'tables' => array()),
    'statssales' => array('config' => array(), 'tables' => array()),
    'statsvisits' => array('config' => array(), 'tables' => array()),
    'sekeywords' => array('config' => array('SEK_FILTER_KW', 'SEK_MIN_OCCURENCES'), 'tables' => array('sekeyword')),
    'pagesnotfound' => array('config' => array(), 'tables' => array('pagenotfound')),
    'qlostatsserviceproducts' => array('config' => array(), 'tables' => array()),
);
$db = Db::getInstance();
foreach ($forget as $name => $parts) {
    $idModule = (int) $db->getValue('SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = \''.pSQL($name).'\'', false);
    if (!$idModule || is_dir(_PS_MODULE_DIR_.$name)) {
        continue;
    }
    foreach (array('module_access', 'module_shop', 'module_group', 'module_country', 'module_currency', 'hook_module', 'hook_module_exceptions') as $table) {
        $db->delete($table, '`id_module` = '.$idModule);
    }
    $db->delete('module_preference', '`module` = \''.pSQL($name).'\'');
    foreach ((array) $db->executeS('SELECT `id_tab` FROM `'._DB_PREFIX_.'tab` WHERE `module` = \''.pSQL($name).'\'', true, false) as $tab) {
        foreach (array('tab_lang', 'access', 'tab_module_preference') as $table) {
            $db->delete($table, '`id_tab` = '.(int) $tab['id_tab']);
        }
        $db->delete('tab', '`id_tab` = '.(int) $tab['id_tab']);
    }
    foreach ($parts['config'] as $key) {
        Configuration::deleteByName($key);
    }
    foreach ($parts['tables'] as $table) {
        $exists = $db->executeS('SHOW TABLES LIKE \''.pSQL(_DB_PREFIX_.$table).'\'', true, false);
        if ($exists && !(int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.bqSQL($table).'`', false)) {
            $db->execute('DROP TABLE `'._DB_PREFIX_.bqSQL($table).'`');
        } elseif ($exists) {
            sb_log("$name: table $table kept, it holds rows");
        }
    }
    foreach ((array) $db->executeS('SELECT `id_meta` FROM `'._DB_PREFIX_.'meta` WHERE `page` LIKE \''.pSQL('module-'.$name.'-').'%\'', true, false) as $meta) {
        foreach (array('meta_lang', 'theme_meta', 'meta') as $table) {
            $db->delete($table, '`id_meta` = '.(int) $meta['id_meta']);
        }
    }
    $db->delete('module', '`id_module` = '.$idModule);
    sb_log("$name removed (module deleted from the code)");
}

// Switched off on every deploy:
//  - qlohotelreview: guest reviews are not in use; keeping it off also keeps its
//    upload endpoint closed (CVE-2025-67325). Remove it from this list to use reviews.
foreach (array('qlohotelreview') as $name) {
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

// Friendly URLs: website links read example.com/room-name instead of
// example.com/index.php?controller=product&id_product=12, with no .php in
// sight. The back office keeps its index.php links; docker/apache.conf
// takes index.php out of its address bar. Only the database setting is set here, once; the .htaccess file
// itself is regenerated on every start by docker/generate-htaccess.php,
// because it lives in the code filesystem, not a persisted volume, and does
// not survive a redeploy.
if (!Configuration::get('PS_REWRITING_SETTINGS')) {
    Configuration::updateValue('PS_REWRITING_SETTINGS', 1);
    sb_log('Friendly URLs switched on');
} else {
    sb_log('Friendly URLs already on');
}

// The installer stored the "Our Properties" menu link as an absolute
// index.php address of the domain it was installed on. Make it the plain
// relative path, so it has no .php and follows a change of domain (rule 22:
// only rows that still hold the old form are touched).
$propertiesPath = parse_url(Context::getContext()->link->getPageLink('our-properties'), PHP_URL_PATH);
if ($propertiesPath && strpos($propertiesPath, 'index.php') === false) {
    Db::getInstance()->execute(
        'UPDATE `'._DB_PREFIX_.'htl_custom_navigation_link`
        SET `link` = \''.pSQL($propertiesPath).'\'
        WHERE `link` LIKE \'%index.php?controller=our-properties%\''
    );
    sb_log('Our Properties menu link set to '.$propertiesPath.' ('.(int) Db::getInstance()->Affected_Rows().' row(s))');
}

exit($failed ? 1 : 0);
