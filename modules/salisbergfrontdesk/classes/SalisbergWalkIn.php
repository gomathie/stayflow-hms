<?php
/**
 * Guided front-desk booking orchestration for Salisberg.
 *
 * The existing Cart, HotelCartBookingData, PaymentModule and
 * HotelBookingDetail classes remain authoritative. This class only keeps the
 * receptionist's draft choices and joins those existing operations together.
 *
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 */

class SalisbergWalkIn
{
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETE = 'complete';

    protected $module;
    protected $context;

    public function __construct(Salisbergfrontdesk $module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    public static function ensureTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sb_walkin_draft` (
                `id_walkin_draft` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_cart` INT UNSIGNED NOT NULL,
                `id_employee` INT UNSIGNED NOT NULL,
                `token` CHAR(32) NOT NULL,
                `step` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `state` LONGTEXT NOT NULL,
                `status` VARCHAR(16) NOT NULL DEFAULT \'active\',
                `id_order` INT UNSIGNED NOT NULL DEFAULT 0,
                `date_add` DATETIME NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_walkin_draft`),
                UNIQUE KEY `uniq_sb_walkin_cart` (`id_cart`),
                UNIQUE KEY `uniq_sb_walkin_token` (`token`),
                KEY `idx_sb_walkin_employee` (`id_employee`, `status`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4'
        );
    }

    public function getBusinessDate()
    {
        // config/config.inc.php sets PHP's timezone from PS_TIMEZONE.
        return date('Y-m-d');
    }

    public function normaliseStay(array $values)
    {
        $arrival = trim((string) (isset($values['arrival']) ? $values['arrival'] : ''));
        $departure = trim((string) (isset($values['departure']) ? $values['departure'] : ''));
        $nights = (int) (isset($values['nights']) ? $values['nights'] : 0);
        $adults = max(1, (int) (isset($values['adults']) ? $values['adults'] : 1));
        $children = max(0, (int) (isset($values['children']) ? $values['children'] : 0));
        $hotel = (int) (isset($values['id_hotel']) ? $values['id_hotel'] : 0);
        $errors = array();

        if (!Validate::isDate($arrival)) {
            $errors['arrival'] = $this->module->l('Enter a valid arrival date.');
        }
        if (!Validate::isDate($departure)) {
            $errors['departure'] = $this->module->l('Enter a valid departure date.');
        }
        if (!$errors) {
            if (strtotime($arrival) < strtotime($this->getBusinessDate())) {
                $errors['arrival'] = $this->module->l('Arrival cannot be before the hotel business date.');
            }
            if (strtotime($departure) <= strtotime($arrival)) {
                $errors['departure'] = $this->module->l('Departure must be after arrival.');
            }
            $nights = (int) HotelHelper::getNumberOfDays($arrival, $departure);
            if ($nights < 1) {
                $errors['nights'] = $this->module->l('The stay must be at least one night.');
            }
        }
        if ($adults > 20 || $children > 20) {
            $errors['guests'] = $this->module->l('The guest count is not valid.');
        }

        $hotels = $this->module->getHotels();
        if (!$hotel && $hotels) {
            $hotel = (int) reset($hotels);
        }
        if (!$hotel || !in_array($hotel, $hotels)) {
            $errors['hotel'] = $this->module->l('You do not have access to this hotel.');
        }

        return array(
            'stay' => array(
                'arrival' => $arrival,
                'departure' => $departure,
                'nights' => $nights,
                'adults' => $adults,
                'children' => $children,
                'id_hotel' => $hotel,
            ),
            'errors' => $errors,
        );
    }

    public function defaultStay()
    {
        $arrival = $this->getBusinessDate();

        return array(
            'arrival' => $arrival,
            'departure' => date('Y-m-d', strtotime('+1 day', strtotime($arrival))),
            'nights' => 1,
            'adults' => 1,
            'children' => 0,
            'id_hotel' => 0,
        );
    }

    protected function occupancy(array $stay)
    {
        return array(array(
            'adults' => (int) $stay['adults'],
            'children' => (int) $stay['children'],
            'child_ages' => array_fill(0, (int) $stay['children'], 0),
        ));
    }

    public function getRoomBoard(array $stay)
    {
        $booking = new HotelBookingDetail();
        $data = $booking->getBookingData(array(
            'date_from' => $stay['arrival'],
            'date_to' => $stay['departure'],
            'hotel_id' => (int) $stay['id_hotel'],
            'id_room_type' => 0,
            'occupancy' => $this->occupancy($stay),
            'search_available' => 1,
            'search_partial' => 1,
            'search_booked' => 1,
            'search_unavai' => 1,
            'only_search_data' => 1,
            'full_detail' => 1,
        ));

        $available = array();
        foreach ((array) (isset($data['rm_data']) ? $data['rm_data'] : array()) as $type) {
            foreach ((array) (isset($type['data']['available']) ? $type['data']['available'] : array()) as $room) {
                $available[(int) $room['id_room']] = true;
            }
        }

        $today = pSQL($this->getBusinessDate());
        $occupied = array_map('intval', array_column((array) Db::getInstance()->executeS(
            'SELECT DISTINCT `id_room` FROM `'._DB_PREFIX_.'htl_booking_detail`
            WHERE `id_hotel` = '.(int) $stay['id_hotel'].' AND `is_cancelled` = 0 AND `is_refunded` = 0
            AND `id_status` = '.(int) HotelBookingDetail::STATUS_CHECKED_IN.'
            AND DATE(`date_from`) <= \''.$today.'\' AND DATE(`date_to`) >= \''.$today.'\''
        ), 'id_room'));

        $rows = (array) Db::getInstance()->executeS(
            'SELECT r.`id` AS `id_room`, r.`room_num`, r.`id_product`, r.`id_hotel`, r.`id_status` AS `room_state`,
                pl.`name` AS `room_type`, pl.`link_rewrite`, rt.`max_adults`, rt.`max_children`, rt.`max_guests`,
                IFNULL(rs.`status`, '.(int) Salisbergfrontdesk::ROOM_CLEAN.') AS `housekeeping`
            FROM `'._DB_PREFIX_.'htl_room_information` r
            INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.`id_product` = r.`id_product`
                AND pl.`id_lang` = '.(int) $this->context->language->id.' AND pl.`id_shop` = '.(int) $this->context->shop->id.'
            INNER JOIN `'._DB_PREFIX_.'htl_room_type` rt ON rt.`id_product` = r.`id_product`
            LEFT JOIN `'._DB_PREFIX_.'sb_room_status` rs ON rs.`id_room` = r.`id`
            WHERE r.`id_hotel` = '.(int) $stay['id_hotel'].'
            ORDER BY pl.`name`, r.`room_num`'
        );

        $quotes = array();
        $groups = array();
        foreach ($rows as $row) {
            $idProduct = (int) $row['id_product'];
            if (!isset($quotes[$idProduct])) {
                $quotes[$idProduct] = $this->estimateQuote($idProduct, $stay, 0);
            }
            $readiness = $this->readiness($row, in_array((int) $row['id_room'], $occupied));
            $room = array_merge($row, array(
                'available' => isset($available[(int) $row['id_room']]),
                'readiness' => $readiness,
                'quote' => $quotes[$idProduct],
                'image' => $this->productImage($idProduct, $row['link_rewrite']),
            ));
            if (!isset($groups[$idProduct])) {
                $groups[$idProduct] = array(
                    'id_product' => $idProduct,
                    'name' => $row['room_type'],
                    'rooms' => array(),
                );
            }
            $groups[$idProduct]['rooms'][] = $room;
        }

        return array_values($groups);
    }

    protected function readiness(array $room, $occupied)
    {
        if ((int) $room['room_state'] == HotelRoomInformation::STATUS_INACTIVE
            || (int) $room['room_state'] == HotelRoomInformation::STATUS_TEMPORARY_INACTIVE
        ) {
            return array('code' => 'out', 'label' => $this->module->l('Out of service'), 'ready' => false);
        }
        if ($occupied) {
            return array('code' => 'occupied', 'label' => $this->module->l('Occupied'), 'ready' => false);
        }
        if ((int) $room['housekeeping'] == Salisbergfrontdesk::ROOM_DIRTY) {
            return array('code' => 'cleaning', 'label' => $this->module->l('Needs cleaning'), 'ready' => false);
        }

        return array('code' => 'ready', 'label' => $this->module->l('Ready'), 'ready' => true);
    }

    protected function productImage($idProduct, $rewrite)
    {
        $cover = Image::getCover((int) $idProduct);
        if (!$cover || empty($cover['id_image'])) {
            return '';
        }

        return $this->context->link->getImageLink($rewrite, (int) $cover['id_image'], 'home_default');
    }

    protected function estimateQuote($idProduct, array $stay, $idRoom)
    {
        $nightly = array();
        $totalIncl = 0;
        $totalExcl = 0;
        for ($date = $stay['arrival']; $date < $stay['departure']; $date = date('Y-m-d', strtotime('+1 day', strtotime($date)))) {
            $next = date('Y-m-d', strtotime('+1 day', strtotime($date)));
            try {
                $price = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice(
                    (int) $idProduct,
                    $date,
                    $next,
                    $this->occupancy($stay),
                    0,
                    0,
                    0,
                    (int) $idRoom,
                    1,
                    1
                );
                $incl = (float) $price['total_price_tax_incl'];
                $excl = (float) $price['total_price_tax_excl'];
            } catch (Exception $e) {
                $incl = (float) Product::getPriceStatic((int) $idProduct, true);
                $excl = (float) Product::getPriceStatic((int) $idProduct, false);
            }
            $nightly[] = array('date' => $date, 'amount' => $incl, 'amount_text' => $this->money($incl));
            $totalIncl += $incl;
            $totalExcl += $excl;
        }

        return array(
            'nightly' => $nightly,
            'total' => $totalIncl,
            'total_text' => $this->money($totalIncl),
            'taxes' => max(0, $totalIncl - $totalExcl),
            'taxes_text' => $this->money(max(0, $totalIncl - $totalExcl)),
            'average' => $stay['nights'] ? $totalIncl / $stay['nights'] : $totalIncl,
            'average_text' => $this->money($stay['nights'] ? $totalIncl / $stay['nights'] : $totalIncl),
        );
    }

    protected function money($amount, $idCurrency = 0)
    {
        return Tools::displayPrice((float) $amount, $idCurrency ?: (int) Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    public function getRoom($idRoom)
    {
        $hotels = $this->module->getHotels();
        if (!$hotels) {
            return false;
        }

        return Db::getInstance()->getRow(
            'SELECT r.`id` AS `id_room`, r.`room_num`, r.`id_product`, r.`id_hotel`, r.`id_status` AS `room_state`,
                pl.`name` AS `room_type`, pl.`link_rewrite`, rt.`max_adults`, rt.`max_children`, rt.`max_guests`,
                IFNULL(rs.`status`, '.(int) Salisbergfrontdesk::ROOM_CLEAN.') AS `housekeeping`
            FROM `'._DB_PREFIX_.'htl_room_information` r
            INNER JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.`id_product` = r.`id_product`
                AND pl.`id_lang` = '.(int) $this->context->language->id.' AND pl.`id_shop` = '.(int) $this->context->shop->id.'
            INNER JOIN `'._DB_PREFIX_.'htl_room_type` rt ON rt.`id_product` = r.`id_product`
            LEFT JOIN `'._DB_PREFIX_.'sb_room_status` rs ON rs.`id_room` = r.`id`
            WHERE r.`id` = '.(int) $idRoom.' AND r.`id_hotel` IN ('.implode(',', $hotels).')'
        );
    }

    public function roomAvailable($idRoom, array $stay)
    {
        $room = $this->getRoom($idRoom);
        if (!$room || (int) $room['id_hotel'] != (int) $stay['id_hotel']) {
            return false;
        }
        if ((int) $stay['adults'] > (int) $room['max_adults']
            || (int) $stay['children'] > (int) $room['max_children']
            || ((int) $stay['adults'] + (int) $stay['children']) > (int) $room['max_guests']
        ) {
            return false;
        }
        foreach ($this->getRoomBoard($stay) as $group) {
            foreach ($group['rooms'] as $candidate) {
                if ((int) $candidate['id_room'] == (int) $idRoom) {
                    return (bool) $candidate['available'];
                }
            }
        }

        return false;
    }

    protected function createCart(array $stay, $idRoom)
    {
        $room = $this->getRoom($idRoom);
        if (!$room || !$this->roomAvailable($idRoom, $stay)) {
            throw new Exception($this->module->l('That room is no longer available for the complete stay.'));
        }

        if (!isset($this->context->cookie->id_guest) || !(int) $this->context->cookie->id_guest) {
            Guest::setNewGuest($this->context->cookie);
        }
        $cart = new Cart();
        $cart->gift = 0;
        $cart->id_shop = (int) $this->context->shop->id;
        $cart->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $cart->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_address_delivery = 0;
        $cart->id_address_invoice = 0;
        $cart->id_guest = (int) $this->context->cookie->id_guest;
        $cart->setNoMultishipping();
        if (!$cart->add()) {
            throw new Exception($this->module->l('The booking draft could not be created.'));
        }
        $this->context->cart = $cart;

        $cartBooking = new HotelCartBookingData();
        if (!$cartBooking->updateCartBooking(
            (int) $room['id_product'],
            $this->occupancy($stay),
            'up',
            (int) $room['id_hotel'],
            (int) $room['id_room'],
            $stay['arrival'],
            $stay['departure'],
            array(),
            array(),
            (int) $cart->id,
            (int) $cart->id_guest,
            HotelBookingDetail::ALLOTMENT_MANUAL
        )) {
            $cart->delete();
            throw new Exception($this->module->l('The selected room could not be added. Refresh availability and try again.'));
        }
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        return new Cart((int) $cart->id);
    }

    public function createReservationCart(array $stay, $idRoom)
    {
        return $this->createCart($stay, $idRoom);
    }

    public function createDraft(array $stay, $idRoom)
    {
        $cart = $this->createCart($stay, $idRoom);
        $room = $this->getRoom($idRoom);
        $state = array(
            'stay' => array_merge($stay, array('id_room' => (int) $idRoom, 'id_product' => (int) $room['id_product'])),
            'guest' => array(),
            'payment' => array(),
            'charge_tax' => 1,
            'quote' => $this->getCartBreakdown($cart, true),
        );
        $now = date('Y-m-d H:i:s');
        $token = bin2hex(random_bytes(16));
        $saved = Db::getInstance()->insert('sb_walkin_draft', array(
            'id_cart' => (int) $cart->id,
            'id_employee' => (int) $this->context->employee->id,
            'token' => pSQL($token),
            'step' => 2,
            'state' => pSQL(Tools::jsonEncode($state), true),
            'status' => self::STATUS_ACTIVE,
            'id_order' => 0,
            'date_add' => pSQL($now),
            'date_upd' => pSQL($now),
        ));
        if (!$saved) {
            throw new Exception($this->module->l('The guided booking could not be started.'));
        }

        return $this->getDraft((int) Db::getInstance()->Insert_ID());
    }

    public function getDraft($idDraft, $lock = false)
    {
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'sb_walkin_draft`
            WHERE `id_walkin_draft` = '.(int) $idDraft.' AND `id_employee` = '.(int) $this->context->employee->id
            .($lock ? ' FOR UPDATE' : '')
        );
        if (!$row) {
            return false;
        }
        $row['state'] = Tools::jsonDecode($row['state'], true);
        if (!is_array($row['state'])) {
            $row['state'] = array();
        }

