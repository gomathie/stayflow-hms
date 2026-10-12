<?php
/**
 * Salisberg Reports
 *
 * Printable reports for the hotel: pick a report and a period, see it on
 * screen, download it as a PDF.
 *
 *  - Bookings: every booking made in the period.
 *  - Arrivals and departures: rooms checking in and out in the period.
 *  - Income by day: bookings and money per day.
 *
 * One page in the back office, Bookings > Reports. Who may open it is an
 * ordinary menu permission (Administration > Permissions, row "Reports").
 *
 * To add a report: add its name to $reports and a build<Name>() method that
 * returns the same shape as the others. The screen and the PDF both render
 * that shape, so nothing else needs to change.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Salisbergreports extends Module
{
    const TAB = 'AdminSalisbergReports';
    const TAB_PARENT = 'AdminParentOrders';
    const MANAGER_PROFILE = 'Hotel Manager';
    /** Longest period one report may cover, in days */
    const MAX_DAYS = 366;

    public function __construct()
    {
        $this->name = 'salisbergreports';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salisberg Hotels';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Salisberg Reports');
        $this->description = $this->l('Booking, arrival and income reports for any period, on screen and as PDF.');
    }

    /** @return array report key => name shown to the user */
    public function getReports()
    {
        return array(
            'bookings' => $this->l('Bookings'),
            'arrivals' => $this->l('Arrivals and departures'),
            'income' => $this->l('Income by day'),
        );
    }

    /** @return array period key => name shown to the user */
    public function getPeriods()
    {
        return array(
            'today' => $this->l('Today'),
            'week' => $this->l('This week'),
            'month' => $this->l('This month'),
            'last_month' => $this->l('Last month'),
            'custom' => $this->l('Choose dates'),
        );
    }

    public function install()
    {
        return parent::install() && $this->ensureSetup();
    }

    /**
     * Creates the menu entry if it is missing. Safe to call on every deploy.
     * The Hotel Manager profile is given the page once, when the page is first
     * created, so that taking the right away later is not undone.
     */
    public function ensureSetup()
    {
        if (Tab::getIdFromClassName(self::TAB)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::TAB;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName(self::TAB_PARENT);
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Reports';
        }
        // add() also reports failure when only its permission bookkeeping fails
        // (no employee in context), so judge by whether the tab now exists
        $tab->add();
        $idTab = (int) Tab::getIdFromClassName(self::TAB);
        if (!$idTab) {
            return false;
        }

        $idProfile = (int) Db::getInstance()->getValue(
            'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang`
            WHERE `name` = \''.pSQL(self::MANAGER_PROFILE).'\' AND `id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT')
        );
        if ($idProfile) {
            Db::getInstance()->execute(
                'INSERT INTO `'._DB_PREFIX_.'access` (`id_profile`, `id_tab`, `view`, `add`, `edit`, `delete`)
                VALUES ('.$idProfile.', '.$idTab.', 1, 0, 0, 0)
                ON DUPLICATE KEY UPDATE `view` = 1'
            );
        }

        return true;
    }

    public function uninstall()
    {
        $idTab = (int) Tab::getIdFromClassName(self::TAB);
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        return parent::uninstall();
    }

    /**
     * Turns the form's choice into two dates.
     *
     * @return array array(from, to) as Y-m-d, from never after to
     */
    public function resolvePeriod($period, $from, $to)
    {
        $today = date('Y-m-d');
        switch ($period) {
            case 'week':
                $from = date('Y-m-d', strtotime('monday this week'));
                $to = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $from = date('Y-m-01');
                $to = date('Y-m-t');
                break;
            case 'last_month':
                $from = date('Y-m-01', strtotime('first day of last month'));
                $to = date('Y-m-t', strtotime('first day of last month'));
                break;
            case 'custom':
                $from = (is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && Validate::isDate($from)) ? $from : $today;
                $to = (is_string($to) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) && Validate::isDate($to)) ? $to : $from;
                break;
            default:
                $from = $to = $today;
        }
        if ($from > $to) {
            list($from, $to) = array($to, $from);
        }
        $limit = date('Y-m-d', strtotime($from.' +'.(self::MAX_DAYS - 1).' days'));
        if ($to > $limit) {
            $to = $limit;
        }

        return array($from, $to);
    }

    /**
     * Builds one report.
     *
     * @return array title, period, summary (label => value), tables (each:
     *               heading, columns (label, align, width in %), rows)
     */
    public function buildReport($type, $from, $to)
    {
        $reports = $this->getReports();
        if (!isset($reports[$type])) {
            $type = 'bookings';
        }
        $method = 'build'.Tools::ucfirst($type);
        $report = $this->{$method}($from, $to);
        $report['type'] = $type;
        $report['title'] = $reports[$type];
        $report['from'] = $from;
        $report['to'] = $to;
        $report['period'] = ($from == $to)
            ? Tools::displayDate($from)
            : sprintf($this->l('%1$s to %2$s'), Tools::displayDate($from), Tools::displayDate($to));

        return $report;
    }

    protected function money($amount)
    {
        return Tools::displayPrice((float) $amount, (int) Configuration::get('PS_CURRENCY_DEFAULT'));
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

    protected function buildBookings($from, $to)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT o.`id_order`, o.`reference`, o.`date_add`, o.`total_paid_tax_incl`, o.`total_paid_real`,
                o.`payment`, o.`current_state`, osl.`name` AS `state`,
                CONCAT(c.`firstname`, \' \', c.`lastname`) AS `guest`,
                COUNT(b.`id`) AS `rooms`, MIN(b.`date_from`) AS `stay_from`, MAX(b.`date_to`) AS `stay_to`,
                GROUP_CONCAT(DISTINCT b.`room_type_name` ORDER BY b.`room_type_name` SEPARATOR \', \') AS `room_types`
            FROM `'._DB_PREFIX_.'orders` o
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl
                ON osl.`id_order_state` = o.`current_state` AND osl.`id_lang` = '.(int) $this->context->language->id.'
            LEFT JOIN `'._DB_PREFIX_.'htl_booking_detail` b ON b.`id_order` = o.`id_order`
            WHERE o.`date_add` BETWEEN \''.pSQL($from).' 00:00:00\' AND \''.pSQL($to).' 23:59:59\'
            GROUP BY o.`id_order`
            ORDER BY o.`date_add` ASC'
        );
        $dead = $this->deadStates();
        $count = 0;
        $cancelled = 0;
        $value = 0.0;
        $paid = 0.0;
        $rooms = 0;
        $lines = array();
        foreach ((array) $rows as $row) {
            if (in_array((int) $row['current_state'], $dead)) {
                ++$cancelled;
            } else {
                ++$count;
                $value += (float) $row['total_paid_tax_incl'];
                $paid += (float) $row['total_paid_real'];
                $rooms += (int) $row['rooms'];
            }
            $lines[] = array(
                $row['reference'],
                Tools::displayDate($row['date_add']),
                $row['guest'],
                $row['room_types'].((int) $row['rooms'] > 1 ? ' ('.(int) $row['rooms'].')' : ''),
                $row['stay_from'] ? Tools::displayDate($row['stay_from']).' - '.Tools::displayDate($row['stay_to']) : '',
                $row['payment'],
                $row['state'],
                $this->money($row['total_paid_tax_incl']),
                $this->money($row['total_paid_real']),
            );
        }

        return array(
            'summary' => array(
                $this->l('Bookings') => $count,
                $this->l('Rooms booked') => $rooms,
                $this->l('Total value') => $this->money($value),
                $this->l('Paid so far') => $this->money($paid),
                $this->l('Still to pay') => $this->money(max(0, $value - $paid)),
                $this->l('Cancelled or refunded') => $cancelled,
            ),
            'note' => $this->l('Bookings made in the period. Totals leave out cancelled and refunded bookings, which are still listed.'),
            'tables' => array(array(
                'heading' => '',
                'columns' => array(
                    array($this->l('Reference'), 'left', 9),
                    array($this->l('Booked on'), 'left', 8),
                    array($this->l('Guest'), 'left', 13),
                    array($this->l('Room type'), 'left', 14),
                    array($this->l('Stay'), 'left', 15),
                    array($this->l('Payment'), 'left', 11),
                    array($this->l('Status'), 'left', 10),
                    array($this->l('Total'), 'right', 10),
                    array($this->l('Paid'), 'right', 10),
                ),
                'rows' => $lines,
            )),
        );
    }

    protected function buildArrivals($from, $to)
    {
        $tables = array();
        $counts = array();
        foreach (array('date_from' => $this->l('Arrivals'), 'date_to' => $this->l('Departures')) as $column => $heading) {
            $rows = Db::getInstance()->executeS(
                'SELECT b.`room_num`, b.`room_type_name`, b.`date_from`, b.`date_to`, b.`adults`, b.`children`, b.`id_status`,
                    o.`reference`, CONCAT(c.`firstname`, \' \', c.`lastname`) AS `guest`, c.`phone`
                FROM `'._DB_PREFIX_.'htl_booking_detail` b
                INNER JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = b.`id_order`
                LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
                WHERE b.`'.bqSQL($column).'` BETWEEN \''.pSQL($from).' 00:00:00\' AND \''.pSQL($to).' 23:59:59\'
                    AND b.`is_cancelled` = 0 AND b.`is_refunded` = 0
                ORDER BY b.`'.bqSQL($column).'` ASC, b.`room_num` ASC'
            );
            $lines = array();
            foreach ((array) $rows as $row) {
                $lines[] = array(
                    Tools::displayDate($row[$column]),
                    $row['room_num'],
                    $row['room_type_name'],
                    $row['guest'],
                    $row['phone'],
                    (int) $row['adults'].' + '.(int) $row['children'],
                    Tools::displayDate($row['date_from']).' - '.Tools::displayDate($row['date_to']),
                    $row['reference'],
                    $this->roomStatus($row['id_status']),
                );
            }
            $counts[$heading] = count($lines);
            $tables[] = array(
                'heading' => $heading,
                'columns' => array(
                    array($this->l('Date'), 'left', 9),
                    array($this->l('Room'), 'left', 8),
                    array($this->l('Room type'), 'left', 16),
                    array($this->l('Guest'), 'left', 15),
                    array($this->l('Phone'), 'left', 11),
                    array($this->l('Adults + children'), 'left', 10),
                    array($this->l('Stay'), 'left', 14),
                    array($this->l('Booking'), 'left', 8),
                    array($this->l('Room status'), 'left', 9),
                ),
                'rows' => $lines,
            );
        }

        return array(
            'summary' => $counts,
            'note' => $this->l('Rooms due to check in and to check out in the period. Cancelled and refunded rooms are left out.'),
            'tables' => $tables,
        );
    }

    protected function roomStatus($idStatus)
    {
        switch ((int) $idStatus) {
            case 2:
                return $this->l('Checked in');
            case 3:
                return $this->l('Checked out');
            default:
                return $this->l('Expected');
        }
    }

    protected function buildIncome($from, $to)
    {
        $dead = $this->deadStates();
        $rows = Db::getInstance()->executeS(
            'SELECT DATE(o.`date_add`) AS `day`, COUNT(*) AS `bookings`,
                SUM(o.`total_paid_tax_incl`) AS `value`, SUM(o.`total_paid_real`) AS `paid`
            FROM `'._DB_PREFIX_.'orders` o
            WHERE o.`date_add` BETWEEN \''.pSQL($from).' 00:00:00\' AND \''.pSQL($to).' 23:59:59\''
            .($dead ? ' AND o.`current_state` NOT IN ('.implode(',', $dead).')' : '').'
            GROUP BY DATE(o.`date_add`)
            ORDER BY `day` ASC'
        );
        $bookings = 0;
        $value = 0.0;
        $paid = 0.0;
        $lines = array();
        foreach ((array) $rows as $row) {
            $bookings += (int) $row['bookings'];
            $value += (float) $row['value'];
            $paid += (float) $row['paid'];
            $lines[] = array(
                Tools::displayDate($row['day']),
                (int) $row['bookings'],
                $this->money($row['value']),
                $this->money($row['paid']),
                $this->money(max(0, $row['value'] - $row['paid'])),
            );
        }

        return array(
            'summary' => array(
                $this->l('Bookings') => $bookings,
                $this->l('Total value') => $this->money($value),
                $this->l('Paid so far') => $this->money($paid),
                $this->l('Still to pay') => $this->money(max(0, $value - $paid)),
            ),
            'note' => $this->l('By the day each booking was made. Days with no bookings are not listed. Cancelled and refunded bookings are left out.'),
            'tables' => array(array(
                'heading' => '',
                'columns' => array(
                    array($this->l('Day'), 'left', 28),
                    array($this->l('Bookings'), 'right', 18),
                    array($this->l('Total value'), 'right', 18),
                    array($this->l('Paid'), 'right', 18),
                    array($this->l('Still to pay'), 'right', 18),
                ),
                'rows' => $lines,
            )),
        );
    }

    /**
     * Sends the report to the browser as a PDF download and ends the request.
     */
    public function downloadPdf(array $report)
    {
        $smarty = $this->context->smarty;
        $logo = _PS_IMG_DIR_.Configuration::get('PS_LOGO_INVOICE');
        $smarty->assign(array(
            'sb_report' => $report,
            'sb_shop_name' => Configuration::get('PS_SHOP_NAME'),
            'sb_logo' => (Configuration::get('PS_LOGO_INVOICE') && file_exists($logo)) ? $logo : '',
            'sb_printed' => Tools::displayDate(date('Y-m-d H:i:s'), null, true),
            'sb_printed_by' => $this->context->employee ? $this->context->employee->firstname.' '.$this->context->employee->lastname : '',
        ));
        $dir = $this->local_path.'views/templates/admin/';

        $pdf = new PDFGenerator((bool) Configuration::get('PS_PDF_USE_CACHE'), 'L');
        $pdf->setFontForLang($this->context->language->iso_code);
        $pdf->createHeader($smarty->fetch($dir.'pdf_header.tpl'));
        $pdf->createFooter($smarty->fetch($dir.'pdf_footer.tpl'));
        $pdf->createContent($smarty->fetch($dir.'pdf_content.tpl'));
        $pdf->writePage();

        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }
        $pdf->render($report['type'].'-'.$report['from'].'-to-'.$report['to'].'.pdf', 'D');
        exit;
    }
}
