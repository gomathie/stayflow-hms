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

class DashAvailability extends Module
{
    public function __construct()
    {
        $this->name = 'dashavailability';
        $this->tab = 'dashboard';
        $this->version = '1.0.3';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6');
        $this->author = 'Webkul';
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Dashboard Availability');
        $this->description = $this->l('Adds a block with a graphical representation of availability of your hotel`s room.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->allow_push = true;
    }

    public function install()
    {
        return (parent::install()
            && $this->registerHook('dashboardZoneTwo')
            && $this->registerHook('dashboardData')
            && $this->registerHook('actionAdminControllerSetMedia')
        );
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (get_class($this->context->controller) == 'AdminDashboardController') {
            Media::addJsDef(array(
                'avail_rooms_txt' => $this->l('Rooms free'),
            ));
            $this->context->controller->addJs($this->_path.'views/js/'.$this->name.'.js');
            $this->context->controller->addCSS($this->_path.'views/css/'.$this->name.'.css');
        }
    }

    public function hookDashboardZoneTwo($params)
    {
        Media::addJsDef(array(
            'dashAvailAajaxUrl' => $this->context->link->getModuleLink($this->name, 'chartdata'),
        ));

        $this->context->smarty->assign(array(
            'dateFromBar' => date('Y-m-d'),
            'dateFromLabel' => date('j M Y'),
        ));

        return $this->display(__FILE__, 'dashboard_zone_two.tpl');
    }

    public function hookDashboardData($params)
    {
        // Period: 7, 14 or 30 nights from a valid date; anything else falls back to 7 from today.
        $dateFrom = date('Y-m-d');
        $days = 7;
        if (isset($params['extra']) && $params['extra'] != 'undefined') {
            $extra = json_decode($params['extra']);
            if (isset($extra->date_from) && Validate::isDate($extra->date_from)) {
                $dateFrom = $extra->date_from;
            }
            if (isset($extra->days) && in_array((int) $extra->days, array(7, 14, 30))) {
                $days = (int) $extra->days;
            }
        }

        if (Configuration::get('PS_DASHBOARD_SIMULATION')) {
            $availability_data = array('values' => array());
            $from = strtotime($dateFrom.' 00:00:00');
            for ($i = 0; $i < $days; $i++) {
                $availability_data['values'][] = array(strtotime('+'.$i.' days', $from), rand(0, 20));
            }
        } else {
            $availability_data = AdminStatsController::getAvailabilityLineChartData($days - 1, $dateFrom, $params['id_hotel']);
        }
        if (empty($availability_data['values'])) {
            $availability_data['values'] = array();
        }

        // Summary: the tightest night and the average, so staff need not read the chart.
        $low = null;
        $sum = 0;
        foreach ($availability_data['values'] as &$point) {
            $point[1] = (int) $point[1];
            $sum += $point[1];
            if ($low === null || $point[1] < $low[1]) {
                $low = $point;
            }
        }
        unset($point);
        $count = count($availability_data['values']);
        if ($low === null) {
            $lowText = $this->l('No data');
            $avgText = '-';
        } else {
            $lowText = sprintf(
                $low[1] == 1 ? $this->l('%1$d room free on %2$s') : $this->l('%1$d rooms free on %2$s'),
                $low[1],
                date('D j M', $low[0])
            );
            $avgText = sprintf($this->l('%s rooms a night'), number_format($sum / $count, 1));
        }

        $availability_data = array_merge(
            $availability_data,
            array(
                'id' => 'availabilities',
                'key' => $this->l('Rooms free'),
                'area' => true,
            )
        );

        return array(
            'data_chart' => array('availability_line_chart1' => array(
                'chart_type' => 'line_chart_availability',
                'date_format' => $this->context->language->date_format_lite,
                'data' => array($availability_data),
            )),
            'data_value' => array(
                'sb_avail_low' => Tools::safeOutput($lowText),
                'sb_avail_avg' => Tools::safeOutput($avgText),
            ),
        );
    }
}
