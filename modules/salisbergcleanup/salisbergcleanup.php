<?php
/**
 * Data clean-up (Advanced Parameters > Data clean-up).
 *
 * Replaces the vendor's Data Cleaner (qlocleaner, removed 2026-11-20): its
 * "delete everything" buttons and "Clean & Optimize" are gone; its database
 * integrity check is kept here. Removing old data by kind and date is in SalisbergDataCleanup; the
 * page is AdminSalisbergCleanupController.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/SalisbergDataCleanup.php';

class Salisbergcleanup extends Module
{
    const TAB = 'AdminSalisbergCleanup';
    const TAB_PARENT = 'AdminTools';

    public function __construct()
    {
        $this->name = 'salisbergcleanup';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salisberg Hotels';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Data clean-up');
        $this->description = $this->l('Remove old drafts, carts, logs, messages and guests with no bookings, by date, and check the database.');
    }

    public function install()
    {
        return parent::install() && $this->ensureSetup();
    }

    /** Menu entry under Advanced Parameters (SuperAdmin only: no profile is granted it), the daily hook and settings */
    public function ensureSetup()
    {
        $this->registerHook('actionAdminControllerSetMedia');
        if (Configuration::get('SBCLEAN_AUTO') === false) {
            Configuration::updateValue('SBCLEAN_AUTO', 1);
        }
        if (Tab::getIdFromClassName(self::TAB)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::TAB;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName(self::TAB_PARENT);
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Data clean-up';
        }
        // add() also reports failure when only its permission bookkeeping fails
        // (no employee in context), so judge by whether the tab now exists
        $tab->add();

        return (bool) Tab::getIdFromClassName(self::TAB);
    }

    /** The configure link on the Modules page opens the clean-up page */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::TAB));
    }

    /** Runs the daily automatic clean-up from the first back office page of the day */
    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('ajax')) {
            return;
        }
        try {
            SalisbergDataCleanup::runAutomatic();
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('Data clean-up (automatic) failed: '.$e->getMessage(), 3, null, 'SalisbergDataCleanup', 0, true);
        }
    }

    public static function checkAndFix()
    {
        $db = Db::getInstance();
        $logs = array();

        // Remove doubles in the configuration
        $filtered_configuration = array();
        $result = $db->ExecuteS('SELECT * FROM '._DB_PREFIX_.'configuration');
        foreach ($result as $row) {
            $key = $row['id_shop_group'].'-|-'.$row['id_shop'].'-|-'.$row['name'];
            if (in_array($key, $filtered_configuration)) {
                $query = 'DELETE FROM '._DB_PREFIX_.'configuration WHERE id_configuration = '.(int)$row['id_configuration'];
                $db->Execute($query);
                $logs[$query] = 1;
            } else {
                $filtered_configuration[] = $key;
            }
        }
        unset($filtered_configuration);

        // Remove inexisting or monolanguage configuration value from configuration_lang
        $query = 'DELETE FROM `'._DB_PREFIX_.'configuration_lang`
        WHERE `id_configuration` NOT IN (SELECT `id_configuration` FROM `'._DB_PREFIX_.'configuration`)
        OR `id_configuration` IN (SELECT `id_configuration` FROM `'._DB_PREFIX_.'configuration` WHERE name IS NULL OR name = "")';
        if ($db->Execute($query)) {
            if ($affected_rows = $db->Affected_Rows()) {
                $logs[$query] = $affected_rows;
            }
        }

        // Simple Cascade Delete
        $queries = self::getCheckAndFixQueries();

        $queries = self::bulle($queries);
        foreach ($queries as $query_array) {
            // If this is a module and the module is not installed, we continue
            if (isset($query_array[4]) && !Module::isInstalled($query_array[4])) {
                continue;
            }

            $query = 'DELETE FROM `'._DB_PREFIX_.$query_array[0].'` WHERE `'.$query_array[1].'` NOT IN (SELECT `'.$query_array[3].'` FROM `'._DB_PREFIX_.$query_array[2].'`)';
            if ($db->Execute($query)) {
                if ($affected_rows = $db->Affected_Rows()) {
                    $logs[$query] = $affected_rows;
                }
            }
        }

        // _lang table cleaning
        $tables = Db::getInstance()->executeS('SHOW TABLES LIKE "'.preg_replace('/([%_])/', '\\$1', _DB_PREFIX_).'%_\\_lang"');
        foreach ($tables as $table) {
            $table_lang = current($table);
            $table = str_replace('_lang', '', $table_lang);
            $id_table = 'id_'.preg_replace('/^'._DB_PREFIX_.'/', '', $table);

            $query = 'DELETE FROM `'.bqSQL($table_lang).'` WHERE `'.bqSQL($id_table).'` NOT IN (SELECT `'.bqSQL($id_table).'` FROM `'.bqSQL($table).'`)';
            if ($db->Execute($query)) {
                if ($affected_rows = $db->Affected_Rows()) {
                    $logs[$query] = $affected_rows;
                }
            }

            $query = 'DELETE FROM `'.bqSQL($table_lang).'` WHERE `id_lang` NOT IN (SELECT `id_lang` FROM `'._DB_PREFIX_.'lang`)';
            if ($db->Execute($query)) {
                if ($affected_rows = $db->Affected_Rows()) {
                    $logs[$query] = $affected_rows;
                }
            }
        }

        // _shop table cleaning
        $tables = Db::getInstance()->executeS('SHOW TABLES LIKE "'.preg_replace('/([%_])/', '\\$1', _DB_PREFIX_).'%_\\_shop"');
        foreach ($tables as $table) {
            $table_shop = current($table);
            $table = str_replace('_shop', '', $table_shop);
            $id_table = 'id_'.preg_replace('/^'._DB_PREFIX_.'/', '', $table);

            if (in_array($table_shop, array(_DB_PREFIX_.'carrier_tax_rules_group_shop'))) {
                continue;
            }

            $query = 'DELETE FROM `'.bqSQL($table_shop).'` WHERE `'.bqSQL($id_table).'` NOT IN (SELECT `'.bqSQL($id_table).'` FROM `'.bqSQL($table).'`)';
            if ($db->Execute($query)) {
                if ($affected_rows = $db->Affected_Rows()) {
                    $logs[$query] = $affected_rows;
                }
            }

            $query = 'DELETE FROM `'.bqSQL($table_shop).'` WHERE `id_shop` NOT IN (SELECT `id_shop` FROM `'._DB_PREFIX_.'shop`)';
            if ($db->Execute($query)) {
                if ($affected_rows = $db->Affected_Rows()) {
                    $logs[$query] = $affected_rows;
                }
            }
        }

        // stock_available
        $query = 'DELETE FROM `'._DB_PREFIX_.'stock_available` WHERE `id_shop` NOT IN (SELECT `id_shop` FROM `'._DB_PREFIX_.'shop`) AND `id_shop_group` NOT IN (SELECT `id_shop_group` FROM `'._DB_PREFIX_.'shop_group`)';
        if ($db->Execute($query)) {
            if ($affected_rows = $db->Affected_Rows()) {
                $logs[$query] = $affected_rows;
            }
        }

        Category::regenerateEntireNtree();

        // @Todo: Remove attachment files, images...
        Image::clearTmpDir();
        self::clearAllCaches();

        return $logs;
    }

    protected static function bulle($array)
    {
        $sorted = false;
        $size = count($array);
        while (!$sorted) {
            $sorted = true;
            for ($i = 0; $i < $size - 1; ++$i) {
                for ($j = $i + 1; $j < $size; ++$j) {
                    if ($array[$i][2] == $array[$j][0]) {
                        $tmp = $array[$i];
                        $array[$i] = $array[$j];
                        $array[$j] = $tmp;
                        $sorted = false;
                    }
                }
            }
        }
        return $array;
    }

    protected static function clearAllCaches()
    {
        $index = file_exists(_PS_TMP_IMG_DIR_.'index.php') ? file_get_contents(_PS_TMP_IMG_DIR_.'index.php') : '';
        Tools::deleteDirectory(_PS_TMP_IMG_DIR_, false);
        file_put_contents(_PS_TMP_IMG_DIR_.'index.php', $index);
        Context::getContext()->smarty->clearAllCache();
    }

    public static function getCheckAndFixQueries()
    {
        $append = array();
        return array_merge(
            $append,
            array(
                // 0 => DELETE FROM __table__, 1 => WHERE __id__ NOT IN, 2 => NOT IN __table__, 3 => __id__ used in the "NOT IN" table, 4 => module_name
                array('access', 'id_profile', 'profile', 'id_profile'),
                array('accessory', 'id_product_1', 'product', 'id_product'),
                array('accessory', 'id_product_2', 'product', 'id_product'),
                array('address_format', 'id_country', 'country', 'id_country'),
                array('attribute', 'id_attribute_group', 'attribute_group', 'id_attribute_group'),
                array('carrier_group', 'id_carrier', 'carrier', 'id_carrier'),
                array('carrier_group', 'id_group', 'group', 'id_group'),
                array('carrier_zone', 'id_carrier', 'carrier', 'id_carrier'),
                array('carrier_zone', 'id_zone', 'zone', 'id_zone'),
                array('cart_cart_rule', 'id_cart', 'cart', 'id_cart'),
                array('cart_product', 'id_cart', 'cart', 'id_cart'),
                array('cart_rule_carrier', 'id_cart_rule', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_carrier', 'id_carrier', 'carrier', 'id_carrier'),
                array('cart_rule_combination', 'id_cart_rule_1', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_combination', 'id_cart_rule_2', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_country', 'id_cart_rule', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_country', 'id_country', 'country', 'id_country'),
                array('cart_rule_group', 'id_cart_rule', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_group', 'id_group', 'group', 'id_group'),
                array('cart_rule_product_rule_group', 'id_cart_rule', 'cart_rule', 'id_cart_rule'),
                array('cart_rule_product_rule', 'id_product_rule_group', 'cart_rule_product_rule_group', 'id_product_rule_group'),
                array('cart_rule_product_rule_value', 'id_product_rule', 'cart_rule_product_rule', 'id_product_rule'),
                array('category_group', 'id_category', 'category', 'id_category'),
                array('category_group', 'id_group', 'group', 'id_group'),
                array('category_product', 'id_category', 'category', 'id_category'),
                array('category_product', 'id_product', 'product', 'id_product'),
                array('cms', 'id_cms_category', 'cms_category', 'id_cms_category'),
                array('cms_block', 'id_cms_category', 'cms_category', 'id_cms_category', 'blockcms'),
                array('cms_block_page', 'id_cms', 'cms', 'id_cms', 'blockcms'),
                array('cms_block_page', 'id_cms_block', 'cms_block', 'id_cms_block', 'blockcms'),
                array('connections', 'id_shop_group', 'shop_group', 'id_shop_group'),
                array('connections', 'id_shop', 'shop', 'id_shop'),
                array('connections_page', 'id_connections', 'connections', 'id_connections'),
                array('connections_page', 'id_page', 'page', 'id_page'),
                array('connections_source', 'id_connections', 'connections', 'id_connections'),
                array('customer', 'id_shop_group', 'shop_group', 'id_shop_group'),
                array('customer', 'id_shop', 'shop', 'id_shop'),
                array('customer_group', 'id_group', 'group', 'id_group'),
                array('customer_group', 'id_customer', 'customer', 'id_customer'),
                array('customer_message', 'id_customer_thread', 'customer_thread', 'id_customer_thread'),
                array('customer_thread', 'id_shop', 'shop', 'id_shop'),
                array('customization', 'id_cart', 'cart', 'id_cart'),
                array('customization_field', 'id_product', 'product', 'id_product'),
                array('customized_data', 'id_customization', 'customization', 'id_customization'),
                array('delivery', 'id_shop', 'shop', 'id_shop'),
                array('delivery', 'id_shop_group', 'shop_group', 'id_shop_group'),
                array('delivery', 'id_carrier', 'carrier', 'id_carrier'),
                array('delivery', 'id_zone', 'zone', 'id_zone'),
                array('editorial', 'id_shop', 'shop', 'id_shop', 'editorial'),
                array('favorite_product', 'id_product', 'product', 'id_product', 'favoriteproducts'),
                array('favorite_product', 'id_customer', 'customer', 'id_customer', 'favoriteproducts'),
                array('favorite_product', 'id_shop', 'shop', 'id_shop', 'favoriteproducts'),
                array('feature_product', 'id_feature', 'feature', 'id_feature'),
                array('feature_product', 'id_product', 'product', 'id_product'),
                array('feature_value', 'id_feature', 'feature', 'id_feature'),
                array('group_reduction', 'id_group', 'group', 'id_group'),
                array('group_reduction', 'id_category', 'category', 'id_category'),
                array('homeslider', 'id_shop', 'shop', 'id_shop', 'homeslider'),
                array('homeslider', 'id_homeslider_slides', 'homeslider_slides', 'id_homeslider_slides', 'homeslider'),
                array('hook_module', 'id_hook', 'hook', 'id_hook'),
                array('hook_module', 'id_module', 'module', 'id_module'),
                array('hook_module_exceptions', 'id_hook', 'hook', 'id_hook'),
                array('hook_module_exceptions', 'id_module', 'module', 'id_module'),
                array('hook_module_exceptions', 'id_shop', 'shop', 'id_shop'),
                array('image', 'id_product', 'product', 'id_product'),
                array('message', 'id_cart', 'cart', 'id_cart'),
                array('message_readed', 'id_message', 'message', 'id_message'),
                array('message_readed', 'id_employee', 'employee', 'id_employee'),
                array('module_access', 'id_profile', 'profile', 'id_profile'),
                array('module_country', 'id_module', 'module', 'id_module'),
                array('module_country', 'id_country', 'country', 'id_country'),
                array('module_country', 'id_shop', 'shop', 'id_shop'),
                array('module_currency', 'id_module', 'module', 'id_module'),
                array('module_currency', 'id_currency', 'currency', 'id_currency'),
                array('module_currency', 'id_shop', 'shop', 'id_shop'),
                array('module_group', 'id_module', 'module', 'id_module'),
                array('module_group', 'id_group', 'group', 'id_group'),
                array('module_group', 'id_shop', 'shop', 'id_shop'),
                array('module_preference', 'id_employee', 'employee', 'id_employee'),
                array('orders', 'id_shop', 'shop', 'id_shop'),
                array('orders', 'id_shop_group', 'group_shop', 'id_shop_group'),
                array('order_carrier', 'id_order', 'orders', 'id_order'),
                array('order_cart_rule', 'id_order', 'orders', 'id_order'),
                array('order_detail', 'id_order', 'orders', 'id_order'),
                array('order_detail_tax', 'id_order_detail', 'order_detail', 'id_order_detail'),
                array('order_history', 'id_order', 'orders', 'id_order'),
                array('order_invoice', 'id_order', 'orders', 'id_order'),
                array('order_invoice_payment', 'id_order', 'orders', 'id_order'),
                array('order_invoice_tax', 'id_order_invoice', 'order_invoice', 'id_order_invoice'),
                array('order_return', 'id_order', 'orders', 'id_order'),
                array('order_return_detail', 'id_order_return', 'order_return', 'id_order_return'),
                array('order_slip', 'id_order', 'orders', 'id_order'),
                array('order_slip_detail', 'id_order_slip', 'order_slip', 'id_order_slip'),
                array('pack', 'id_product_pack', 'product', 'id_product'),
                array('pack', 'id_product_item', 'product', 'id_product'),
                array('page', 'id_page_type', 'page_type', 'id_page_type'),
                array('page_viewed', 'id_shop', 'shop', 'id_shop'),
                array('page_viewed', 'id_shop_group', 'shop_group', 'id_shop_group'),
                array('page_viewed', 'id_date_range', 'date_range', 'id_date_range'),
                array('product_attachment', 'id_attachment', 'attachment', 'id_attachment'),
                array('product_attachment', 'id_product', 'product', 'id_product'),
                array('product_attribute', 'id_product', 'product', 'id_product'),
                array('product_attribute_combination', 'id_product_attribute', 'product_attribute', 'id_product_attribute'),
                array('product_attribute_combination', 'id_attribute', 'attribute', 'id_attribute'),
                array('product_attribute_image', 'id_image', 'image', 'id_image'),
                array('product_attribute_image', 'id_product_attribute', 'product_attribute', 'id_product_attribute'),
                array('product_carrier', 'id_product', 'product', 'id_product'),
                array('product_carrier', 'id_shop', 'shop', 'id_shop'),
                array('product_carrier', 'id_carrier_reference', 'carrier', 'id_reference'),
                array('product_country_tax', 'id_product', 'product', 'id_product'),
                array('product_country_tax', 'id_country', 'country', 'id_country'),
                array('product_country_tax', 'id_tax', 'tax', 'id_tax'),
                array('product_download', 'id_product', 'product', 'id_product'),
                array('product_group_reduction_cache', 'id_product', 'product', 'id_product'),
                array('product_group_reduction_cache', 'id_group', 'group', 'id_group'),
                array('product_sale', 'id_product', 'product', 'id_product'),
                array('product_supplier', 'id_product', 'product', 'id_product'),
                array('product_supplier', 'id_supplier', 'supplier', 'id_supplier'),
                array('product_tag', 'id_product', 'product', 'id_product'),
                array('product_tag', 'id_tag', 'tag', 'id_tag'),
                array('range_price', 'id_carrier', 'carrier', 'id_carrier'),
                array('range_weight', 'id_carrier', 'carrier', 'id_carrier'),
                array('referrer_cache', 'id_referrer', 'referrer', 'id_referrer'),
                array('referrer_cache', 'id_connections_source', 'connections_source', 'id_connections_source'),
                array('search_index', 'id_product', 'product', 'id_product'),
                array('search_word', 'id_lang', 'lang', 'id_lang'),
                array('search_word', 'id_shop', 'shop', 'id_shop'),
                array('shop_url', 'id_shop', 'shop', 'id_shop'),
                array('specific_price_priority', 'id_product', 'product', 'id_product'),
                array('stock', 'id_warehouse', 'warehouse', 'id_warehouse'),
                array('stock', 'id_product', 'product', 'id_product'),
                array('stock_available', 'id_product', 'product', 'id_product'),
                array('stock_mvt', 'id_stock', 'stock', 'id_stock'),
                array('tab_module_preference', 'id_employee', 'employee', 'id_employee'),
                array('tab_module_preference', 'id_tab', 'tab', 'id_tab'),
                array('tax_rule', 'id_country', 'country', 'id_country'),
                array('warehouse_carrier', 'id_warehouse', 'warehouse', 'id_warehouse'),
                array('warehouse_carrier', 'id_carrier', 'carrier', 'id_carrier'),
                array('warehouse_product_location', 'id_product', 'product', 'id_product'),
                array('warehouse_product_location', 'id_warehouse', 'warehouse', 'id_warehouse'),
                array('specific_price', 'id_product', 'product', 'id_product'),
                array('specific_price', 'id_customer', 'customer', 'id_customer'),
                array('specific_price', 'id_group', 'group', 'id_group'),
                array('htl_features_block_data_lang', 'id_features_block', 'htl_features_block_data', 'id_features_block'),
                array('cart_rule', 'id_customer', 'customer', 'id_customer'),
                array('htl_testimonials_block_data_lang', 'id_testimonial_block', 'htl_testimonials_block_data', 'id_testimonial_block'),
                array('htl_room_type_global_demand_advance_option', 'id_global_demand', 'htl_room_type_global_demand', 'id_global_demand'),
                array('htl_room_type_global_demand_advance_option_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_room_type_global_demand_advance_option_lang', 'id_option', 'htl_room_type_global_demand_advance_option', 'id_option'),
                array('htl_room_type_global_demand_lang', 'id_global_demand', 'htl_room_type_global_demand', 'id_global_demand'),
                array('htl_branch_info_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_branch_info_lang', 'id', 'htl_branch_info', 'id'),
                array('htl_features_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_features_lang', 'id', 'htl_features', 'id'),
                array('htl_room_type_demand', 'id_global_demand', 'htl_room_type_global_demand', 'id_global_demand'),
                array('htl_room_type_demand', 'id_product', 'product', 'id_product'),
                array('htl_room_type_demand_price', 'id_product', 'product', 'id_product'),
                array('htl_room_type_feature_pricing', 'id_product', 'product', 'id_product'),
                array('htl_room_type_feature_pricing_restriction', 'id_feature_price', 'htl_room_type_feature_pricing', 'id_feature_price'),
                array('htl_room_type_feature_pricing_group', 'id_group', 'group', 'id_group'),
                array('htl_booking_demands_tax', 'id_tax', 'tax', 'id_tax'),
                // htl_booking_detail -> product is left out on purpose: deleting a room type must never erase booking history
                array('htl_booking_detail', 'id_order', 'orders', 'id_order'),
                array('htl_booking_demands', 'id_htl_booking', 'htl_booking_detail', 'id_htl_booking'),
                array('htl_cart_booking_data', 'id_order', 'orders', 'id_order'),
                array('htl_cart_booking_data', 'id_customer', 'customer', 'id_customer'),
                array('htl_branch_features', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_image', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_branch_info', 'id_category', 'category', 'id_category'),
                array('htl_room_information', 'id_product', 'product', 'id_product'),
                array('htl_room_information', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_room_type', 'id_product', 'product', 'id_product'),
                array('htl_room_type', 'id_hotel', 'htl_branch_info', 'id'),
                array('profile_lang', 'id_profile', 'profile', 'id_profile'),
                array('profile_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_access', 'id_profile', 'profile', 'id_profile'),
                array('htl_access', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_room_disable_dates', 'id_room_type', 'htl_room_type', 'id_product'),
                array('htl_room_type_feature_pricing_lang', 'id_feature_price', 'htl_room_type_feature_pricing', 'id_feature_price'),
                array('htl_room_type_feature_pricing_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_order_restrict_date', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_branch_refund_rules', 'id_hotel', 'htl_branch_info', 'id'),
                array('htl_order_refund_rules_lang', 'id_refund_rule', 'htl_branch_refund_rules', 'id_refund_rule'),
                array('htl_advance_payment', 'id_product', 'product', 'id_product'),
                array('htl_order_refund_rules_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_room_type_service_product', 'id_product', 'product', 'id_product'),
                array('htl_room_type_service_product', 'id_element', 'product', 'id_product'),
                array('htl_room_type_service_product_price', 'id_product', 'product', 'id_product'),
                array('htl_room_type_service_product_price', 'id_element', 'product', 'id_product'),
                array('htl_bed_type_lang', 'id_lang', 'lang', 'id_lang'),
                array('htl_room_type_bed_type', 'id_product', 'product', 'id_product'),
                array('htl_room_type_bed_type', 'id_bed_type', 'htl_bed_type', 'id_bed_type'),

            )
        );
    }

}
