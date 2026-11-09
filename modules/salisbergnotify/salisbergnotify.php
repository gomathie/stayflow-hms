<?php
/**
 * Salisberg Notify
 *
 * Sends the guest texts that the front desk already prepares (check-in, and
 * paid in full) through mNotify. The only secret is the API key, entered by
 * a SuperAdmin under Administration > Integrations. With no key saved, the
 * hook returns at once and no text is sent.
 *
 * The request is the mNotify quick-SMS call:
 *   POST https://api.mnotify.com/api/sms/quick?key=API_KEY
 *   {"recipient":["0..."],"sender":"Salisberg","message":"...","is_schedule":false}
 * The sender name must be a sender ID already approved on the mNotify account.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Salisbergnotify extends Module
{
    const KEY = 'SBNOTIFY_API_KEY';
    const SENDER = 'SBNOTIFY_SENDER';
    const DEFAULT_SENDER = 'Salisberg';
    const TAB = 'AdminSalisbergIntegrations';
    // Staff who receive booking alerts (a chosen list of employee ids), and each
    // one's phone number. The phone is kept here, not on the vendor employee row.
    const ALERT_EMPLOYEES = 'SBNOTIFY_ALERT_EMPLOYEES';
    const EMP_PHONE_PREFIX = 'SBNOTIFY_EMP_PHONE_';
    // Hidden page any signed-in employee may post their own phone to, so a staff
    // member who cannot open Integrations can still answer the login prompt.
    const PHONE_TAB = 'AdminSalisbergPhone';

    public function __construct()
    {
        $this->name = 'salisbergnotify';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salisberg';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Salisberg Notify');
        $this->description = $this->l('Sends guest text messages through mNotify once an API key is saved.');
    }

    public function install()
    {
        return parent::install() && $this->ensureSetup();
    }

    public function uninstall()
    {
        foreach (array(self::TAB, self::PHONE_TAB) as $class) {
            $idTab = (int) Tab::getIdFromClassName($class);
            if ($idTab) {
                $tab = new Tab($idTab);
                $tab->delete();
            }
        }

        return parent::uninstall();
    }

    /**
     * Creates the Integrations page and listens for texts. Safe to call on
     * every deploy. A saved key and sender name are left as they are.
     */
    public function ensureSetup()
    {
        if (Configuration::get(self::SENDER) === false) {
            Configuration::updateValue(self::SENDER, self::DEFAULT_SENDER);
        }

        return $this->installTab()
            && $this->installPhoneTab()
            && $this->registerHook('actionSalisbergSendSms')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('actionAdminControllerSetMedia');
    }

    /** Employee ids chosen to receive booking alerts. */
    public function alertRecipients()
    {
        $ids = array_map('intval', explode(',', (string) Configuration::get(self::ALERT_EMPLOYEES)));

        return array_values(array_unique(array_filter($ids)));
    }

    public function isRecipient($idEmployee)
    {
        return in_array((int) $idEmployee, $this->alertRecipients(), true);
    }

    public function setRecipients(array $ids)
    {
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids))));

        Configuration::updateValue(self::ALERT_EMPLOYEES, implode(',', $clean));
    }

    /** The phone a given employee receives alerts on, or '' when none is set. */
    public function empPhone($idEmployee)
    {
        return trim((string) Configuration::get(self::EMP_PHONE_PREFIX.(int) $idEmployee));
    }

    public function setEmpPhone($idEmployee, $phone)
    {
        Configuration::updateValue(self::EMP_PHONE_PREFIX.(int) $idEmployee, trim((string) $phone));
    }

    /** True when mail is set up (SMTP or PHP mail), i.e. not set to "never send". */
    public function mailEnabled()
    {
        return (int) Configuration::get('PS_MAIL_METHOD') !== 3;
    }

    /** True when an alert can reach anyone: by text (a key is saved) or by email. */
    public function canNotify()
    {
        return $this->hasKey() || $this->mailEnabled();
    }

    /**
     * Alerts every chosen recipient, by text if a key is saved and the person has
     * a phone, and by email if mail is set up. A failure on one channel or one
     * person never stops the others.
     */
    public function notifyRecipients($subject, $message, $reference)
    {
        $hasKey = $this->hasKey();
        $mail = $this->mailEnabled();
        if (!$hasKey && !$mail) {
            return;
        }
        foreach ($this->alertRecipients() as $idEmployee) {
            if ($hasKey) {
                $phone = $this->empPhone($idEmployee);
                if ($phone !== '') {
                    $this->deliver($phone, $message, $reference);
                }
            }
            if ($mail) {
                $this->emailRecipient($idEmployee, $subject, $message, $reference);
            }
        }
    }

    /** Emails one recipient the alert at their staff email, if it is a real address. */
    protected function emailRecipient($idEmployee, $subject, $message, $reference)
    {
        $employee = new Employee((int) $idEmployee);
        if (!Validate::isLoadedObject($employee) || !Validate::isEmail($employee->email)) {
            return;
        }
        try {
            Mail::Send(
                (int) $employee->id_lang ?: (int) Configuration::get('PS_LANG_DEFAULT'),
                'staff_alert',
                $subject,
                array('{message}' => $message, '{firstname}' => $employee->firstname),
                $employee->email,
                $employee->firstname.' '.$employee->lastname,
                null,
                null,
                null,
                null,
                $this->local_path.'mails/'
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog('salisbergnotify: alert email for '.$this->logRef($reference).' not sent: '.$e->getMessage(), 3);
        }
    }

    /** A new booking was created (website or front desk): alert the chosen staff. */
    public function hookActionValidateOrder($params)
    {
        if (!$this->canNotify() || empty($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return;
        }
        $order = $params['order'];
        $rooms = Db::getInstance()->getRow(
            'SELECT COUNT(*) AS rooms, MIN(`date_from`) AS date_from, MAX(`date_to`) AS date_to
            FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_order` = '.(int) $order->id
        );
        $guest = '';
        if (!empty($params['customer']) && Validate::isLoadedObject($params['customer'])) {
            $guest = trim($params['customer']->firstname.' '.$params['customer']->lastname);
        }
        $dates = '';
        if (!empty($rooms['date_from']) && !empty($rooms['date_to'])) {
            $dates = ', '.date('j M', strtotime($rooms['date_from'])).'-'.date('j M', strtotime($rooms['date_to']));
        }
        $count = isset($rooms['rooms']) ? (int) $rooms['rooms'] : 0;
        $message = sprintf(
            $this->l('%1$s: new booking %2$s%3$s%4$s. Total %5$s.'),
            Configuration::get('PS_SHOP_NAME'),
            $order->reference,
            $guest !== '' ? ' for '.$guest : '',
            ($count ? ', '.$count.' '.($count == 1 ? $this->l('room') : $this->l('rooms')) : '').$dates,
            Tools::displayPrice((float) $order->total_paid_tax_incl, (int) $order->id_currency)
        );
        $this->notifyRecipients(sprintf($this->l('New booking %s'), $order->reference), $message, $order->reference);
    }

    /** A payment was recorded (full or part): alert the chosen staff. */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!$this->canNotify() || empty($params['newOrderStatus']) || empty($params['id_order'])) {
            return;
        }
        $state = $params['newOrderStatus'];
        $full = !empty($state->paid);
        $partial = (int) $state->id === (int) Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
        if (!$full && !$partial) {
            return;
        }
        $order = new Order((int) $params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        $message = sprintf(
            $this->l('%1$s: payment on booking %2$s. Paid %3$s of %4$s.'),
            Configuration::get('PS_SHOP_NAME'),
            $order->reference,
            Tools::displayPrice((float) $order->total_paid_real, (int) $order->id_currency),
            Tools::displayPrice((float) $order->total_paid_tax_incl, (int) $order->id_currency)
        );
        $this->notifyRecipients(sprintf($this->l('Payment on booking %s'), $order->reference), $message, $order->reference);
    }

    /**
     * On a back office page, when the signed-in employee is a chosen recipient
     * with no phone yet and a key is saved, load the prompt that asks for it.
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)
            || !$this->hasKey()
            || !$this->isRecipient($employee->id)
            || $this->empPhone($employee->id) !== '') {
            return;
        }
        Media::addJsDef(array('sbNotifyPhone' => array(
            'url' => $this->context->link->getAdminLink(self::PHONE_TAB),
            'name' => $employee->firstname,
        )));
        $this->context->controller->addCSS($this->_path.'views/css/phone-prompt.css');
        $this->context->controller->addJS($this->_path.'views/js/phone-prompt.js');
    }

    /**
     * Opens the Integrations page from the module's Configure button, so the
     * key is entered in one place only.
     */
    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::TAB));
    }

    /** A text prepared by the front desk. With no key saved, nothing is sent. */
    public function hookActionSalisbergSendSms($params)
    {
        $message = isset($params['message']) ? trim((string) $params['message']) : '';
        $phone = isset($params['phone']) ? (string) $params['phone'] : '';
        $reference = isset($params['reference']) ? (string) $params['reference'] : '';
        if ($message === '') {
            return;
        }
        $this->deliver($phone, $message, $reference);
    }

    /**
     * Sends one text. Returns true when mNotify accepts it. A missing key,
     * a missing number or a network failure returns false and never throws,
     * so check-in and payment are not stopped.
     */
    public function deliver($phone, $message, $reference)
    {
        $key = trim((string) Configuration::get(self::KEY));
        if ($key === '') {
            return false;
        }
        $number = $this->normalizePhone($phone);
        $message = trim((string) $message);
        if ($number === '' || $message === '') {
            PrestaShopLogger::addLog('salisbergnotify: text for '.$this->logRef($reference).' skipped, no usable phone number', 2);

            return false;
        }
        $sender = trim((string) Configuration::get(self::SENDER));
        if (!preg_match('/^[A-Za-z0-9]{1,11}$/', $sender)) {
            $sender = self::DEFAULT_SENDER;
        }

        return $this->post($key, $sender, $number, $message, $reference);
    }

    /** Ghana numbers become the local 0XXXXXXXXX shape mNotify shows in its examples. */
    public function normalizePhone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strpos($digits, '00233') === 0) {
            $digits = substr($digits, 2);
        }
        if (strpos($digits, '233') === 0 && strlen($digits) >= 12) {
            $digits = '0'.substr($digits, 3);
        }

        return $digits;
    }

    /** True when a key is saved. The key itself is never returned. */
    public function hasKey()
    {
        return trim((string) Configuration::get(self::KEY)) !== '';
    }

    public function sender()
    {
        $sender = trim((string) Configuration::get(self::SENDER));

        return preg_match('/^[A-Za-z0-9]{1,11}$/', $sender) ? $sender : self::DEFAULT_SENDER;
    }

    protected function installTab()
    {
        if (Tab::getIdFromClassName(self::TAB)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::TAB;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminAdmin');
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Integrations';
        }
        // add() also reports failure when only its permission bookkeeping fails
        // (no employee in context), so judge by whether the tab now exists
        $tab->add();

        return (bool) Tab::getIdFromClassName(self::TAB);
    }

    /**
     * A hidden page (not in the menu) that any signed-in employee may post their
     * own phone to. Its controller grants access to every employee itself, so no
     * per-profile permission is needed.
     */
    protected function installPhoneTab()
    {
        if (Tab::getIdFromClassName(self::PHONE_TAB)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::PHONE_TAB;
        $tab->module = $this->name;
        $tab->id_parent = -1;
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'My phone';
        }
        $tab->add();

        return (bool) Tab::getIdFromClassName(self::PHONE_TAB);
    }

    /**
     * Posts one text. The key travels only in the request and is never written
     * to the log. A slow or absent reply gives up after 8 seconds.
     */
    protected function post($key, $sender, $number, $message, $reference)
    {
        $body = json_encode(array(
            'recipient' => array($number),
            'sender' => $sender,
            'message' => $message,
            'is_schedule' => false,
        ));
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 8,
                'ignore_errors' => true,
            ),
        ));
        $response = @file_get_contents('https://api.mnotify.com/api/sms/quick?key='.rawurlencode($key), false, $context);
        $ref = $this->logRef($reference);
        if ($response === false) {
            PrestaShopLogger::addLog('salisbergnotify: text for '.$ref.' was not sent (no reply from mNotify)', 3);

            return false;
        }
        $data = json_decode($response, true);
        $status = is_array($data) && isset($data['status']) ? (string) $data['status'] : '';
        $code = is_array($data) && isset($data['code']) ? (string) $data['code'] : '';
        $note = '';
        if (is_array($data)) {
            if (isset($data['message']) && $data['message'] !== '') {
                $note = (string) $data['message'];
            } elseif (isset($data['error'])) {
                $note = (string) $data['error'];
            }
        }
        $note = str_replace($key, '', strip_tags($note));
        $note = Tools::substr(trim($note), 0, 160);
        if ($status === 'success' || $code === '2000') {
            PrestaShopLogger::addLog('salisbergnotify: text for '.$ref.' accepted ('.$code.')', 1);

            return true;
        }
        PrestaShopLogger::addLog('salisbergnotify: text for '.$ref.' refused ('.$code.' '.$note.')', 3);

        return false;
    }

    protected function logRef($reference)
    {
        $reference = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $reference);

        return $reference !== '' ? $reference : 'unknown';
    }
}
