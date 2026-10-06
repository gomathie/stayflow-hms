<?php
/**
 * Creates the booking in "Awaiting payment" for the chosen offline method.
 */
class SalisbergpayValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // A booking may only be confirmed from our own summary page, by POST, with
        // the customer's token: another site must not be able to trigger it.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isTokenValid()) {
            Tools::redirect('index.php?controller=order-opc');
        }

        // The option may have been withdrawn (address or settings changed) since the guest chose it
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == $this->module->name) {
                $authorized = true;
                break;
            }
        }
        $methods = $this->module->getAvailableMethods();
        $method = (string) Tools::getValue('method');
        if (!$authorized || !isset($methods[$method])) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        // check all service products are available
        ServiceProductCartDetail::validateServiceProductsInCart();

        if (Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem')) {
            require_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
            if (HotelOrderRestrictDate::validateOrderRestrictDateOnPayment($this)) {
                Tools::redirect('index.php?controller=order-opc');
            }
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        if ($cart->is_advance_payment) {
            $total = $cart->getOrderTotal(true, Cart::ADVANCE_PAYMENT);
        } else {
            $total = $cart->getOrderTotal(true, Cart::BOTH);
        }

        $this->module->validateOrder(
            $cart->id,
            Configuration::get('PS_OS_AWAITING_PAYMENT'),
            $total,
            $this->module->getPaymentName($method),
            null,
            array(),
            (int) $currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
    }
}
