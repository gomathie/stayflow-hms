<?php
/**
 * Salisberg Pay: offline payment options for Salisberg Hotels.
 *
 * Two methods, each confirmed by staff in the back office:
 *  - cash: the guest pays at the hotel
 *  - momo: the guest sends Mobile Money to the hotel's number, quoting the booking reference
 *
 * Bookings are created in the "Awaiting payment" state. Modelled on the bundled bankwire module.
 *
 * @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Salisbergpay extends PaymentModule
{
    const METHOD_CASH = 'cash';
    const METHOD_MOMO = 'momo';

    protected $_html = '';

    public function __construct()
    {
        $this->name = 'salisbergpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Salisberg Hotels';
        $this->controllers = array('payment', 'validation');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Salisberg Pay (Cash and Mobile Money)');
        $this->description = $this->l('Lets guests book and pay with cash at the hotel or by Mobile Money. Staff confirm each payment in the back office.');
        $this->confirmUninstall = $this->l('Are you sure you want to remove these payment options?');

        if (Configuration::get('SBPAY_MOMO') && !Configuration::get('SBPAY_MOMO_NUMBER')) {
            $this->warning = $this->l('Mobile Money is switched on but no number is set, so guests cannot see it yet.');
        }

        $this->payment_type = OrderPayment::PAYMENT_TYPE_REMOTE_PAYMENT;
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('payment')
            && $this->registerHook('paymentReturn')
            && Configuration::updateValue('SBPAY_CASH', 1)
            && Configuration::updateValue('SBPAY_MOMO', 1)
            && Configuration::updateValue('SBPAY_MOMO_NETWORK', 'MTN Mobile Money')
            && Configuration::updateValue('SBPAY_MOMO_NAME', '')
            && Configuration::updateValue('SBPAY_MOMO_NUMBER', '')
            && Configuration::updateValue('SBPAY_MOMO_NOTE', '');
    }

    public function uninstall()
    {
        foreach (array('SBPAY_CASH', 'SBPAY_MOMO', 'SBPAY_MOMO_NETWORK', 'SBPAY_MOMO_NAME', 'SBPAY_MOMO_NUMBER', 'SBPAY_MOMO_NOTE') as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Methods a guest can use right now, keyed by code.
     */
    public function getAvailableMethods()
    {
        $methods = array();
        if (Configuration::get('SBPAY_CASH')) {
            $methods[self::METHOD_CASH] = array(
                'code' => self::METHOD_CASH,
                'title' => $this->l('Pay cash at the hotel'),
                'hint' => $this->l('Pay at reception when you arrive'),
            );
        }
        // Without a number there is nowhere to send money, so the option stays hidden
        if (Configuration::get('SBPAY_MOMO') && Configuration::get('SBPAY_MOMO_NUMBER')) {
            $methods[self::METHOD_MOMO] = array(
                'code' => self::METHOD_MOMO,
                'title' => $this->l('Pay by Mobile Money (MoMo)'),
                'hint' => $this->l('Send payment from your phone; we confirm it and your booking'),
            );
        }

        return $methods;
    }

    /**
     * Name stored on the order; also how the confirmation page recognises the method.
     */
    public function getPaymentName($method)
    {
        return $method == self::METHOD_MOMO ? 'Mobile Money (MoMo)' : 'Cash at hotel';
    }

    public function getMomoDetails()
    {
        return array(
            'network' => Configuration::get('SBPAY_MOMO_NETWORK'),
            'name' => Configuration::get('SBPAY_MOMO_NAME'),
            'number' => Configuration::get('SBPAY_MOMO_NUMBER'),
            'note' => Configuration::get('SBPAY_MOMO_NOTE'),
        );
    }

    public function hookPayment($params)
    {
        if (!$this->active || !$this->checkCurrency($params['cart'])) {
            return;
        }
        $methods = $this->getAvailableMethods();
        if (!$methods) {
            return;
        }
        $this->smarty->assign('sbpay_methods', $methods);

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        $objOrder = $params['objOrder'];
        $idOrderState = $objOrder->getCurrentState();
        $objOrderState = new OrderState($idOrderState);
        $history = $objOrder->getHistory($this->context->language->id);
        $initialStatus = array_pop($history);

        $smartyVars = array('status' => 'failed');
        if ($idOrderState == Configuration::get('PS_OS_AWAITING_PAYMENT')
            || ($objOrderState->logable
                && $initialStatus['id_order_state'] == Configuration::get('PS_OS_AWAITING_PAYMENT')
            )
        ) {
            $objCart = new Cart($objOrder->id_cart);
            $cartTotal = $objCart->is_advance_payment ? $objOrder->getOrdersTotalPaid(1) : $objOrder->getOrdersTotalPaid();
            $objHotelBooking = new HotelBookingDetail();

            $smartyVars = array(
                'status' => 'ok',
                'sbpay_method' => $objOrder->payment == $this->getPaymentName(self::METHOD_MOMO) ? self::METHOD_MOMO : self::METHOD_CASH,
                'sbpay_momo' => $this->getMomoDetails(),
                'cart_room_bookings' => $objHotelBooking->getBookingDataByOrderReference($objOrder->reference),
                'total_to_pay' => Tools::displayPrice($cartTotal, $params['currencyObj'], false),
                'reference' => $objOrder->reference,
            );
        }
        $this->smarty->assign($smartyVars);

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $number = trim((string) Tools::getValue('SBPAY_MOMO_NUMBER'));
            $name = trim((string) Tools::getValue('SBPAY_MOMO_NAME'));
            $network = trim((string) Tools::getValue('SBPAY_MOMO_NETWORK'));
            $note = trim(strip_tags((string) Tools::getValue('SBPAY_MOMO_NOTE')));
            $errors = array();

            if ($number !== '' && !preg_match('/^\+?[0-9 ]{7,20}$/', $number)) {
                $errors[] = $this->l('The Mobile Money number may only contain digits, spaces and a leading +.');
            }
            if (!Validate::isGenericName($name) || !Validate::isGenericName($network)) {
                $errors[] = $this->l('The account name and network must not contain < > = { } characters.');
            }
            if (Tools::strlen($note) > 500) {
                $errors[] = $this->l('The note is too long (500 characters at most).');
            }
            if (Tools::getValue('SBPAY_MOMO') && $number === '') {
                $errors[] = $this->l('Enter the Mobile Money number, or switch Mobile Money off.');
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->_html .= $this->displayError($error);
                }
            } else {
                Configuration::updateValue('SBPAY_CASH', (int) Tools::getValue('SBPAY_CASH'));
                Configuration::updateValue('SBPAY_MOMO', (int) Tools::getValue('SBPAY_MOMO'));
                Configuration::updateValue('SBPAY_MOMO_NETWORK', $network);
                Configuration::updateValue('SBPAY_MOMO_NAME', $name);
                Configuration::updateValue('SBPAY_MOMO_NUMBER', $number);
                Configuration::updateValue('SBPAY_MOMO_NOTE', $note);
                $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $this->_html.$this->renderForm();
    }

    public function renderForm()
    {
        $switch = array(
            array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')),
            array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No')),
        );
        $fields_form = array(
            'form' => array(
                'legend' => array('title' => $this->l('Payment options'), 'icon' => 'icon-money'),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Cash at the hotel'),
                        'name' => 'SBPAY_CASH',
                        'is_bool' => true,
                        'values' => $switch,
                        'desc' => $this->l('Guests book now and pay at reception.'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Mobile Money (MoMo)'),
                        'name' => 'SBPAY_MOMO',
                        'is_bool' => true,
                        'values' => $switch,
                        'desc' => $this->l('Guests send money to the number below. Shown to guests only when a number is set.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Network'),
                        'name' => 'SBPAY_MOMO_NETWORK',
                        'desc' => $this->l('For example: MTN Mobile Money, Telecel Cash, AirtelTigo Money.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Registered account name'),
                        'name' => 'SBPAY_MOMO_NAME',
                        'desc' => $this->l('The name the guest will see when sending, so they can check it is you.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Mobile Money number'),
                        'name' => 'SBPAY_MOMO_NUMBER',
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Extra note for guests'),
                        'name' => 'SBPAY_MOMO_NOTE',
                        'desc' => $this->l('Optional. For example a merchant ID, or how soon to pay.'),
                    ),
                ),
                'submit' => array('title' => $this->l('Save')),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $values = array();
        foreach (array('SBPAY_CASH', 'SBPAY_MOMO', 'SBPAY_MOMO_NETWORK', 'SBPAY_MOMO_NAME', 'SBPAY_MOMO_NUMBER', 'SBPAY_MOMO_NOTE') as $key) {
            $values[$key] = Tools::getValue($key, Configuration::get($key));
        }
        $helper->tpl_vars = array(
            'fields_value' => $values,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }
}