        return $row;
    }

    protected function saveDraft(array $draft, array $state, $step = null, $status = null, $idOrder = null)
    {
        $values = array(
            'state' => pSQL(Tools::jsonEncode($state), true),
            'date_upd' => pSQL(date('Y-m-d H:i:s')),
        );
        if ($step !== null) {
            $values['step'] = (int) $step;
        }
        if ($status !== null) {
            $values['status'] = pSQL($status);
        }
        if ($idOrder !== null) {
            $values['id_order'] = (int) $idOrder;
        }

        return Db::getInstance()->update('sb_walkin_draft', $values, '`id_walkin_draft` = '.(int) $draft['id_walkin_draft']);
    }

    protected function getCartRoom($idCart)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'htl_cart_booking_data`
            WHERE `id_cart` = '.(int) $idCart.' ORDER BY `id` LIMIT 1'
        );
    }

    public function saveStay(array $draft, array $stay, $idRoom, $chargeTax)
    {
        if ($draft['status'] != self::STATUS_ACTIVE) {
            throw new Exception($this->module->l('This booking is already complete.'));
        }
        $cart = new Cart((int) $draft['id_cart']);
        if (!Validate::isLoadedObject($cart) || $cart->orderExists()) {
            throw new Exception($this->module->l('This booking draft is no longer available.'));
        }
        if (!$this->roomAvailable($idRoom, $stay)) {
            throw new Exception($this->module->l('That room is not available for the complete stay or cannot hold this many guests.'));
        }
        $old = $this->getCartRoom($cart->id);
        $room = $this->getRoom($idRoom);
        if (!$old || !$room) {
            throw new Exception($this->module->l('The room selection could not be loaded.'));
        }

        Db::getInstance()->execute('START TRANSACTION');
        try {
            $oldBooking = new HotelCartBookingData((int) $old['id']);
            if (!$oldBooking->deleteCartBookingData(
                (int) $cart->id,
                (int) $old['id_product'],
                (int) $old['id_room'],
                $old['date_from'],
                $old['date_to']
            )) {
                throw new Exception($this->module->l('The old room selection could not be updated.'));
            }
            $this->context->cart = new Cart((int) $cart->id);
            $newBooking = new HotelCartBookingData();
            if (!$newBooking->updateCartBooking(
                (int) $room['id_product'],
                $this->occupancy($stay),
                'up',
                (int) $room['id_hotel'],
                (int) $room['id_room'],
                $stay['arrival'],
                $stay['departure'],
                array(),
                array(),
                (int) $cart->id,
                (int) $cart->id_guest,
                HotelBookingDetail::ALLOTMENT_MANUAL
            )) {
                throw new Exception($this->module->l('The new room selection could not be saved.'));
            }
            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            throw $e;
        }

        Cache::clear();
        $cart = new Cart((int) $cart->id);
        $state = $draft['state'];
        $state['stay'] = array_merge($stay, array('id_room' => (int) $idRoom, 'id_product' => (int) $room['id_product']));
        $state['charge_tax'] = $chargeTax ? 1 : 0;
        $state['quote'] = $this->getCartBreakdown($cart, $chargeTax);
        $state['payment'] = array();
        $this->saveDraft($draft, $state, 2);

        return $this->getDraft($draft['id_walkin_draft']);
    }

    public function searchGuests($term)
    {
        $term = trim((string) $term);
        if (Tools::strlen($term) < 2) {
            return array();
        }
        $safe = pSQL(str_replace(array('%', '_'), '', $term));
        $digits = pSQL(preg_replace('/\D+/', '', $term));
        $phone = '';
        if (Tools::strlen($digits) >= 3) {
            $phone = ' OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.`phone`, \' \', \'\'), \'-\', \'\'), \'+\', \'\'), \'(\', \'\'), \')\', \'\') LIKE \'%'.$digits.'%\'';
        }
        $rows = (array) Db::getInstance()->executeS(
            'SELECT c.`id_customer`, c.`firstname`, c.`lastname`, c.`email`, c.`phone`, c.`company`
            FROM `'._DB_PREFIX_.'customer` c
            WHERE c.`deleted` = 0 AND c.`active` = 1
            AND (CONCAT(c.`firstname`, \' \', c.`lastname`) LIKE \'%'.$safe.'%\'
                OR c.`email` LIKE \'%'.$safe.'%\''.$phone.')
            ORDER BY c.`lastname`, c.`firstname` LIMIT 20'
        );
        foreach ($rows as &$row) {
            if (Customer::hasNoEmail($row['email'])) {
                $row['email'] = '';
            }
        }
        unset($row);

        return $rows;
    }

    protected function duplicateGuest($email, $phone, $ignoreId = 0)
    {
        if ($email !== '') {
            $id = (int) Db::getInstance()->getValue(
                'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
                WHERE `email` = \''.pSQL($email).'\' AND `deleted` = 0 AND `active` = 1
                AND `id_customer` != '.(int) $ignoreId.' ORDER BY `id_customer` DESC'
            );
            if ($id) {
                return $id;
            }
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (Tools::strlen($digits) >= 7) {
            return (int) Db::getInstance()->getValue(
                'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
                WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`phone`, \' \', \'\'), \'-\', \'\'), \'+\', \'\'), \'(\', \'\'), \')\', \'\') = \''.pSQL($digits).'\'
                AND `deleted` = 0 AND `active` = 1 AND `id_customer` != '.(int) $ignoreId.' ORDER BY `id_customer` DESC'
            );
        }

        return 0;
    }

    public function saveGuest(array $draft, array $values)
    {
        $idCustomer = (int) (isset($values['id_customer']) ? $values['id_customer'] : 0);
        $created = false;
        if ($idCustomer) {
            $customer = new Customer($idCustomer);
            if (!Validate::isLoadedObject($customer) || $customer->deleted || !$customer->active) {
                throw new Exception($this->module->l('The selected guest is no longer available.'));
            }
        } else {
            $firstname = trim((string) (isset($values['firstname']) ? $values['firstname'] : ''));
            $lastname = trim((string) (isset($values['lastname']) ? $values['lastname'] : ''));
            $phone = trim((string) (isset($values['phone']) ? $values['phone'] : ''));
            $email = trim((string) (isset($values['email']) ? $values['email'] : ''));
            $company = trim((string) (isset($values['company']) ? $values['company'] : ''));
            if ($firstname === '' || !Validate::isName($firstname)) {
                throw new Exception($this->module->l('Enter a valid first name.'));
            }
            if ($lastname === '' || !Validate::isName($lastname)) {
                throw new Exception($this->module->l('Enter a valid last name.'));
            }
            if ($phone === '' || !Validate::isPhoneNumber($phone)) {
                throw new Exception($this->module->l('Enter a valid phone number.'));
            }
            if ($email !== '' && !Validate::isEmail($email)) {
                throw new Exception($this->module->l('Enter a valid email address or leave it blank.'));
            }
            if ($company !== '' && !Validate::isGenericName($company)) {
                throw new Exception($this->module->l('The company name is not valid.'));
            }
            if ($duplicate = $this->duplicateGuest($email, $phone)) {
                throw new Exception(sprintf(
                    $this->module->l('A guest with this email or phone is already on record (#%d). Search for and select that guest.'),
                    $duplicate
                ));
            }

            $customer = new Customer();
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->phone = $phone;
            $customer->company = $company;
            $customer->email = $email !== ''
                ? $email
                : 'guest-'.date('YmdHis').'-'.Tools::passwdGen(6, 'NUMERIC').'@'.Customer::NO_EMAIL_DOMAIN;
            $customer->passwd = Tools::encrypt(Tools::passwdGen(16));
            $customer->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            $customer->active = 1;
            if (!$customer->add()) {
                throw new Exception($this->module->l('The guest could not be saved.'));
            }
            $customer->updateGroup(array((int) $customer->id_default_group));
            $idCustomer = (int) $customer->id;
            $created = true;
        }

        $cart = new Cart((int) $draft['id_cart']);
        $this->attachCustomer($cart, $customer);
        $state = $draft['state'];
        $state['guest'] = array(
            'id_customer' => $idCustomer,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'phone' => $customer->phone,
            'email' => Customer::hasNoEmail($customer->email) ? '' : $customer->email,
            'company' => $customer->company,
            'created' => $created,
            'special_requests' => trim((string) (isset($values['special_requests']) ? $values['special_requests'] : '')),
        );
        $state['quote'] = $this->getCartBreakdown(new Cart((int) $cart->id), !empty($state['charge_tax']));
        $this->saveDraft($draft, $state, 3);

        return $this->getDraft($draft['id_walkin_draft']);
    }

    protected function attachCustomer(Cart $cart, Customer $customer)
    {
        $addresses = $customer->getAddresses((int) $cart->id_lang);
        $cart->id_customer = (int) $customer->id;
        $cart->secure_key = $customer->secure_key;
        if ($addresses) {
            $cart->id_address_delivery = (int) $addresses[0]['id_address'];
            $cart->id_address_invoice = (int) $addresses[0]['id_address'];
        }
        $cart->setNoMultishipping();
        if (!$cart->save()) {
            throw new Exception($this->module->l('The guest could not be attached to the booking.'));
        }
        Db::getInstance()->update(
            'htl_cart_booking_data',
            array('id_customer' => (int) $customer->id),
            '`id_cart` = '.(int) $cart->id
        );
        $this->context->cart = $cart;
        $this->context->customer = $customer;
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
    }

    public function paymentMethods()
    {
        if (Module::isEnabled('salisbergpay') && ($pay = Module::getInstanceByName('salisbergpay'))) {
            return $pay->getAvailableMethods();
        }

        return array('cash' => array('code' => 'cash', 'title' => $this->module->l('Cash'), 'hint' => ''));
    }

    public function savePayment(array $draft, array $values)
    {
        if (empty($draft['state']['guest']['id_customer'])) {
            throw new Exception($this->module->l('Choose the guest before recording payment.'));
        }
        $methods = $this->paymentMethods();
        $method = (string) (isset($values['method']) ? $values['method'] : '');
        if (!isset($methods[$method])) {
            throw new Exception($this->module->l('Choose an available payment method.'));
        }
        $arrangement = (string) (isset($values['arrangement']) ? $values['arrangement'] : 'later');
        if (!in_array($arrangement, array('full', 'partial', 'later'), true)) {
            throw new Exception($this->module->l('Choose full payment, partial payment or pay later.'));
        }
        $breakdown = $this->getCartBreakdown(new Cart((int) $draft['id_cart']), !empty($draft['state']['charge_tax']));
        $amount = 0;
        if ($arrangement == 'full') {
            $amount = (float) $breakdown['total'];
        } elseif ($arrangement == 'partial') {
            $amount = (float) (isset($values['amount']) ? $values['amount'] : 0);
            if ($amount <= 0 || $amount >= (float) $breakdown['total']) {
                throw new Exception($this->module->l('A partial payment must be more than zero and less than the total.'));
            }
        }
        $confirmed = !empty($values['confirmed']) && $arrangement != 'later';
        $reference = trim((string) (isset($values['reference']) ? $values['reference'] : ''));
        $tendered = (float) (isset($values['tendered']) ? $values['tendered'] : 0);
        if ($method == 'cash' && $amount > 0 && !$confirmed) {
            throw new Exception($this->module->l('Confirm that the cash was received, or choose pay later.'));
        }
        if ($method == 'cash' && $confirmed && $tendered < $amount) {
            throw new Exception($this->module->l('Cash tendered cannot be less than the payment applied.'));
        }
        if ($method != 'cash' && $confirmed && $reference === '') {
            throw new Exception($this->module->l('Enter the confirmed transaction reference.'));
        }
        if ($reference !== '' && !Validate::isString($reference)) {
            throw new Exception($this->module->l('The transaction reference is not valid.'));
        }

        $state = $draft['state'];
        $state['quote'] = $breakdown;
        $state['payment'] = array(
            'method' => $method,
            'method_title' => $methods[$method]['title'],
            'arrangement' => $arrangement,
            'intended_amount' => $amount,
            'confirmed' => $confirmed,
            'confirmed_amount' => $confirmed ? $amount : 0,
            'reference' => $reference,
            'cash_tendered' => $method == 'cash' ? $tendered : 0,
            'change_due' => ($method == 'cash' && $confirmed) ? max(0, $tendered - $amount) : 0,
            'accepted_total' => (float) $breakdown['total'],
            'status' => $confirmed ? 'confirmed' : 'pending',
        );
        $this->saveDraft($draft, $state, 4);

        return $this->getDraft($draft['id_walkin_draft']);
    }

    public function getCartBreakdown(Cart $cart, $chargeTax = true)
    {
        if (!Validate::isLoadedObject($cart)) {
            return array();
        }
        $oldTax = Configuration::get('PS_TAX');
        if (!$chargeTax) {
            Configuration::set('PS_TAX', 0);
        }
        $oldCart = $this->context->cart;
        $oldCurrency = $this->context->currency;
        $oldCustomer = $this->context->customer;
        try {
            $this->context->cart = $cart;
            $this->context->currency = new Currency((int) $cart->id_currency);
            if ((int) $cart->id_customer) {
                $this->context->customer = new Customer((int) $cart->id_customer);
            }
            Cache::clear();
            $roomIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_ROOMS);
            $roomExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_ROOMS);
            $extrasIncl = (float) $cart->getOrderTotal(true, Cart::ONLY_ROOM_SERVICES)
                + (float) $cart->getOrderTotal(true, Cart::ONLY_STANDALONE_PRODUCTS);
            $extrasExcl = (float) $cart->getOrderTotal(false, Cart::ONLY_ROOM_SERVICES)
                + (float) $cart->getOrderTotal(false, Cart::ONLY_STANDALONE_PRODUCTS);
            $discounts = abs((float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS));
            $totalIncl = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $totalExcl = (float) $cart->getOrderTotal(false, Cart::BOTH);
            $currency = (int) $cart->id_currency;
            $result = array(
                'accommodation' => $roomIncl,
                'accommodation_text' => $this->money($roomIncl, $currency),
                'extras' => $extrasIncl,
                'extras_text' => $this->money($extrasIncl, $currency),
                'discounts' => $discounts,
                'discounts_text' => $this->money($discounts, $currency),
                'taxes' => max(0, $totalIncl - $totalExcl),
                'taxes_text' => $this->money(max(0, $totalIncl - $totalExcl), $currency),
                'total' => $totalIncl,
                'total_text' => $this->money($totalIncl, $currency),
                'tax_included' => $chargeTax && ($roomIncl > $roomExcl || $extrasIncl > $extrasExcl),
            );
        } finally {
            $this->context->cart = $oldCart;
            $this->context->currency = $oldCurrency;
            $this->context->customer = $oldCustomer;
            if (!$chargeTax) {
                Configuration::set('PS_TAX', $oldTax);
            }
        }

        return $result;
    }

    public function currentRoomSummary(array $draft)
    {
        $stay = $draft['state']['stay'];
        $room = $this->getRoom((int) $stay['id_room']);
        if (!$room) {
            return false;
        }
        $occupied = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_booking_detail`
            WHERE `id_room` = '.(int) $room['id_room'].' AND `is_cancelled` = 0 AND `is_refunded` = 0
            AND `id_status` = '.(int) HotelBookingDetail::STATUS_CHECKED_IN
        );
        $room['readiness'] = $this->readiness($room, $occupied > 0);
        $room['image'] = $this->productImage($room['id_product'], $room['link_rewrite']);

        return $room;
    }

    public function finalize(array $draft, $action, $acceptPrice)
    {
        if (!in_array($action, array('checkin', 'reserve'), true)) {
            throw new Exception($this->module->l('Choose how to complete this booking.'));
        }
        $oldTax = Configuration::get('PS_TAX');
        Db::getInstance()->execute('START TRANSACTION');
        try {
            $draft = $this->getDraft((int) $draft['id_walkin_draft'], true);
            if (!$draft) {
                throw new Exception($this->module->l('This booking draft could not be found.'));
            }
            if ($draft['status'] == self::STATUS_COMPLETE && (int) $draft['id_order']) {
                Db::getInstance()->execute('COMMIT');
                return array('id_order' => (int) $draft['id_order'], 'already_complete' => true);
            }
            if (empty($draft['state']['guest']['id_customer']) || empty($draft['state']['payment']['method'])) {
                throw new Exception($this->module->l('Complete the guest and payment steps first.'));
            }
            $cart = new Cart((int) $draft['id_cart']);
            if (!Validate::isLoadedObject($cart)) {
                throw new Exception($this->module->l('The booking cart could not be loaded.'));
            }
            if ($cart->orderExists()) {
                $idOrder = (int) Db::getInstance()->getValue(
                    'SELECT `id_order` FROM `'._DB_PREFIX_.'orders` WHERE `id_cart` = '.(int) $cart->id.' ORDER BY `id_order` DESC'
                );
                $this->saveDraft($draft, $draft['state'], 4, self::STATUS_COMPLETE, $idOrder);
                Db::getInstance()->execute('COMMIT');
                return array('id_order' => $idOrder, 'already_complete' => true);
            }

            $stay = $draft['state']['stay'];
            Db::getInstance()->getValue(
                'SELECT `id` FROM `'._DB_PREFIX_.'htl_room_information` WHERE `id` = '.(int) $stay['id_room'].' FOR UPDATE'
            );
            if (!$this->roomAvailable((int) $stay['id_room'], $stay)) {
                throw new Exception($this->module->l('The room became unavailable. No booking or payment was created. Choose another room.'));
            }
            $room = $this->currentRoomSummary($draft);
            if ($action == 'checkin') {
                if ($stay['arrival'] != $this->getBusinessDate()) {
                    throw new Exception($this->module->l('Only a stay arriving today can be checked in now.'));
                }
                if (!$room || empty($room['readiness']['ready'])) {
                    throw new Exception($this->module->l('The room is not ready. Save it as a reservation awaiting the room instead.'));
                }
            }

            if (empty($draft['state']['charge_tax'])) {
                Configuration::set('PS_TAX', 0);
            }
            $this->context->cart = $cart;
            $this->context->customer = new Customer((int) $cart->id_customer);
            $this->context->currency = new Currency((int) $cart->id_currency);
            $cartErrors = HotelCartBookingData::validateCartBookings(!Configuration::get('PS_ALLOW_ADD_ALL_SERVICES_IN_BOOKING'));
            if ($cartErrors) {
                throw new Exception(implode(' ', $cartErrors));
            }
            $breakdown = $this->getCartBreakdown($cart, !empty($draft['state']['charge_tax']));
            $payment = $draft['state']['payment'];
            if (abs((float) $payment['accepted_total'] - (float) $breakdown['total']) > 0.009 && !$acceptPrice) {
                $state = $draft['state'];
                $state['quote'] = $breakdown;
                $state['price_changed'] = array(
                    'from' => (float) $payment['accepted_total'],
                    'to' => (float) $breakdown['total'],
                    'from_text' => $this->money($payment['accepted_total'], $cart->id_currency),
                    'to_text' => $breakdown['total_text'],
                );
                Db::getInstance()->execute('ROLLBACK');
                Configuration::set('PS_TAX', $oldTax);
                $this->saveDraft($draft, $state, 4);

                return array('price_changed' => true);
            }
            if (!empty($draft['state']['price_changed']) && $acceptPrice) {
                $payment['accepted_total'] = (float) $breakdown['total'];
                if ($payment['arrangement'] == 'full') {
                    $payment['intended_amount'] = (float) $breakdown['total'];
                    $payment['confirmed_amount'] = $payment['confirmed'] ? (float) $breakdown['total'] : 0;
                }
            }
            if ((float) $payment['confirmed_amount'] > (float) $breakdown['total']) {
                throw new Exception($this->module->l('The confirmed payment is more than the current total. Return to payment and correct it.'));
            }

            $amount = (float) $payment['confirmed_amount'];
            if ($amount >= (float) $breakdown['total'] - 0.009) {
                $idState = (int) Configuration::get('PS_OS_PAYMENT_ACCEPTED');
            } elseif ($amount > 0) {
                $idState = (int) Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
            } else {
                $idState = (int) Configuration::get('PS_OS_AWAITING_PAYMENT');
            }
            $orderModule = new BoOrder();
            $orderModule->displayName = $payment['method_title'];
            $orderModule->payment_type = $payment['method'] == 'cash'
                ? OrderPayment::PAYMENT_TYPE_PAY_AT_HOTEL
                : OrderPayment::PAYMENT_TYPE_REMOTE_PAYMENT;
            $extra = ($amount > 0 && $payment['reference'] !== '')
                ? array('transaction_id' => $payment['reference'])
                : array();
            $message = $this->module->l('Walk-in booking -- Employee:').' '
                .substr($this->context->employee->firstname, 0, 1).'. '.$this->context->employee->lastname;
            if (!empty($draft['state']['guest']['special_requests'])) {
                $message .= '<br>'.$this->module->l('Guest request:').' '.strip_tags($draft['state']['guest']['special_requests']);
            }
            $orderModule->validateOrder(
                (int) $cart->id,
                $idState,
                Tools::ps_round($amount, 6),
                $payment['method_title'],
                $message,
                $extra,
                null,
                false,
                $cart->secure_key,
                null,
                true
            );
            $idOrder = (int) $orderModule->currentOrder;
            if (!$idOrder) {
                throw new Exception($this->module->l('The booking could not be created. No second payment is needed; retry this confirmation.'));
            }

            if ($action == 'checkin') {
                $bookings = (array) Db::getInstance()->executeS(
                    'SELECT `id` FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_order` = '.(int) $idOrder
                );
                foreach ($bookings as $booking) {
                    if ($error = $this->module->changeBookingStatus((int) $booking['id'], HotelBookingDetail::STATUS_CHECKED_IN)) {
                        throw new Exception($error);
                    }
                }
            }

            $state = $draft['state'];
            $state['quote'] = $breakdown;
            $state['payment'] = $payment;
            $state['completion'] = $action;
            unset($state['price_changed']);
            $this->saveDraft($draft, $state, 4, self::STATUS_COMPLETE, $idOrder);
            Db::getInstance()->execute('COMMIT');
            Configuration::set('PS_TAX', $oldTax);

            return array('id_order' => $idOrder, 'checked_in' => $action == 'checkin');
        } catch (Exception $e) {
            Db::getInstance()->execute('ROLLBACK');
            Configuration::set('PS_TAX', $oldTax);
            throw $e;
        }
    }

    public function success(array $draft)
    {
        if ($draft['status'] != self::STATUS_COMPLETE || !(int) $draft['id_order']) {
            return false;
        }
        $order = new Order((int) $draft['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }
        $customer = new Customer((int) $order->id_customer);
        $booking = Db::getInstance()->getRow(
            'SELECT `room_num`, `room_type_name`, `date_from`, `date_to`, `id_status`
            FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_order` = '.(int) $order->id.' ORDER BY `id`'
        );
        $balance = max(0, (float) $order->total_paid_tax_incl - (float) $order->total_paid_real);

        return array(
            'id_order' => (int) $order->id,
            'reference' => $order->reference,
            'guest' => $customer->firstname.' '.$customer->lastname,
            'room_num' => $booking['room_num'],
            'room_type' => $booking['room_type_name'],
            'arrival' => Tools::displayDate($booking['date_from']),
            'departure' => Tools::displayDate($booking['date_to']),
            'checked_in' => (int) $booking['id_status'] == HotelBookingDetail::STATUS_CHECKED_IN,
            'total' => $this->money($order->total_paid_tax_incl, $order->id_currency),
            'paid' => $this->money($order->total_paid_real, $order->id_currency),
            'balance' => $this->money($balance, $order->id_currency),
            'has_payment' => (float) $order->total_paid_real > 0.009,
        );
    }
}
