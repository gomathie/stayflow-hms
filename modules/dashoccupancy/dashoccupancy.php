<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class DashOccupancy extends Module
{
    public function __construct()
    {
        $this->name = 'dashoccupancy';
        $this->tab = 'dashboard';
        $this->version = '1.0.3';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');
        $this->author = 'Webkul';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Dashboard Occupancy');
        $this->description = $this->l('Adds a block with a graphical representation of occupancy of your hotel`s room.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->allow_push = true;
    }

    public function install()
    {
        return (parent::install()
            && $this->registerHook('dashboardZoneOne')
            && $this->registerHook('dashboardData')
            && $this->registerHook('actionAdminControllerSetMedia')
        );
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (get_class($this->context->controller) == 'AdminDashboardController') {
            $this->context->controller->addJs($this->_path.'views/js/'.$this->name.'.js');
            $this->context->controller->addCSS($this->_path.'views/css/'.$this->name.'.css');
        }
    }

    public function hookDashboardZoneOne()
    {
        return $this->display(__FILE__, 'dashboard_zone_one.tpl');
    }

    /**
     * Occupancy, as the owner approved on 2026-11-19 (SIMPLIFY-REVIEW):
     *  - Tonight: rooms with a guest checked in now (the same rule as Today at
     *    the desk), rooms out of service, and the rest free, as one bar;
     *  - Occupancy rate for the chosen period: booked room-nights divided by
     *    available room-nights, the usual hotel measure.
     * The old figure ("rooms with any booking in the period") is gone: over a
     * month it was close to 100% and read like today's occupancy.
     */
    public function hookDashboardData($params)
    {
        $hotels = $this->hotelsInScope(isset($params['id_hotel']) ? (int) $params['id_hotel'] : 0);
        $from = Validate::isDate($params['date_from']) ? substr($params['date_from'], 0, 10) : date('Y-m-d');
        $to = Validate::isDate($params['date_to']) ? substr($params['date_to'], 0, 10) : $from;
        if ($to < $from) {
            list($from, $to) = array($to, $from);
        }
        $nights = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;
        $end = date('Y-m-d', strtotime($to.' +1 day'));

        if (Configuration::get('PS_DASHBOARD_SIMULATION')) {
            $total = rand(20, 60);
            $inHouse = rand(0, $total);
            $blocked = rand(0, (int) ($total / 10));
            $bookedNights = rand(0, $total * $nights);
        } elseif (!$hotels) {
            $total = $inHouse = $blocked = $bookedNights = 0;
        } else {
            $in = implode(',', array_map('intval', $hotels));
            $db = Db::getInstance();
            $total = (int) $db->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_room_information` WHERE `id_hotel` IN ('.$in.')', false);
            $blocked = (int) $db->getValue(
                'SELECT COUNT(*) FROM `'._DB_PREFIX_.'htl_room_information` WHERE `id_hotel` IN ('.$in.')
                AND `id_status` != '.(int) HotelRoomInformation::STATUS_ACTIVE,
                false
            );
            $inHouse = (int) $db->getValue(
                'SELECT COUNT(DISTINCT `id_room`) FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_hotel` IN ('.$in.')
                AND `id_status` = '.(int) HotelBookingDetail::STATUS_CHECKED_IN.' AND `is_refunded` = 0 AND `is_cancelled` = 0',
                false
            );
            // nights of each stay that fall inside [from, to]
            $bookedNights = (int) $db->getValue(
                'SELECT COALESCE(SUM(DATEDIFF(LEAST(DATE(`date_to`), \''.pSQL($end).'\'), GREATEST(DATE(`date_from`), \''.pSQL($from).'\'))), 0)
                FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_hotel` IN ('.$in.')
                AND `is_refunded` = 0 AND `is_cancelled` = 0
                AND DATE(`date_from`) < \''.pSQL($end).'\' AND DATE(`date_to`) > \''.pSQL($from).'\'',
                false
            );
        }
        $inHouse = min($inHouse, $total);
        $blocked = min($blocked, $total - $inHouse);
        $free = max(0, $total - $inHouse - $blocked);
        $availableNights = max(0, ($total - $blocked) * $nights);
        $rate = $availableNights ? min(100, round($bookedNights / $availableNights * 100)) : 0;
        $pct = function ($n) use ($total) {
            return $total ? (int) round($n / $total * 100) : 0;
        };

        return array(
            'data_value' => array(
                'do_tonight_in' => $inHouse,
                'do_tonight_total' => $total,
                'do_tonight_pct' => $pct($inHouse).'%',
                'do_rate_pct' => $rate.'%',
                'do_rate_detail' => sprintf($this->l('%1$s of %2$s room-nights'), number_format($bookedNights), number_format($availableNights)),
                'do_rate_period' => $from == $to ? date('j M', strtotime($from)) : date('j M', strtotime($from)).' – '.date('j M', strtotime($to)),
            ),
            'data_chart' => array(
                'dash_occupancy_chart1' => array(
                    'chart_type' => 'sb_occupancy_bar',
                    'data' => array(
                        array('key' => 'booked', 'label' => $this->l('In house'), 'value' => $inHouse, 'percent' => $pct($inHouse)),
                        array('key' => 'free', 'label' => $this->l('Free'), 'value' => $free, 'percent' => $pct($free)),
                        array('key' => 'blocked', 'label' => $this->l('Out of service'), 'value' => $blocked, 'percent' => $pct($blocked)),
                    ),
                ),
            ),
        );
    }

    /** Hotels this employee may see, limited to one when the Dashboard filter picks it */
    protected function hotelsInScope($idHotel)
    {
        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)) {
            return array();
        }
        $ids = array_map('intval', (array) HotelBranchInformation::getProfileAccessedHotels((int) $employee->id_profile, 1, 1));
        if ($idHotel) {
            $ids = in_array($idHotel, $ids, true) ? array($idHotel) : array();
        }

        return $ids;
    }
}
