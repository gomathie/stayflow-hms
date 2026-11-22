<?php
/**
 * Hidden action controller for the Today workspace embedded on Dashboard.
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
                // Return to where the button was, so a refresh cannot repeat the change.
                if ((int) Tools::getValue('id_order_back')) {
                    Tools::redirectAdmin(
                        $this->context->link->getAdminLink('AdminOrders').'&id_order='.(int) Tools::getValue('id_order_back').'&vieworder&conf=4'
                    );
                }
                $anchor = $name == 'sb_check_in' ? '#sb-arrivals' : '#sb-departures';
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard').'&sb_done='.$name.$anchor);
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

        $this->module->assignTodayTemplate((int) $this->tabAccess['edit'] === 1);
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/today.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
