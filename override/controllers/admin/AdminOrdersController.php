<?php
/**
 * Bookings > Add new booking: a "Charge tax" switch on the booking form, on by
 * default. Front desk staff turn it off for a guest who is not to be charged
 * tax; that booking and its invoice are then made without tax.
 *
 * The switch is only shown when tax is charged at all and the manager allows
 * it (Rooms & Services > Charges & Tax, Salisbergpay::taxChoiceAllowed()).
 * Turning it off switches tax off for the one request that creates the
 * booking; nothing is saved to the settings.
 */
class AdminOrdersController extends AdminOrdersControllerCore
{
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddOrder')
            && (string) Tools::getValue('sb_charge_tax', '1') === '0'
            && $this->taxChoiceAllowed()
        ) {
            Configuration::set('PS_TAX', 0);
        }

        return parent::postProcess();
    }

    public function renderForm()
    {
        $this->context->smarty->assign('sb_tax_choice', $this->taxChoiceAllowed());

        return parent::renderForm();
    }

    protected function taxChoiceAllowed()
    {
        return Module::isEnabled('salisbergpay')
            && ($pay = Module::getInstanceByName('salisbergpay'))
            && $pay->taxChoiceAllowed();
    }
}
