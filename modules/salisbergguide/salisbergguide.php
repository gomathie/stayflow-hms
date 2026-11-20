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
        'AdminSalisbergGuide' => array(1, 0, 0, 0),
        // daily work
        'AdminSalisbergRoomBoard' => array(1, 0, 1, 0),
        'AdminSalisbergWalkIn' => array(1, 0, 1, 0),
        'AdminHotelRoomsBooking' => array(1, 1, 1, 0),
        'AdminOrders' => array(1, 1, 1, 0),
        'AdminInvoices' => array(1, 0, 0, 0),
        'AdminCustomers' => array(1, 1, 1, 0),
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
        'AdminSalisbergRoomBoard' => array(1, 0, 1, 0),
        'AdminSalisbergWalkIn' => array(1, 0, 1, 0),
        'AdminHotelRoomsBooking' => array(1, 1, 1, 1),
        'AdminOrders' => array(1, 1, 1, 1),
        'AdminSalisbergReports' => array(1, 0, 0, 0),
        'AdminInvoices' => array(1, 1, 1, 0),
        'AdminOrderMessage' => array(1, 1, 1, 1),
        'AdminBookingDocument' => array(1, 1, 1, 1),
        'AdminOrderRefundRules' => array(1, 1, 1, 1),
        'AdminOrderRefundRequests' => array(1, 1, 1, 0),
        // guests
        'AdminCustomers' => array(1, 1, 1, 1),
        'AdminAddresses' => array(1, 1, 1, 1),
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
    public static $menuToggles = array('AdminParentModules' => 'Modules and Services');

    /**
     * Stock menu entries shown under a clearer name: class name => array(the
     * name or list of names it may still carry, new name). An entry is renamed
     * only while it carries one of those names, so a name changed by hand in
     * the back office is kept.
     */
    protected $menuLabels = array(
        'AdminParentOrders' => array('Orders', 'Bookings'),
        'AdminOrders' => array(array('Orders', 'Reservations'), 'Bookings'),
        'AdminOrderMessage' => array('Order messages', 'Booking messages'),
        'AdminOrderPreferences' => array('Orders', 'Bookings'),
        'AdminOrderRefundRules' => array('Manage Order Refund Rules', 'Refund rules'),
        'AdminOrderRefundRequests' => array('Manage Order Refund Requests', 'Refund requests'),
        'AdminHotelReservationSystemManagement' => array('Hotel Reservation System', 'Manage Hotel'),
        'AdminAddHotel' => array('Manage Hotel', 'Hotel Details'),
        'AdminCustomerThreads' => array('Customer Service', 'Inbox'),
    );

    /**
     * Pages kept out of the menu. The controllers stay: a booking is still an
     * order and a cart, and the installer still creates these tabs.
     * active = 0 only. A later deploy hides them again.
     */
    public static $hiddenMenus = array(
        'AdminModulesCatalog',
        'AdminQloappsChannelManagerConnector',
        'AdminZones',
        'AdminSlip',
        // shop screens a single hotel does not open
        'AdminGroups',
        'AdminGenders',
        'AdminCarts',
        'AdminSpecificPriceRule',
        'AdminStats',
        'AdminThemes',
        'AdminSearchConf',
        'AdminGeolocation',
        'AdminStates',
    );

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
            && $this->renameMenuEntries()
            && $this->orderMenus()
            && $this->deskOnlyStaffMenu()
            && $this->moveToAdministration()
            && $this->hideUnusedMenus()
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

    protected function renameMenuEntries()
    {
        foreach ($this->menuLabels as $className => $names) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $old = array_map('pSQL', (array) $names[0]);
                Db::getInstance()->update(
                    'tab_lang',
                    array('name' => pSQL($names[1])),
                    'id_tab = '.$idTab.' AND name IN (\''.implode('\', \'', $old).'\')'
                );
            }
        }

        return true;
    }

    /**
     * Side menu order: Bookings comes straight after Dashboard, ahead of Rooms &
     * Services (the installer lists them the other way round). Done once, tracked
     * by SBGUIDE_MENU_ORDER, so an order set later in Administration > Menus is
     * not undone by a deploy. Fresh installs get this order from the installer
     * data (install/data/xml/tab.xml), so for them the step finds nothing to move.
     */
    protected function orderMenus()
    {
        if (Configuration::get('SBGUIDE_MENU_ORDER')) {
            return true;
        }
        $idCatalog = (int) Tab::getIdFromClassName('AdminCatalog');
        $idOrders = (int) Tab::getIdFromClassName('AdminParentOrders');
        if ($idCatalog && $idOrders) {
            $db = Db::getInstance();
            $catalogAt = (int) $db->getValue('SELECT `position` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idCatalog);
            $ordersAt = (int) $db->getValue('SELECT `position` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idOrders);
            if ($ordersAt > $catalogAt) {
                $db->execute(
                    'UPDATE `'._DB_PREFIX_.'tab` SET `position` = `position` + 1
                    WHERE `id_parent` = 0 AND `position` >= '.$catalogAt.' AND `position` < '.$ordersAt
                );
                $db->update('tab', array('position' => $catalogAt), 'id_tab = '.$idOrders);
                PrestaShopLogger::addLog('salisbergguide: Bookings moved ahead of Rooms & Services in the side menu', 1);
            }
        }

        return Configuration::updateValue('SBGUIDE_MENU_ORDER', 1);
    }

    /**
     * SIMPLIFY-REVIEW P-17: the Hotel Staff menu is the desk only.
     *  - Refund requests moves from Manage Hotel to the end of Bookings (for
     *    everyone), so Manage Hotel holds nothing a front desk needs.
     *  - Hotel Staff lose view on Manage Hotel, Addresses and Booking messages.
     * Done once (SBGUIDE_STAFF_DESK_MENU), so a right given back by hand in
     * Administration > Permissions, or a menu moved later, is not undone.
     */
    protected function deskOnlyStaffMenu()
    {
        if (Configuration::get('SBGUIDE_STAFF_DESK_MENU')) {
            return true;
        }
        $db = Db::getInstance();
        $idRefunds = (int) Tab::getIdFromClassName('AdminOrderRefundRequests');
        $idBookings = (int) Tab::getIdFromClassName('AdminParentOrders');
        $idHotel = (int) Tab::getIdFromClassName('AdminHotelReservationSystemManagement');
        if ($idRefunds && $idBookings && $idHotel
            && (int) $db->getValue('SELECT `id_parent` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idRefunds) === $idHotel
        ) {
            $last = (int) $db->getValue('SELECT MAX(`position`) FROM `'._DB_PREFIX_.'tab` WHERE `id_parent` = '.$idBookings);
            $db->update('tab', array('id_parent' => $idBookings, 'position' => $last + 1), 'id_tab = '.$idRefunds);
            PrestaShopLogger::addLog('salisbergguide: Refund requests moved under Bookings', 1);
        }
        $idStaff = (int) $this->getProfileId(self::STAFF_PROFILE);
        if ($idStaff) {
            $revoked = array();
            foreach (array('AdminHotelReservationSystemManagement', 'AdminAddresses', 'AdminOrderMessage') as $className) {
                $idTab = (int) Tab::getIdFromClassName($className);
                if ($idTab && $db->update(
                    'access',
                    array('view' => 0, 'add' => 0, 'edit' => 0, 'delete' => 0),
                    'id_profile = '.$idStaff.' AND id_tab = '.$idTab.' AND `view` = 1'
                ) && $db->Affected_Rows()) {
                    $revoked[] = $className;
                }
            }
            if ($revoked) {
                PrestaShopLogger::addLog('salisbergguide: Hotel Staff menu trimmed: '.implode(', ', $revoked), 1);
            }
        }

        return Configuration::updateValue('SBGUIDE_STAFF_DESK_MENU', 1);
    }

    /**
     * Owner, 2026-11-20: Recycle bin and Statuses leave Bookings, and Positions
     * leaves Modules and Services; all three go to the end of Administration.
     * An entry is moved only while it is still in its original section, and
     * once (SBGUIDE_ADMIN_MOVES), so a menu moved later by hand is kept.
     * Permissions are per page, not per section, so nobody gains or loses access.
     */
    protected function moveToAdministration()
    {
        if (Configuration::get('SBGUIDE_ADMIN_MOVES')) {
            return true;
        }
        $db = Db::getInstance();
        $idAdmin = (int) Tab::getIdFromClassName('AdminAdmin');
        if (!$idAdmin) {
            return false;
        }
        $moves = array(
            'AdminSalisbergRecycleBin' => 'AdminParentOrders',
            'AdminStatuses' => 'AdminParentOrders',
            'AdminModulesPositions' => 'AdminParentModules',
        );
        $moved = array();
        foreach ($moves as $className => $fromClass) {
            $idTab = (int) Tab::getIdFromClassName($className);
            $idFrom = (int) Tab::getIdFromClassName($fromClass);
            if (!$idTab || !$idFrom
                || (int) $db->getValue('SELECT `id_parent` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idTab) !== $idFrom
            ) {
                continue;
            }
            $last = (int) $db->getValue('SELECT MAX(`position`) FROM `'._DB_PREFIX_.'tab` WHERE `id_parent` = '.$idAdmin);
            $db->update('tab', array('id_parent' => $idAdmin, 'position' => $last + 1), 'id_tab = '.$idTab);
            $moved[] = $className;
        }
        if ($moved) {
            PrestaShopLogger::addLog('salisbergguide: moved under Administration: '.implode(', ', $moved), 1);
        }

        return Configuration::updateValue('SBGUIDE_ADMIN_MOVES', 1);
    }

    /**
     * SIMPLIFY-REVIEW P-16: whether an employee may see the hotel's income
     * (sales totals, revenue and performance charts, income reports).
     * Everyone except the Hotel Staff profile. The one rule; callers in other
     * modules use it through class_exists('Salisbergguide').
     */
    public static function seesIncome($employee = null)
    {
        $employee = $employee ?: Context::getContext()->employee;
        if (!$employee || !$employee->id || $employee->isSuperAdmin()) {
            return true;
        }
        $idStaff = (int) Db::getInstance()->getValue(
            'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang`
            WHERE `name` = \''.pSQL(self::STAFF_PROFILE).'\' AND `id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT')
        );

        return (int) $employee->id_profile !== $idStaff;
    }

    /** Hotel Staff (P-17): the top bar shows only what the desk uses */
    public function isDeskOnly()
    {
        $employee = $this->context->employee;

        return $employee && $employee->id && !$employee->isSuperAdmin()
            && (int) $employee->id_profile === $this->getProfileId(self::STAFF_PROFILE);
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

    protected function hideUnusedMenus()
    {
        foreach (self::$hiddenMenus as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if (!$idTab) {
                continue;
            }
            $active = (int) Db::getInstance()->getValue(
                'SELECT `active` FROM `'._DB_PREFIX_.'tab` WHERE `id_tab` = '.$idTab
            );
            if ($active) {
                Db::getInstance()->update('tab', array('active' => 0), 'id_tab = '.$idTab);
                PrestaShopLogger::addLog('salisbergguide: hid the menu entry '.$className, 1);
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
        $controller->addCSS($this->_path.'views/css/install-app.css', 'all');
        $controller->addJS($this->_path.'views/js/install-app.js');
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
        $this->addNewsPopup();
        $this->context->smarty->assign('sb_desk_only', $this->isDeskOnly());
    }

    const NEWS_SEEN = 'SBGUIDE_NEWS_SEEN_';

    /** The newest date on the What's New page, read from the template ('' if none) */
    public function latestNewsKey()
    {
        $raw = (string) @file_get_contents($this->local_path.'views/templates/admin/whats_new.tpl');

        return preg_match("/<section>\\s*<h3>\\{l s='([^']+)'/", $raw, $m) ? $m[1] : '';
    }

    public function markNewsSeen($idEmployee)
    {
        Configuration::updateValue(self::NEWS_SEEN.(int) $idEmployee, $this->latestNewsKey());
    }

    /**
     * "What's new" pop-up, once per person per update (owner, 2026-11-19): when
     * the newest date on What's New is not the one this person last saw, the
     * items of that date for their role are shown with View more and the
     * guide. The items come from the What's New page itself, so there is one
     * place to write them.
     */
    protected function addNewsPopup()
    {
        $employee = $this->context->employee;
        $controller = $this->context->controller;
        if (!Validate::isLoadedObject($employee) || !$controller || Tools::getValue('ajax')
            || Tools::getValue('controller') === 'AdminSalisbergWhatsNew') {
            return;
        }
        $key = $this->latestNewsKey();
        if ($key === '' || Configuration::get(self::NEWS_SEEN.(int) $employee->id) === $key) {
            return;
        }
        try {
            $html = $this->renderGuide('whats_new.tpl');
        } catch (Exception $e) {
            return;
        }
        if (!preg_match('/<section>\s*<h3>(.*?)<\/h3>\s*<ul>(.*?)<\/ul>/s', $html, $m) || !trim(strip_tags($m[2]))) {
            return;
        }
        $canAdmin = $this->canReadAdminGuide();
        Media::addJsDef(array('sbNews' => array(
            'date' => trim(strip_tags($m[1])),
            'items' => $m[2],
            'more' => $this->employeeCanView('AdminSalisbergWhatsNew') ? $this->context->link->getAdminLink('AdminSalisbergWhatsNew') : '',
            'guide' => $this->context->link->getAdminLink($canAdmin ? 'AdminSalisbergAdminGuide' : 'AdminSalisbergStaffGuide'),
            'guideLabel' => $canAdmin ? $this->l('Open the Admin Guide') : $this->l('Open the Staff Guide'),
        )));
        $controller->addCSS($this->_path.'views/css/news-popup.css', 'all');
        $controller->addJS($this->_path.'views/js/news-popup.js');
        // shown once: it counts as seen as soon as it is shown (no request needed,
        // so it works for every role whatever pages they may open)
        $this->markNewsSeen((int) $employee->id);
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
            'AdminDashboard', 'AdminSalisbergRoomBoard', 'AdminSalisbergWalkIn', 'AdminSalisbergHousekeeping', 'AdminSalisbergCharges', 'AdminHotelRoomsBooking', 'AdminOrders', 'AdminInvoices', 'AdminCustomers', 'AdminCarts',
            'AdminCustomerThreads', 'AdminOrderRefundRequests', 'AdminProducts', 'AdminNormalProducts', 'AdminAddHotel',
            'AdminHotelFeatures', 'AdminHotelFeaturePricesSettings', 'AdminHotelGeneralSettings', 'AdminOrderRefundRules',
            'AdminCartRules', 'AdminModules', 'AdminPayment', 'AdminCurrencies', 'AdminTaxes', 'AdminTaxRulesGroup',
            'AdminPreferences', 'AdminThemes', 'AdminMeta', 'AdminCmsContent', 'AdminMaintenance', 'AdminEmails',
            'AdminBackup', 'AdminInformation', 'AdminEmployees', 'AdminProfiles', 'AdminAccess', 'AdminContacts', 'AdminStats',
            'AdminSalisbergGuide', 'AdminSalisbergStaffGuide', 'AdminSalisbergAdminGuide', 'AdminSalisbergWhatsNew',
            'AdminSalisbergReports', 'AdminSalisbergIntegrations',
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
