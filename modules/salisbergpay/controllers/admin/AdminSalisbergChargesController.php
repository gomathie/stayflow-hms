<?php
/**
 * Rooms & Services > Charges & Tax: switch on or off the charges added to
 * every booking by themselves, and whether tax is charged at all. Changing
 * anything needs the Edit right on this page. Bookings already made keep
 * what they were charged.
 */
class AdminSalisbergChargesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Charges & Tax');
    }

    public function initToolbar()
    {
        $this->toolbar_btn = array();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn = array();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('sb_charge') || Tools::isSubmit('sb_tax')) {
            $on = (int) Tools::getValue('sb_on') ? 1 : 0;
            if ((int) $this->tabAccess['edit'] !== 1) {
                $this->errors[] = $this->l('You do not have permission to change this.');
            } elseif (Tools::isSubmit('sb_tax')) {
                Configuration::updateValue('PS_TAX', $on);
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergCharges').'&sb_done='.($on ? 'tax_on' : 'tax_off'));
            } elseif (!$this->module->setChargeAutoAdded((int) Tools::getValue('sb_charge'), $on)) {
                $this->errors[] = $this->l('This charge could not be found.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergCharges').'&sb_done='.($on ? 'charge_on' : 'charge_off'));
            }
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $messages = array(
            'charge_on' => $this->l('The charge will be added to new bookings.'),
            'charge_off' => $this->l('The charge will no longer be added to new bookings.'),
            'tax_on' => $this->l('Tax is charged on new bookings.'),
            'tax_off' => $this->l('Tax is no longer charged on new bookings.'),
        );
        $done = (string) Tools::getValue('sb_done');
        if (isset($messages[$done])) {
            $this->confirmations[] = $messages[$done];
        }

        $this->context->smarty->assign(array(
            'sb_charges' => $this->module->getAutoCharges(),
            'sb_tax_on' => (bool) Configuration::get('PS_TAX'),
            'sb_taxes' => $this->module->getTaxesInUse(),
            'sb_can_edit' => (int) $this->tabAccess['edit'] === 1,
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergCharges'),
            'sb_service_link' => $this->context->link->getAdminLink('AdminNormalProducts'),
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/charges.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
