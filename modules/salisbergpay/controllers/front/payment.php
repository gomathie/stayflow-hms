<?php
/**
 * Order summary shown before the guest confirms a cash or Mobile Money booking.
 */
class SalisbergpayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $methods = $this->module->getAvailableMethods();
        $method = (string) Tools::getValue('method');
        if (!isset($methods[$method])) {
            Tools::redirect('index.php?controller=order-opc');
        }

        if ($cart->is_advance_payment) {
            $total = $cart->getOrderTotal(true, Cart::ADVANCE_PAYMENT);
        } else {
            $total = $cart->getOrderTotal(true, Cart::BOTH);
        }

        // check all service products are available
        ServiceProductCartDetail::validateServiceProductsInCart();

        $restrict_order = false;
        if (Module::isInstalled('hotelreservationsystem') && Module::isEnabled('hotelreservationsystem')) {
            require_once _PS_MODULE_DIR_.'hotelreservationsystem/define.php';
            if (HotelOrderRestrictDate::validateOrderRestrictDateOnPayment($this)) {
                $restrict_order = true;
            }
        }
        if (count($this->errors)) {
            $restrict_order = true;
        }

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'total' => $total,
            'sbpay_method' => $methods[$method],
            'sbpay_momo' => $this->module->getMomoDetails(),
            'sbpay_token' => Tools::getToken(false),
            'restrict_order' => $restrict_order,
        ));

        $this->setTemplate('payment_execution.tpl');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJS($this->module->getLocalPath().'views/js/payment.js');
    }
}
