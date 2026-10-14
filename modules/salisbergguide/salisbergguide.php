<?php
/**
 * Salisberg Guide: in-app help for the back office.
 *
 * Adds a "Guides" menu with a Staff Guide (every profile that is granted it)
 * and an Admin Guide (SuperAdmin only), and creates a restricted "Hotel Staff"
 * profile for front desk employees.
 **/

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
        // add and edit, not only view: see grantCartEditing()
        'AdminCarts' => array(1, 1, 1, 0),
        'AdminCustomerThreads' => array(1, 1, 1, 0),
        'AdminOrderRefundRequests' => array(1, 0, 1, 0),
        // look, do not change
        'AdminProducts' => array(1, 0, 0, 0),
        'AdminSalisbergStaffGuide' => array(1, 0, 0, 0),
        'AdminSalisbergWhatsNew' => array(1, 0, 0, 0),
    );

    const MANAGER_PROFILE = 'Hotel Manager';

    /**
     * Pages the person running the hotel works in. Everything about the hotel
     * itself; nothing about how the system is installed or built (modules,
     * localization, preferences, advanced parameters, profiles, permissions),
     * which stays with the SuperAdmin accounts of the developer team.
     */
    public static $managerAccess = array(
        'AdminDashboard' => array(1, 0, 0, 0),
        'AdminStats' => array(1, 0, 0, 0),
        // menu parents
        'AdminCatalog' => array(1, 0, 0, 0),
        'AdminParentOrders' => array(1, 0, 0, 0),
        'AdminParentCustomer' => array(1, 0, 0, 0),
        'AdminPriceRule' => array(1, 0, 0, 0),
        'AdminHotelReservationSystemManagement' => array(1, 0, 0, 0),
        'AdminParentPreferences' => array(1, 0, 0, 0),
        'AdminAdmin' => array(1, 0, 0, 0),
        'AdminSalisbergGuide' => array(1, 0, 0, 0),
        // rooms, services and prices
        'AdminProducts' => array(1, 1, 1, 1),
        'AdminNormalProducts' => array(1, 1, 1, 1),
        'AdminCategories' => array(1, 1, 1, 1),
        'AdminFeatures' => array(1, 1, 1, 1),
        'AdminHotelBedTypes' => array(1, 1, 1, 1),
        'AdminCartRules' => array(1, 1, 1, 1),
        'AdminSpecificPriceRule' => array(1, 1, 1, 1),
        'AdminHotelFeaturePricesSettings' => array(1, 1, 1, 1),
        'AdminRoomTypeGlobalDemand' => array(1, 1, 1, 1),
        // bookings and money
        'AdminHotelRoomsBooking' => array(1, 1, 1, 1),
        'AdminOrders' => array(1, 1, 1, 1),
        'AdminSalisbergReports' => array(1, 0, 0, 0),
        'AdminInvoices' => array(1, 1, 1, 0),
        'AdminSlip' => array(1, 1, 1, 0),
        'AdminOrderMessage' => array(1, 1, 1, 1),
        'AdminBookingDocument' => array(1, 1, 1, 1),
        'AdminOrderRefundRules' => array(1, 1, 1, 1),
        'AdminOrderRefundRequests' => array(1, 1, 1, 0),
        // guests
        'AdminCustomers' => array(1, 1, 1, 1),
        'AdminAddresses' => array(1, 1, 1, 1),
        // add and edit, not only view: see grantCartEditing()
        'AdminCarts' => array(1, 1, 1, 0),
        'AdminCustomerThreads' => array(1, 1, 1, 1),
        'AdminContacts' => array(1, 1, 1, 1),
        // the hotel and what the website says about it
        'AdminAddHotel' => array(1, 1, 1, 0),
        'AdminHotelFeatures' => array(1, 1, 1, 1),
        'AdminHotelConfigurationSetting' => array(1, 0, 1, 0),
        'AdminHotelGeneralSettings' => array(1, 0, 1, 0),
        'AdminAboutHotelBlockSetting' => array(1, 1, 1, 1),
        'AdminFeaturesModuleSetting' => array(1, 1, 1, 1),
        'AdminHotelRoomModuleSetting' => array(1, 1, 1, 1),
        'AdminTestimonialsModuleSetting' => array(1, 1, 1, 1),
        'AdminFooterPaymentBlockSetting' => array(1, 1, 1, 1),
        'AdminCmsContent' => array(1, 1, 1, 1),
        'AdminCms' => array(1, 1, 1, 1),
        'AdminCmsCategories' => array(1, 1, 1, 1),
        // staff accounts (the platform itself stops anyone but a SuperAdmin
        // from creating, editing or deleting a SuperAdmin)
        'AdminEmployees' => array(1, 1, 1, 1),
        // both guides
        'AdminSalisbergStaffGuide' => array(1, 0, 0, 0),
        'AdminSalisbergAdminGuide' => array(1, 0, 0, 0),
        'AdminSalisbergWhatsNew' => array(1, 0, 0, 0),
    );

    /** Menu sections a SuperAdmin can show or hide from the Admin Guide page: class name => name */
    public static $menuToggles = array(
        'AdminQloappsChannelManagerConnector' => 'Channel Manager',
        'AdminParentModules' => 'Modules and Services',
    );

    /**
     * Stock menu entries shown under a clearer name: class name => array(stock
     * name, new name). An entry is renamed only while it still carries the
     * stock name, so a name changed by hand in the back office is kept.
     */
    protected $menuLabels = array(
        'AdminParentOrders' => array('Orders', 'Bookings'),
        'AdminOrders' => array('Orders', 'Bookings'),
        'AdminHotelReservationSystemManagement' => array('Hotel Reservation System', 'Manage Hotel'),
        'AdminAddHotel' => array('Manage Hotel', 'Hotel Details'),
        'AdminCatalog' => array('Catalog', 'Rooms & Services'),
        'AdminCustomerThreads' => array('Customer Service', 'Inbox'),
        'AdminHotelRoomsBooking' => array('Book Now', 'Add new booking'),
    );

    /** The vendor's own store pages, kept out of the menu: class names */
    public static $vendorMenus = array('AdminModulesCatalog');

    /**
     * Shop features a hotel does not use, taken out of the product: their menu
     * entries are deleted, with every profile's rights to them. Class names.
     */
    public static $removedMenus = array(
        'AdminWarehouses',
        'AdminStockManagement',
        'AdminStockMvt',
        'AdminStockInstantState',
        'AdminStockCover',
        'AdminStockConfiguration',
        'AdminStock',
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
     * Creates whatever is missing: the menu entries and the Hotel Staff and
     * Hotel Manager profiles, and applies the menu names in $menuLabels. Safe
     * to call on every deploy.
     */
    public function ensureSetup()
    {
        // The What's New right is given to existing profiles once, when the
        // page first appears, so that taking it away later is not undone
        $whatsNewIsNew = !Tab::getIdFromClassName('AdminSalisbergWhatsNew');

        return $this->installTab('AdminSalisbergGuide', 'Guides', 0)
            && $this->installTab('AdminSalisbergStaffGuide', 'Staff Guide', (int) Tab::getIdFromClassName('AdminSalisbergGuide'))
            && $this->installTab('AdminSalisbergAdminGuide', 'Admin Guide', (int) Tab::getIdFromClassName('AdminSalisbergGuide'))
            && $this->installTab('AdminSalisbergWhatsNew', 'What\'s New', (int) Tab::getIdFromClassName('AdminSalisbergGuide'))
            && $this->installProfile(self::STAFF_PROFILE, self::$staffAccess)
            && $this->installProfile(self::MANAGER_PROFILE, self::$managerAccess)
            && (!$whatsNewIsNew || $this->grantWhatsNew())
            && $this->grantHotelAccess()
            && $this->grantCartEditing()
            && $this->renameMenuEntries()
            && $this->hideVendorMenus()
            && $this->removeMenus()
            && $this->registerHook('header')
            && $this->registerHook('footer')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionAdminLoginControllerSetMedia');
    }

    /**
     * QloApps has a second, separate permission layer on top of the normal
     * Administration > Permissions grid: a per-hotel "Hotel Permissions"
     * table on the same page (class AdminAccessController, table
     * htl_access), defaulting to no access for every profile it creates
     * except SuperAdmin. Without this, Hotel Staff and Hotel Manager can
     * still search, add to cart and create a booking (none of that checks
     * htl_access), but are refused when the system tries to show them
     * anything hotel-scoped afterward: the booking they just made ("You do
     * not have permission to view this."), and every hotel-scoped dashboard
     * and stats widget (revenue, occupancy, check-ins/outs), which simply
     * render empty rather than erroring. Found and fixed 2026-10-12 after a
     * live report of exactly this. Run on every deploy, not guarded, because
     * access being off for either role on the hotel this project runs is
     * never a deliberate choice (there is one hotel, and both roles work
     * inside it by design) — unlike $staffAccess/$managerAccess, which are
     * deliberately never reset once an admin has adjusted them by hand.
     */
    protected function grantHotelAccess()
    {
        $idStaff = (int) $this->getProfileId(self::STAFF_PROFILE);
        $idManager = (int) $this->getProfileId(self::MANAGER_PROFILE);
        $hotelIds = array_column(
            Db::getInstance()->executeS('SELECT `id` FROM `'._DB_PREFIX_.'htl_branch_info` WHERE `active` = 1'),
            'id'
        );

        $changed = 0;
        foreach (array($idStaff, $idManager) as $idProfile) {
            if (!$idProfile) {
                continue;
            }
            foreach ($hotelIds as $idHotel) {
                $current = Db::getInstance()->getValue(
                    'SELECT `access` FROM `'._DB_PREFIX_.'htl_access`
                    WHERE `id_profile` = '.(int) $idProfile.' AND `id_hotel` = '.(int) $idHotel
                );
                if ($current === false) {
                    Db::getInstance()->insert('htl_access', array(
                        'id_profile' => (int) $idProfile,
                        'id_hotel' => (int) $idHotel,
                        'access' => 1,
                    ));
                    ++$changed;
                } elseif (!$current) {
                    Db::getInstance()->update(
                        'htl_access',
                        array('access' => 1),
                        'id_profile = '.(int) $idProfile.' AND id_hotel = '.(int) $idHotel
                    );
                    ++$changed;
                }
            }
        }

        if ($changed) {
            PrestaShopLogger::addLog("salisbergguide: granted hotel access to $changed profile/hotel row(s) for Hotel Staff/Hotel Manager", 1);
        }

        return true;
    }

    /**
     * Book Now ends on Bookings > Add new, and that form does all its work
     * on the cart (choosing the guest, the address, vouchers, extra services)
     * by calling the Booking Carts page behind the scenes. Each of those
     * calls is refused without a word unless the profile may add and edit
     * Booking Carts; with view only the guest is never attached, creating
     * the booking fails, and the rooms stay in the cart. Both profiles were
     * first created with view only, and an existing profile is never reset,
     * so this raises those two rights where they are still off. It never
     * lowers anything.
     */
    protected function grantCartEditing()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminCarts');
        if (!$idTab) {
            return true;
        }

        foreach (array(self::STAFF_PROFILE, self::MANAGER_PROFILE) as $name) {
            $idProfile = (int) $this->getProfileId($name);
            if (!$idProfile) {
                continue;
            }
            $where = '`id_profile` = '.$idProfile.' AND `id_tab` = '.$idTab;
            $row = Db::getInstance()->getRow('SELECT `add`, `edit` FROM `'._DB_PREFIX_.'access` WHERE '.$where);
            if (!$row) {
                Db::getInstance()->insert('access', array(
                    'id_profile' => $idProfile,
                    'id_tab' => $idTab,
                    'view' => 1,
                    'add' => 1,
                    'edit' => 1,
                    'delete' => 0,
                ));
            } elseif (!$row['add'] || !$row['edit']) {
                Db::getInstance()->update('access', array('view' => 1, 'add' => 1, 'edit' => 1), $where);
            } else {
                continue;
            }
            PrestaShopLogger::addLog('salisbergguide: '.$name.' may now add and edit Booking Carts, needed to finish a booking', 1);
        }

        return true;
    }

    protected function renameMenuEntries()
    {
        foreach ($this->menuLabels as $className => $names) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                Db::getInstance()->update(
                    'tab_lang',
                    array('name' => pSQL($names[1])),
                    'id_tab = '.$idTab.' AND name = \''.pSQL($names[0]).'\''
                );
            }
        }

        return true;
    }

    /** Deletes the menu entries in $removedMenus that still exist. Safe to repeat. */
    protected function removeMenus()
    {
        foreach (self::$removedMenus as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
                PrestaShopLogger::addLog('salisbergguide: removed the menu entry '.$className, 1);
            }
        }

        return true;
    }

    protected function hideVendorMenus()
    {
        foreach (self::$vendorMenus as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                Db::getInstance()->update('tab', array('active' => 0), 'id_tab = '.$idTab);
            }
        }

        return true;
    }

    /**
     * Small interface helpers. Add new ones here rather than in a new module.
     *  - everywhere: the show/hide (eye) button on password fields
     *  - back office only: the fold/unfold arrows in the side menu (side-menu.js)
     *  - everywhere: the light/dark switch. Its colours live in
     *    admin/themes/default/css/overrides.css (back office) and
     *    themes/hotel-reservation-theme/css/salisberg.css (website)
     *
     * @param bool $backOffice true when called for a back office page
     */
    protected function addInterfaceAssets($backOffice = false)
    {
        $controller = $this->context->controller;
        if (!$controller) {
            return;
        }
        $controller->addCSS($this->_path.'views/css/password-toggle.css', 'all');
        $controller->addJS($this->_path.'views/js/password-toggle.js');
        if (!$backOffice) {
            // tells theme-toggle.js it is on the website (its own saved choice, light by default)
            Media::addJsDef(array('sbThemeScope' => 'site'));
        } else {
            $controller->addJS($this->_path.'views/js/side-menu.js');
            $controller->addJS($this->_path.'views/js/page-head.js');
        }
        $controller->addJS($this->_path.'views/js/theme-toggle.js');
    }

    public function hookHeader()
    {
        $this->addInterfaceAssets();
    }

    /**
     * Website footer: a "Location and Contact" column, next to Explore,
     * Follow us on and Payment accepted. Uses the same address, phone and
     * email already shown on the Contact page (Shop::getAddress(),
     * PS_SHOP_PHONE, PS_SHOP_EMAIL), so there is one place to update them
     * (Preferences > General / Shop Parameters) and both pages agree.
     */
    public function hookFooter()
    {
        $address = (new Shop())->getAddress();
        $formattedAddress = '';
        if ($address instanceof Address) {
            $formattedAddress = trim(AddressFormat::generateAddress($address, array(), ', ', ' '));
        }

        $this->smarty->assign(array(
            'sbFooterAddress' => $formattedAddress,
            'sbFooterPhone' => Configuration::get('PS_SHOP_PHONE'),
            'sbFooterEmail' => Configuration::get('PS_SHOP_EMAIL'),
        ));

        return $this->display(__FILE__, 'footer_location.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {
        $this->addInterfaceAssets(true);
    }

    public function hookActionAdminLoginControllerSetMedia()
    {
        $this->addInterfaceAssets(true);
    }

    public function uninstall()
    {
        foreach (array('AdminSalisbergWhatsNew', 'AdminSalisbergAdminGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergGuide') as $className) {
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
     * Creates a profile once and grants it the pages in $access. An existing
     * profile of that name is left untouched, so permissions an administrator
     * has adjusted by hand are never reset.
     *
     * @param string $name   profile name, as shown in Administration > Profiles
     * @param array  $access class name => array(view, add, edit, delete)
     */
    public function installProfile($name, array $access)
    {
        $exists = $this->getProfileId($name);
        if ($exists) {
            // Still make sure the profile can open its guides and their menu
            $guides = array();
            foreach (array('AdminSalisbergGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergAdminGuide') as $className) {
                if (isset($access[$className])) {
                    $guides[$className] = $access[$className];
                }
            }

            return $this->grant($exists, $guides);
        }

        $profile = new Profile();
        foreach (Language::getLanguages(false) as $lang) {
            $profile->name[(int) $lang['id_lang']] = $name;
        }
        if (!$profile->add()) {
            return false;
        }

        // Every page starts closed for the new profile
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`)
            SELECT '.(int) $profile->id.', `id_tab`, 0, 0, 0, 0 FROM `'._DB_PREFIX_.'tab`'
        );

        return $this->grant((int) $profile->id, $access);
    }

    protected function grantWhatsNew()
    {
        $ok = true;
        foreach (array(self::STAFF_PROFILE => self::$staffAccess, self::MANAGER_PROFILE => self::$managerAccess) as $name => $access) {
            $idProfile = $this->getProfileId($name);
            if ($idProfile) {
                $ok = $this->grant($idProfile, array('AdminSalisbergWhatsNew' => $access['AdminSalisbergWhatsNew'])) && $ok;
            }
        }

        return $ok;
    }

    /**
     * Whether the signed-in employee holds the View right for a back office page.
     */
    public function employeeCanView($className)
    {
        $employee = $this->context->employee;
        if (!$employee || !$employee->id) {
            return false;
        }
        $access = Profile::getProfileAccess($employee->id_profile, (int) Tab::getIdFromClassName($className));

        return !empty($access['view']);
    }

    protected function getProfileId($name)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang`
            WHERE `name` = \''.pSQL($name).'\' AND `id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT')
        );
    }

    /**
     * Who may read the Admin Guide: SuperAdmins and Hotel Managers.
     */
    public function canReadAdminGuide()
    {
        $employee = $this->context->employee;
        if (!$employee || !$employee->id) {
            return false;
        }

        return $employee->isSuperAdmin() || (int) $employee->id_profile === $this->getProfileId(self::MANAGER_PROFILE);
    }

    /**
     * Shows or hides one of the menu sections in $menuToggles for everyone.
     * A hidden section is only removed from the menu; its pages still open
     * for those with permission, for example from the links in the guides.
     */
    public function setMenuVisible($className, $visible)
    {
        if (!isset(self::$menuToggles[$className])) {
            return false;
        }
        $idTab = (int) Tab::getIdFromClassName($className);
        if (!$idTab) {
            return false;
        }

        return Db::getInstance()->update('tab', array('active' => (int) (bool) $visible), 'id_tab = '.$idTab);
    }

    /**
     * @return array class name => array('name' => ..., 'visible' => bool), for the sections that exist
     */
    public function getMenuToggles()
    {
        $toggles = array();
        foreach (self::$menuToggles as $className => $name) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $toggles[$className] = array(
                    'name' => $name,
                    'visible' => (bool) Db::getInstance()->getValue('SELECT `active` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idTab),
                );
            }
        }

        return $toggles;
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
            'AdminDashboard', 'AdminSalisbergToday', 'AdminSalisbergHousekeeping', 'AdminSalisbergCharges', 'AdminHotelRoomsBooking', 'AdminOrders', 'AdminInvoices', 'AdminCustomers', 'AdminCarts',
            'AdminCustomerThreads', 'AdminOrderRefundRequests', 'AdminProducts', 'AdminNormalProducts', 'AdminAddHotel',
            'AdminHotelFeatures', 'AdminHotelFeaturePricesSettings', 'AdminHotelGeneralSettings', 'AdminOrderRefundRules',
            'AdminCartRules', 'AdminModules', 'AdminPayment', 'AdminCurrencies', 'AdminTaxes', 'AdminTaxRulesGroup',
            'AdminPreferences', 'AdminThemes', 'AdminMeta', 'AdminCmsContent', 'AdminMaintenance', 'AdminEmails',
            'AdminBackup', 'AdminInformation', 'AdminEmployees', 'AdminProfiles', 'AdminAccess', 'AdminContacts', 'AdminStats',
            'AdminSalisbergGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergAdminGuide', 'AdminSalisbergWhatsNew',
            'AdminSalisbergReports',
        );
        $links = array();
        foreach ($pages as $page) {
            $links[$page] = $this->context->link->getAdminLink($page);
        }
        $employee = $this->context->employee;
        $this->context->smarty->assign(array(
            'sb_links' => $links,
            'sb_is_admin' => $employee && $employee->isSuperAdmin(),
            'sb_can_read_admin_guide' => $this->canReadAdminGuide(),
            'sb_can_read_whats_new' => $this->employeeCanView('AdminSalisbergWhatsNew'),
            'sb_menu_toggles' => $this->getMenuToggles(),
            'sb_shop_name' => Configuration::get('PS_SHOP_NAME'),
            'sb_shop_email' => Configuration::get('PS_SHOP_EMAIL'),
            'sb_css' => $this->_path.'views/css/guide.css',
        ));

        return $this->context->smarty->fetch($this->local_path.'views/templates/admin/'.$template);
    }
}
