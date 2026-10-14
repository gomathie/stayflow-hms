<?php
/**
 * Bookings > Today: arrivals, departures, guests in house and unpaid
 * balances, with Check in and Check out buttons. Changing anything needs the
 * Edit right on this page.
 */
class AdminSalisbergTodayController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Today');
    }

    public function initToolbar()
    {
        // no add/save buttons: the buttons are on the rows
        $this->toolbar_btn = array();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn = array();
    }

    public function postProcess()
    {
        $actions = array(
            'sb_check_in' => HotelBookingDetail::STATUS_CHECKED_IN,
            'sb_check_out' => HotelBookingDetail::STATUS_CHECKED_OUT,
        );
        foreach ($actions as $name => $status) {
            if (!Tools::isSubmit($name)) {
                continue;
            }
            if ((int) $this->tabAccess['edit'] !== 1) {
                $this->errors[] = $this->l('You do not have permission to change this.');
            } elseif ($error = $this->module->changeBookingStatus((int) Tools::getValue('id_booking'), $status)) {
                $this->errors[] = $error;
            } else {
                // back to the page with a plain address, so a refresh cannot repeat the change
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergToday').'&sb_done='.$name);
            }
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $done = (string) Tools::getValue('sb_done');
        if ($done == 'sb_check_in') {
            $this->confirmations[] = $this->l('The room is checked in.');
        } elseif ($done == 'sb_check_out') {
            $this->confirmations[] = $this->l('The room is checked out and marked for cleaning.');
        }

        $today = $this->module->getToday();
        $this->context->smarty->assign(array(
            'sb_today' => $today,
            'sb_date' => Tools::displayDate(date('Y-m-d')),
            'sb_can_edit' => (int) $this->tabAccess['edit'] === 1,
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergToday'),
            'sb_booking_link' => $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order=',
            'sb_housekeeping_link' => $this->context->link->getAdminLink('AdminSalisbergHousekeeping'),
            'sb_book_link' => $this->context->link->getAdminLink('AdminSalisbergRoomBoard'),
            'sb_expected' => HotelBookingDetail::STATUS_ALLOTED,
            'sb_in' => HotelBookingDetail::STATUS_CHECKED_IN,
            'sb_css' => $this->module->getPathUri().'views/css/frontdesk.css',
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/today.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
