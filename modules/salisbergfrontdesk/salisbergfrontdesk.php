<?php
/**
 * Salisberg Front Desk
 *
 * Front-desk pages for the people at the desk and in housekeeping:
 *
 *  - Bookings > Room board: stay-wide availability and guided walk-ins.
 *  - Bookings > Today: today's arrivals, departures, guests in house and
 *    unpaid balances, with Check in and Check out buttons.
 *  - Bookings > Housekeeping: every room with its state (clean, dirty, out of
 *    service) and buttons to change it.
 *
 * A room is marked dirty when its guest is checked out, from either page or
 * from the booking itself (hook actionRoomBookingStatusUpdateAfter).
 *
 * "Out of service" is not stored here: it is the platform's own room status
 * (Inactive), the one the Rooms tab of a room type sets, so a room taken out
 * of service on either screen stops being offered to guests. This module only
 * stores clean/dirty, in sb_room_status.
 *
 * It also tells the guest what has happened:
 *  - at check-in: an email with the invoice or receipt attached, and a text;
 *  - when the booking is paid in full: a text (the platform already emails
 *    the receipt for that).
 * Texts go out through the hook actionSalisbergSendSms. Nothing listens to it
 * until an SMS provider's module is installed, so until then no text is sent.
 *
 * Who may open the pages is an ordinary menu permission (Administration >
 * Permissions, rows "Today" and "Housekeeping"): View to see, Edit to change.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__).'/classes/SalisbergWalkIn.php';

class Salisbergfrontdesk extends Module
{
    const TAB_ROOM_BOARD = 'AdminSalisbergRoomBoard';
    const TAB_WALK_IN = 'AdminSalisbergWalkIn';
    const TAB_TODAY = 'AdminSalisbergToday';
    const TAB_HOUSEKEEPING = 'AdminSalisbergHousekeeping';
    const TAB_PARENT = 'AdminParentOrders';

    const ROOM_CLEAN = 1;
    const ROOM_DIRTY = 2;

    /** How far back an unpaid stay is still listed, in days */
    const UNPAID_DAYS = 30;

    /** Profiles given both pages once, when the pages are first created */
    protected static $profiles = array('Hotel Staff', 'Hotel Manager');

    public function __construct()
    {
        $this->name = 'salisbergfrontdesk';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Salisberg Hotels';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Salisberg Front Desk');
        $this->description = $this->l('Room availability, guided walk-in booking, today at the desk and housekeeping status.');
    }

    public function install()
    {
        return parent::install() && $this->ensureSetup();
    }

    /**
     * Creates the table, the hook and the two menu entries if any is missing.
     * Safe to call on every deploy.
     */
    public function ensureSetup()
    {
        $ok = Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sb_room_status` (
                `id_room` INT UNSIGNED NOT NULL,
                `status` TINYINT UNSIGNED NOT NULL DEFAULT '.(int) self::ROOM_CLEAN.',
                `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_room`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'
        );
        $ok = SalisbergWalkIn::ensureTable() && $ok;
        foreach (array('actionRoomBookingStatusUpdateAfter', 'actionOrderStatusPostUpdate') as $hook) {
            if (!$this->isRegisteredInHook($hook)) {
                $ok = $this->registerHook($hook) && $ok;
            }
        }

        return $ok
            && $this->installTab(self::TAB_ROOM_BOARD, 'Room board', true)
            && $this->installTab(self::TAB_TODAY, 'Today', false)
            && $this->installTab(self::TAB_HOUSEKEEPING, 'Housekeeping', false)
            && $this->installTab(self::TAB_WALK_IN, 'Walk-in booking', false, true);
    }

    /**
     * The listed profiles are given the page once, when it is first created,
     * so that a right taken away later in Permissions is not given back.
     */
    protected function installTab($className, $name, $first, $hidden = false)
    {
        if (Tab::getIdFromClassName($className)) {
            return true;
        }
        $idParent = (int) Tab::getIdFromClassName(self::TAB_PARENT);
        $tab = new Tab();
        $tab->class_name = $className;
        $tab->module = $this->name;
        $tab->id_parent = $hidden ? -1 : $idParent;
        // Hidden workflow pages stay active so their controllers can be
        // opened directly; id_parent = -1 alone keeps them out of the menu.
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = $name;
        }
        // add() also reports failure when only its permission bookkeeping fails
        // (no employee in context), so judge by whether the tab now exists
        $tab->add();
        $idTab = (int) Tab::getIdFromClassName($className);
        if (!$idTab) {
            return false;
        }
        if ($first && !$hidden) {
            // the desk opens this page first, so it leads its menu section
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'tab` SET `position` = `position` + 1
                WHERE `id_parent` = '.$idParent.' AND `id_tab` != '.$idTab
            );
            Db::getInstance()->update('tab', array('position' => 0), 'id_tab = '.$idTab);
        }

        foreach (self::$profiles as $profileName) {
            $idProfile = (int) Db::getInstance()->getValue(
                'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang`
                WHERE `name` = \''.pSQL($profileName).'\' AND `id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT')
            );
            if ($idProfile) {
                Db::getInstance()->execute(
                    'INSERT INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`)
                    VALUES ('.$idProfile.', '.$idTab.', 1, 0, 1, 0)
                    ON DUPLICATE KEY UPDATE `view` = 1, `edit` = 1'
                );
            }
        }

        return true;
    }

    public function uninstall()
    {
        foreach (array(self::TAB_ROOM_BOARD, self::TAB_WALK_IN, self::TAB_TODAY, self::TAB_HOUSEKEEPING) as $className) {
            $idTab = (int) Tab::getIdFromClassName($className);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return parent::uninstall();
    }

    public function getWalkInService()
    {
        return new SalisbergWalkIn($this);
    }

    /** A room whose guest has just been checked out needs cleaning */
    public function hookActionRoomBookingStatusUpdateAfter($params)
    {
        if (empty($params['id_hotel_booking_detail'])) {
            return;
        }
        $booking = new HotelBookingDetail((int) $params['id_hotel_booking_detail']);
        if (!Validate::isLoadedObject($booking)) {
            return;
        }
        if ((int) $booking->id_status == HotelBookingDetail::STATUS_CHECKED_OUT) {
            $this->saveRoomStatus((int) $booking->id_room, self::ROOM_DIRTY);
        } elseif ((int) $booking->id_status == HotelBookingDetail::STATUS_CHECKED_IN) {
            $this->notifyCheckIn($booking);
        }
    }

    /** A booking that has just become paid in full: text the guest */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (empty($params['newOrderStatus']) || empty($params['id_order']) || !$params['newOrderStatus']->paid) {
            return;
        }
        $order = new Order((int) $params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        $customer = new Customer((int) $order->id_customer);
        $this->sendSms($customer->phone, sprintf(
            $this->l('%1$s: we have received your payment of %2$s for booking %3$s. Thank you.'),
            Configuration::get('PS_SHOP_NAME'),
            Tools::displayPrice((float) $order->total_paid_real, (int) $order->id_currency),
            $order->reference
        ), $order);
    }

    /**
     * The first room of a booking to be checked in: email the guest with the
     * invoice (a receipt once it is paid) attached, and text them. Later rooms
     * of the same booking say nothing more.
     */
    protected function notifyCheckIn(HotelBookingDetail $booking)
    {
        $arrived = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_booking_detail`
            WHERE `id_order` = '.(int) $booking->id_order.' AND `id` != '.(int) $booking->id.'
            AND `id_status` IN ('.(int) HotelBookingDetail::STATUS_CHECKED_IN.', '.(int) HotelBookingDetail::STATUS_CHECKED_OUT.')'
        );
        $order = new Order((int) $booking->id_order);
        if ($arrived || !Validate::isLoadedObject($order)) {
            return;
        }
        $customer = new Customer((int) $order->id_customer);
        $shopName = Configuration::get('PS_SHOP_NAME');
        $checkOut = date('j M Y', strtotime($booking->date_to));
        $balance = max(0, (float) $order->total_paid_tax_incl - (float) $order->total_paid_real);

        $this->sendSms($customer->phone, sprintf(
            $this->l('Welcome to %1$s, %2$s. You are checked in to room %3$s until %4$s. Booking %5$s.'),
            $shopName,
            $customer->firstname,
            $booking->room_num,
            $checkOut,
            $order->reference
        ), $order);

        if (!Validate::isEmail($customer->email) || Customer::hasNoEmail($customer->email)) {
            return;
        }
        try {
            $attachment = null;
            if ((int) Configuration::get('PS_INVOICE') && $order->invoice_number) {
                $invoices = $order->getInvoicesCollection();
                Hook::exec('actionPDFInvoiceRender', array('order_invoice_list' => $invoices));
                $pdf = new PDF($invoices, PDF::TEMPLATE_INVOICE, $this->context->smarty);
                $attachment = array(
                    'content' => $pdf->render(false),
                    'name' => Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop).sprintf('%06d', $order->invoice_number).'.pdf',
                    'mime' => 'application/pdf',
                );
            }
            Mail::Send(
                (int) $order->id_lang,
                'checkin',
                Mail::l('You are checked in', (int) $order->id_lang),
                array(
                    '{firstname}' => $customer->firstname,
                    '{lastname}' => $customer->lastname,
                    '{order_name}' => $order->reference,
                    '{room}' => $booking->room_num.' ('.$booking->room_type_name.')',
                    '{check_out}' => $checkOut,
                    '{balance_line}' => ($balance > 0.009)
                        ? sprintf($this->l('Still to pay: %s. You can settle it at reception.'), Tools::displayPrice($balance, (int) $order->id_currency))
                        : $this->l('Your booking is paid in full. Your receipt is attached.'),
                ),
                $customer->email,
                $customer->firstname.' '.$customer->lastname,
                null,
                null,
                $attachment,
                null,
                $this->local_path.'mails/',
                false,
                (int) $order->id_shop
            );
        } catch (Exception $e) {
            // a mail problem must never stop a guest being checked in
            PrestaShopLogger::addLog('salisbergfrontdesk: check-in email for '.$order->reference.' not sent: '.$e->getMessage(), 3);
        }
    }

    /**
     * Hands a text message to whichever SMS provider is installed. With none
     * installed the hook has no listener and nothing happens.
     */
    protected function sendSms($phone, $message, Order $order)
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return;
        }
        try {
            Hook::exec('actionSalisbergSendSms', array(
                'phone' => $phone,
                'message' => $message,
                'id_order' => (int) $order->id,
                'reference' => $order->reference,
            ));
        } catch (Exception $e) {
            PrestaShopLogger::addLog('salisbergfrontdesk: text for '.$order->reference.' not sent: '.$e->getMessage(), 3);
        }
    }

    /** @return int[] hotels the signed-in employee's profile may see */
    public function getHotels()
    {
        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)) {
            return array();
        }

        return array_map('intval', (array) HotelBranchInformation::getProfileAccessedHotels($employee->id_profile, 1, 1));
    }

    /** Order states that mean the booking did not go ahead */
    protected function deadStates()
    {
        return array_filter(array_map('intval', array(
            Configuration::get('PS_OS_CANCELED'),
            Configuration::get('PS_OS_REFUND'),
            Configuration::get('PS_OS_ERROR'),
        )));
    }

    protected function money($amount)
    {
        return Tools::displayPrice((float) $amount, (int) Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /** Rooms of live bookings matching $where, with guest, balance and housekeeping state */
    protected function getBookings($where, $orderBy)
    {
        $hotels = $this->getHotels();
        if (!$hotels) {
            return array();
        }
        $dead = $this->deadStates();
        $rows = Db::getInstance()->executeS(
            'SELECT b.`id`, b.`id_order`, b.`id_room`, b.`room_num`, b.`room_type_name`, b.`date_from`, b.`date_to`,
                b.`adults`, b.`children`, b.`id_status`, b.`check_in`, b.`check_out`,
                o.`reference`, o.`total_paid_tax_incl`, o.`total_paid_real`,
                CONCAT(c.`firstname`, \' \', c.`lastname`) AS `guest`, c.`phone`,
                r.`id_status` AS `room_state`, IFNULL(rs.`status`, '.(int) self::ROOM_CLEAN.') AS `housekeeping`
            FROM `'._DB_PREFIX_.'htl_booking_detail` b
            INNER JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = b.`id_order`
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
            LEFT JOIN `'._DB_PREFIX_.'htl_room_information` r ON r.`id` = b.`id_room`
            LEFT JOIN `'._DB_PREFIX_.'sb_room_status` rs ON rs.`id_room` = b.`id_room`
            WHERE b.`is_cancelled` = 0 AND b.`is_refunded` = 0
                AND b.`id_hotel` IN ('.implode(',', $hotels).')'
                .($dead ? ' AND o.`current_state` NOT IN ('.implode(',', $dead).')' : '').'
                AND ('.$where.')
            ORDER BY '.$orderBy
        );
        $today = date('Y-m-d');
        $rows = (array) $rows;
        foreach ($rows as &$row) {
            $row['balance'] = max(0, (float) $row['total_paid_tax_incl'] - (float) $row['total_paid_real']);
            $row['balance_text'] = $row['balance'] > 0.009 ? $this->money($row['balance']) : '';
            $row['stay'] = Tools::displayDate($row['date_from']).' - '.Tools::displayDate($row['date_to']);
            $row['guests'] = (int) $row['adults'].' + '.(int) $row['children'];
            $row['late'] = substr($row['date_from'], 0, 10) < $today && (int) $row['id_status'] == HotelBookingDetail::STATUS_ALLOTED;
            $row['overdue'] = substr($row['date_to'], 0, 10) < $today && (int) $row['id_status'] == HotelBookingDetail::STATUS_CHECKED_IN;
            $row['leaves_today'] = substr($row['date_to'], 0, 10) == $today;
            $row['room_label'] = $this->roomLabel($row['room_state'], $row['housekeeping']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Everything the Today page shows.
     *
     * @return array arrivals, departures, in_house (rooms) and unpaid (bookings)
     */
    public function getToday()
    {
        $today = pSQL(date('Y-m-d'));
        $expected = (int) HotelBookingDetail::STATUS_ALLOTED;
        $in = (int) HotelBookingDetail::STATUS_CHECKED_IN;
        $out = (int) HotelBookingDetail::STATUS_CHECKED_OUT;

        return array(
            // due today or earlier and not yet arrived, plus those who arrived today
            'arrivals' => $this->getBookings(
                '(b.`id_status` = '.$expected.' AND DATE(b.`date_from`) <= \''.$today.'\' AND DATE(b.`date_to`) >= \''.$today.'\')
                OR (b.`id_status` = '.$in.' AND DATE(b.`check_in`) = \''.$today.'\')',
                'b.`id_status` ASC, b.`date_from` ASC, b.`room_num` ASC'
            ),
            // in the room and due out today or earlier, plus those who left today
            'departures' => $this->getBookings(
                '(b.`id_status` = '.$in.' AND DATE(b.`date_to`) <= \''.$today.'\')
                OR (b.`id_status` = '.$out.' AND DATE(b.`check_out`) = \''.$today.'\')',
                'b.`id_status` ASC, b.`date_to` ASC, b.`room_num` ASC'
            ),
            'in_house' => $this->getBookings('b.`id_status` = '.$in, 'b.`room_num` ASC'),
            'unpaid' => $this->getUnpaid(),
        );
    }

    /** Bookings with money still to pay whose stay is current, coming, or ended recently */
    protected function getUnpaid()
    {
        $hotels = $this->getHotels();
        if (!$hotels) {
            return array();
        }
        $dead = $this->deadStates();
        $since = pSQL(date('Y-m-d', strtotime('-'.(int) self::UNPAID_DAYS.' days')));
        $rows = Db::getInstance()->executeS(
            'SELECT o.`id_order`, o.`reference`, o.`payment`, o.`total_paid_tax_incl`, o.`total_paid_real`,
                CONCAT(c.`firstname`, \' \', c.`lastname`) AS `guest`, c.`phone`,
                COUNT(b.`id`) AS `rooms`, MIN(b.`date_from`) AS `stay_from`, MAX(b.`date_to`) AS `stay_to`,
                GROUP_CONCAT(b.`room_num` ORDER BY b.`room_num` SEPARATOR \', \') AS `room_nums`
            FROM `'._DB_PREFIX_.'orders` o
            INNER JOIN `'._DB_PREFIX_.'htl_booking_detail` b ON b.`id_order` = o.`id_order`
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
            WHERE b.`is_cancelled` = 0 AND b.`is_refunded` = 0
                AND b.`id_hotel` IN ('.implode(',', $hotels).')
                AND (o.`total_paid_tax_incl` - o.`total_paid_real`) > 0.009'
                .($dead ? ' AND o.`current_state` NOT IN ('.implode(',', $dead).')' : '').'
            GROUP BY o.`id_order`
            HAVING `stay_to` >= \''.$since.'\'
            ORDER BY `stay_from` ASC
            LIMIT 200'
        );
        $rows = (array) $rows;
        foreach ($rows as &$row) {
            $row['total_text'] = $this->money($row['total_paid_tax_incl']);
            $row['paid_text'] = $this->money($row['total_paid_real']);
            $row['balance_text'] = $this->money((float) $row['total_paid_tax_incl'] - (float) $row['total_paid_real']);
            $row['stay'] = Tools::displayDate($row['stay_from']).' - '.Tools::displayDate($row['stay_to']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Checks a room in or out, with the rules the booking page applies:
     * the time recorded lies within the stay, and a room is checked in before
     * it is checked out.
     *
     * @return string an error to show, or '' when the change was saved
     */
    public function changeBookingStatus($idBooking, $newStatus)
    {
        $booking = new HotelBookingDetail((int) $idBooking);
        if (!Validate::isLoadedObject($booking) || !in_array((int) $booking->id_hotel, $this->getHotels())) {
            return $this->l('This booking could not be found.');
        }
        if ($booking->is_cancelled || $booking->is_refunded) {
            return $this->l('This room was cancelled or refunded.');
        }
        $stayFrom = strtotime(date('Y-m-d', strtotime($booking->date_from)));
        $stayTo = strtotime(date('Y-m-d', strtotime($booking->date_to)).' 23:59:59');
        $now = time();
        if ($now < $stayFrom) {
            return $this->l('This stay has not started yet.');
        }
        // a guest leaving late is recorded at the end of the stay they booked
        $when = date('Y-m-d H:i:s', min($now, $stayTo));

        if ($newStatus == HotelBookingDetail::STATUS_CHECKED_IN) {
            if ((int) $booking->id_status != HotelBookingDetail::STATUS_ALLOTED) {
                return $this->l('This room is already checked in.');
            }
            $booking->check_in = $when;
        } elseif ($newStatus == HotelBookingDetail::STATUS_CHECKED_OUT) {
            if ((int) $booking->id_status != HotelBookingDetail::STATUS_CHECKED_IN) {
                return $this->l('Check the room in before checking it out.');
            }
            if (!Validate::isDate($booking->check_in) || strtotime($booking->check_in) <= 0) {
                // an arrival time was never recorded: the stay began on its first day
                $booking->check_in = $booking->date_from;
            }
            if (strtotime($booking->check_in) > strtotime($when)) {
                $when = $booking->check_in;
            }
            $booking->check_out = $when;
        } else {
            return $this->l('This booking could not be found.');
        }
        $booking->id_status = (int) $newStatus;
        try {
            $saved = $booking->save();
        } catch (Exception $e) {
            // a stored value the platform no longer accepts: say so, never an error page
            PrestaShopLogger::addLog('salisbergfrontdesk: booking room '.(int) $booking->id.' not saved: '.$e->getMessage(), 3);
            $saved = false;
        }
        if (!$saved) {
            return $this->l('The change could not be saved. Open the booking and change the room status there.');
        }
        // the same hook the booking page fires, so everything that follows a
        // status change (a dirty room after check-out) happens here too
        Hook::exec('actionRoomBookingStatusUpdateAfter', array(
            'id_hotel_booking_detail' => $booking->id,
            'id_order' => $booking->id_order,
            'id_room' => $booking->id_room,
            'date_from' => $booking->date_from,
            'date_to' => $booking->date_to,
        ));

        return '';
    }

    /** The one word shown for a room: out of service wins over clean/dirty */
    protected function roomLabel($roomState, $housekeeping)
    {
        if ((int) $roomState == HotelRoomInformation::STATUS_INACTIVE
            || (int) $roomState == HotelRoomInformation::STATUS_TEMPORARY_INACTIVE
        ) {
            return 'out';
        }

        return ((int) $housekeeping == self::ROOM_DIRTY) ? 'dirty' : 'clean';
    }

    /**
     * Every room of the hotels the employee may see.
     *
     * @return array rooms, each with: state (clean, dirty or out), who is in it
     *               now, who arrives today, when and by whom it was last changed
     */
    public function getRooms()
    {
        $hotels = $this->getHotels();
        if (!$hotels) {
            return array();
        }
        $today = pSQL(date('Y-m-d'));
        $live = 'b.`is_cancelled` = 0 AND b.`is_refunded` = 0';
        $rows = Db::getInstance()->executeS(
            'SELECT r.`id` AS `id_room`, r.`room_num`, r.`floor`, r.`id_status` AS `room_state`,
                pl.`name` AS `room_type`, IFNULL(rs.`status`, '.(int) self::ROOM_CLEAN.') AS `housekeeping`, rs.`date_upd`,
                CONCAT(e.`firstname`, \' \', e.`lastname`) AS `changed_by`,
                (SELECT CONCAT(c.`firstname`, \' \', c.`lastname`) FROM `'._DB_PREFIX_.'htl_booking_detail` b
                    LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = b.`id_customer`
                    WHERE b.`id_room` = r.`id` AND '.$live.' AND b.`id_status` = '.(int) HotelBookingDetail::STATUS_CHECKED_IN.'
                    ORDER BY b.`id` DESC LIMIT 1) AS `guest_in`,
                (SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_booking_detail` b
                    WHERE b.`id_room` = r.`id` AND '.$live.' AND b.`id_status` = '.(int) HotelBookingDetail::STATUS_ALLOTED.'
                    AND DATE(b.`date_from`) = \''.$today.'\') AS `arriving`,
                (SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_booking_detail` b
                    WHERE b.`id_room` = r.`id` AND '.$live.' AND b.`id_status` = '.(int) HotelBookingDetail::STATUS_ALLOTED.'
                    AND DATE(b.`date_to`) >= \''.$today.'\') AS `coming`
            FROM `'._DB_PREFIX_.'htl_room_information` r
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
                ON pl.`id_product` = r.`id_product` AND pl.`id_lang` = '.(int) $this->context->language->id.'
                AND pl.`id_shop` = '.(int) $this->context->shop->id.'
            LEFT JOIN `'._DB_PREFIX_.'sb_room_status` rs ON rs.`id_room` = r.`id`
            LEFT JOIN `'._DB_PREFIX_.'employee` e ON e.`id_employee` = rs.`id_employee`
            WHERE r.`id_hotel` IN ('.implode(',', $hotels).')
            ORDER BY pl.`name` ASC, r.`room_num` ASC'
        );
        $rows = (array) $rows;
        foreach ($rows as &$row) {
            $row['state'] = $this->roomLabel($row['room_state'], $row['housekeeping']);
            // dates for a room closed for a period are set in its room type
            $row['fixed'] = (int) $row['room_state'] == HotelRoomInformation::STATUS_TEMPORARY_INACTIVE;
            $row['changed'] = $row['date_upd'] ? Tools::displayDate($row['date_upd'], null, true) : '';
        }
        unset($row);

        return $rows;
    }

    protected function saveRoomStatus($idRoom, $status, $idEmployee = null)
    {
        if ($idEmployee === null) {
            $idEmployee = Validate::isLoadedObject($this->context->employee) ? (int) $this->context->employee->id : 0;
        }

        return Db::getInstance()->execute(
            'INSERT INTO `'._DB_PREFIX_.'sb_room_status` (`id_room`, `status`, `id_employee`, `date_upd`)
            VALUES ('.(int) $idRoom.', '.(int) $status.', '.(int) $idEmployee.', \''.pSQL(date('Y-m-d H:i:s')).'\')
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `id_employee` = VALUES(`id_employee`), `date_upd` = VALUES(`date_upd`)'
        );
    }

    /**
     * Sets a room clean, dirty, out of service or back in service.
     *
     * @param string $state clean, dirty, out or back
     *
     * @return string an error to show, or '' when the change was saved
     */
    public function setRoomState($idRoom, $state)
    {
        $room = new HotelRoomInformation((int) $idRoom);
        if (!Validate::isLoadedObject($room) || !in_array((int) $room->id_hotel, $this->getHotels())) {
            return $this->l('This room could not be found.');
        }
        if ($state == 'clean' || $state == 'dirty') {
            return $this->saveRoomStatus($room->id, $state == 'dirty' ? self::ROOM_DIRTY : self::ROOM_CLEAN)
                ? '' : $this->l('The change could not be saved. Please try again.');
        }
        if ((int) $room->id_status == HotelRoomInformation::STATUS_TEMPORARY_INACTIVE) {
            return $this->l('This room is closed for set dates. Change that in its room type, under the Rooms tab.');
        }
        if ($state == 'out') {
            $inHouse = (int) Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_booking_detail`
                WHERE `id_room` = '.(int) $room->id.' AND `is_cancelled` = 0 AND `is_refunded` = 0
                AND `id_status` = '.(int) HotelBookingDetail::STATUS_CHECKED_IN
            );
            if ($inHouse) {
                return $this->l('A guest is checked in to this room. Check them out or move them first.');
            }
            $room->id_status = HotelRoomInformation::STATUS_INACTIVE;
        } elseif ($state == 'back') {
            $room->id_status = HotelRoomInformation::STATUS_ACTIVE;
        } else {
            return $this->l('This room could not be found.');
        }

        return $room->save() ? '' : $this->l('The change could not be saved. Please try again.');
    }
}
