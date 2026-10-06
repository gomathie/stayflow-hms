<?php
/**
 * Salisberg Guide: in-app help for the back office.
 *
 * Adds a "Guides" menu with a Staff Guide (every profile that is granted it)
 * and an Admin Guide (SuperAdmin only), and creates a restricted "Hotel Staff"
 * profile for front desk employees.
 *
 * @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Salisbergguide extends Module
{
    const STAFF_PROFILE = 'Hotel Staff';

    /** Back office pages a front desk employee works in: class name => array(view, add, edit, delete) */
    public static $staffAccess = array(
        'AdminDashboard' => array(1, 0, 0, 0),
        // menu parents (needed for the children to appear)
        'AdminParentOrders' => array(1, 0, 0, 0),
        'AdminParentCustomer' => array(1, 0, 0, 0),
        'AdminCatalog' => array(1, 0, 0, 0),
        'AdminHotelReservationSystemManagement' => array(1, 0, 0, 0),
        'AdminSalisbergGuide' => array(1, 0, 0, 0),
        // daily work
        'AdminHotelRoomsBooking' => array(1, 1, 1, 0),
        'AdminOrders' => array(1, 1, 1, 0),
        'AdminInvoices' => array(1, 0, 0, 0),
        'AdminCustomers' => array(1, 1, 1, 0),
        'AdminAddresses' => array(1, 1, 1, 0),
        'AdminCarts' => array(1, 0, 0, 0),
        'AdminCustomerThreads' => array(1, 1, 1, 0),
        'AdminOrderRefundRequests' => array(1, 0, 1, 0),
        // look, do not change
        'AdminProducts' => array(1, 0, 0, 0),
        'AdminSalisbergStaffGuide' => array(1, 0, 0, 0),
    );

    public function __construct()
    {
        $this->name = 'salisbergguide';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salisberg Hotels';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Salisberg Guide');
        $this->description = $this->l('How-to guides inside the back office: one for hotel staff and one for administrators.');
    }

    public function install()
    {
        return parent::install() && $this->ensureSetup();
    }

    /**
     * Creates whatever is missing: the three menu entries and the Hotel Staff
     * profile. Safe to call on every deploy.
     */
    public function ensureSetup()
    {
        return $this->installTab('AdminSalisbergGuide', 'Guides', 0)
            && $this->installTab('AdminSalisbergStaffGuide', 'Staff Guide', (int) Tab::getIdFromClassName('AdminSalisbergGuide'))
            && $this->installTab('AdminSalisbergAdminGuide', 'Admin Guide', (int) Tab::getIdFromClassName('AdminSalisbergGuide'))
            && $this->installStaffProfile()
            && $this->registerHook('header')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionAdminLoginControllerSetMedia');
    }

    /**
     * Small interface helpers shared by the website and the back office.
     * Currently: the show/hide (eye) button on password fields.
     */
    protected function addInterfaceAssets()
    {
        $controller = $this->context->controller;
        if (!$controller) {
            return;
        }
        $controller->addCSS($this->_path.'views/css/password-toggle.css', 'all');
        $controller->addJS($this->_path.'views/js/password-toggle.js');
    }

    public function hookHeader()
    {
        $this->addInterfaceAssets();
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->addInterfaceAssets();
    }

    public function hookActionAdminLoginControllerSetMedia()
    {
        $this->addInterfaceAssets();
    }

    public function uninstall()
    {
        foreach (array('AdminSalisbergAdminGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergGuide') as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }
        // The Hotel Staff profile is left in place: employees may be assigned to it.

        return parent::uninstall();
    }

    protected function installTab($className, $name, $idParent)
    {
        if (Tab::getIdFromClassName($className)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->module = $this->name;
        $tab->id_parent = (int) $idParent;
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = $name;
        }

        // add() also reports failure when only its permission bookkeeping fails
        // (no employee in context), so judge by whether the tab now exists
        $tab->add();

        return (bool) Tab::getIdFromClassName($className);
    }

    /**
     * Creates the Hotel Staff profile once and grants it the pages in $staffAccess.
     * An existing profile of that name is left untouched, so permissions an
     * administrator has adjusted by hand are never reset.
     */
    public function installStaffProfile()
    {
        $idLang = (int) Configuration::get('PS_LANG_DEFAULT');
        $exists = (int) Db::getInstance()->getValue(
            'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang`
            WHERE `name` = \''.pSQL(self::STAFF_PROFILE).'\' AND `id_lang` = '.$idLang
        );
        if ($exists) {
            // Still make sure the profile can open the staff guide and its menu
            return $this->grant($exists, array(
                'AdminSalisbergGuide' => self::$staffAccess['AdminSalisbergGuide'],
                'AdminSalisbergStaffGuide' => self::$staffAccess['AdminSalisbergStaffGuide'],
            ));
        }

        $profile = new Profile();
        foreach (Language::getLanguages(false) as $lang) {
            $profile->name[(int) $lang['id_lang']] = self::STAFF_PROFILE;
        }
        if (!$profile->add()) {
            return false;
        }

        // Every page starts closed for the new profile
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`)
            SELECT '.(int) $profile->id.', `id_tab`, 0, 0, 0, 0 FROM `'._DB_PREFIX_.'tab`'
        );

        return $this->grant((int) $profile->id, self::$staffAccess);
    }

    protected function grant($idProfile, array $access)
    {
        $ok = true;
        foreach ($access as $className => $rights) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if (!$idTab) {
                continue;
            }
            $ok &= Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`)
                VALUES ('.(int) $idProfile.', '.$idTab.', '.(int) $rights[0].', '.(int) $rights[1].', '.(int) $rights[2].', '.(int) $rights[3].')
                ON DUPLICATE KEY UPDATE `view` = VALUES(`view`), `add` = VALUES(`add`), `edit` = VALUES(`edit`), `delete` = VALUES(`delete`)'
            );
        }

        return (bool) $ok;
    }

    /**
     * Renders one guide template with links to the pages it talks about.
     */
    public function renderGuide($template)
    {
        $pages = array(
            'AdminDashboard', 'AdminHotelRoomsBooking', 'AdminOrders', 'AdminInvoices', 'AdminCustomers', 'AdminCarts',
            'AdminCustomerThreads', 'AdminOrderRefundRequests', 'AdminProducts', 'AdminNormalProducts', 'AdminAddHotel',
            'AdminHotelFeatures', 'AdminHotelFeaturePricesSettings', 'AdminHotelGeneralSettings', 'AdminOrderRefundRules',
            'AdminCartRules', 'AdminModules', 'AdminPayment', 'AdminCurrencies', 'AdminTaxes', 'AdminTaxRulesGroup',
            'AdminPreferences', 'AdminThemes', 'AdminMeta', 'AdminCmsContent', 'AdminMaintenance', 'AdminEmails',
            'AdminBackup', 'AdminEmployees', 'AdminProfiles', 'AdminAccess', 'AdminContacts', 'AdminStats',
            'AdminSalisbergGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergAdminGuide',
        );
        $links = array();
        foreach ($pages as $page) {
            $links[$page] = $this->context->link->getAdminLink($page);
        }
        $employee = $this->context->employee;
        $this->context->smarty->assign(array(
            'sb_links' => $links,
            'sb_is_admin' => $employee && $employee->isSuperAdmin(),
            'sb_shop_name' => Configuration::get('PS_SHOP_NAME'),
            'sb_shop_email' => Configuration::get('PS_SHOP_EMAIL'),
            'sb_css' => $this->_path.'views/css/guide.css',
        ));

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/'.$template);
    }
}
