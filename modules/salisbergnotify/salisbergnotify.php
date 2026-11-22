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
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionAdminEmployeesFormModifier')
            && $this->registerHook('actionObjectEmployeeAddAfter')
            && $this->registerHook('actionObjectEmployeeUpdateAfter');
    }

    /**
     * True when the signed-in employee may switch booking alerts on or off for
     * other people: a SuperAdmin or a Hotel Manager (owner decision 2026-11-19).
     */
    public function canManageAlerts()
    {
        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)) {
            return false;
        }
        if ($employee->isSuperAdmin()) {
            return true;
        }
        $manager = (int) Db::getInstance()->getValue(
            'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang` WHERE `name` = \'Hotel Manager\'',
            false
        );

        return $manager && (int) $employee->id_profile === $manager;
    }

    /**
     * Administration > Employees, add or edit: "Phone (for texts)" for everyone
     * who can open the form (their own account included), and "Receive booking
     * alerts" for a SuperAdmin or Hotel Manager only.
     */
    public function hookActionAdminEmployeesFormModifier($params)
    {
        if (empty($params['fields'][0]['form']['input'])) {
            return;
        }
        $id = (int) Tools::getValue('id_employee');
        $params['fields'][0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Phone (for texts)'),
            'name' => 'sb_alert_phone',
            'class' => 'fixed-width-xl',
            'hint' => $this->l('Booking alerts are texted to this number. A Ghana number can start with 0 or +233.'),
        );
        $params['fields_value']['sb_alert_phone'] = $id ? $this->empPhone($id) : '';
        if ($this->canManageAlerts()) {
            $params['fields'][0]['form']['input'][] = array(
                'type' => 'switch',
                'label' => $this->l('Receive booking alerts'),
                'name' => 'sb_alert_on',
                'is_bool' => true,
                'desc' => $this->l('A text (when a phone is set and texts are switched on) and an email for each new booking, payment, cancellation and milestone.'),
                'values' => array(
                    array('id' => 'sb_alert_on_on', 'value' => 1, 'label' => $this->l('Yes')),
                    array('id' => 'sb_alert_on_off', 'value' => 0, 'label' => $this->l('No')),
                ),
            );
            $params['fields_value']['sb_alert_on'] = $id ? (int) $this->isRecipient($id) : 0;
        }
    }

    public function hookActionObjectEmployeeAddAfter($params)
    {
        $this->saveEmployeeAlertFields($params);
    }

    public function hookActionObjectEmployeeUpdateAfter($params)
    {
        $this->saveEmployeeAlertFields($params);
    }

    /**
     * Stores the two fields when they came from the employee form. A save from
     * anywhere else (sign-in, password change) does not post them and changes
     * nothing. An invalid number is not saved and is logged.
     */
    protected function saveEmployeeAlertFields($params)
    {
        if (empty($params['object']) || !Validate::isLoadedObject($params['object'])
            || Tools::getValue('controller') !== 'AdminEmployees') {
            return;
        }
        $id = (int) $params['object']->id;
        if (Tools::getIsset('sb_alert_phone')) {
            $phone = trim((string) Tools::getValue('sb_alert_phone'));
            if ($phone === '' || Validate::isPhoneNumber($phone)) {
                $this->setEmpPhone($id, $phone);
            } else {
                PrestaShopLogger::addLog('salisbergnotify: phone for employee '.$id.' not saved (invalid)', 2);
            }
        }
        if (Tools::getIsset('sb_alert_on') && $this->canManageAlerts()) {
            $ids = $this->alertRecipients();
            if ((int) Tools::getValue('sb_alert_on')) {
                $ids[] = $id;
            } else {
                $ids = array_diff($ids, array($id));
            }
            $this->setRecipients($ids);
        }
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
     * Alerts every chosen recipient: a short text if a key is saved and the
     * person has a phone, and a longer email (the same text plus a table of
     * details) if mail is set up. A failure on one channel or one person never
     * stops the others.
     *
     * @param array $details label => value rows for the email only
     */
    public function notifyRecipients($subject, $message, $reference, array $details = array())
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
                $this->emailRecipient($idEmployee, $subject, $message, $reference, $details);
            }
        }
    }

    /** Emails one recipient the alert at their staff email, if it is a real address. */
    protected function emailRecipient($idEmployee, $subject, $message, $reference, array $details = array())
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
                array(
                    '{heading}' => Tools::safeOutput($subject),
                    '{message}' => Tools::safeOutput($message),
                    '{details_html}' => self::detailsHtml($details),
                    '{details_txt}' => self::detailsText($details),
                    '{firstname}' => $employee->firstname,
                ),
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

    /**
     * The facts of a booking that texts and emails are written from, read once
     * here so staff and guest messages never disagree.
     */
    public static function bookingFacts(Order $order)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `room_num`, `room_type_name`, `date_from`, `date_to`
            FROM `'._DB_PREFIX_.'htl_booking_detail` WHERE `id_order` = '.(int) $order->id.' ORDER BY `date_from`, `room_num`'
        );
        $rows = is_array($rows) ? $rows : array();
        $from = $to = null;
        $rooms = array();
        foreach ($rows as $row) {
            $from = ($from === null || $row['date_from'] < $from) ? $row['date_from'] : $from;
            $to = ($to === null || $row['date_to'] > $to) ? $row['date_to'] : $to;
            $rooms[] = trim($row['room_num'].' ('.$row['room_type_name'].')');
        }
        $customer = new Customer((int) $order->id_customer);
        $total = (float) $order->total_paid_tax_incl;
        $paid = (float) $order->total_paid_real;
        $currency = (int) $order->id_currency;
        $nights = ($from && $to) ? max(1, (int) round((strtotime(substr($to, 0, 10)) - strtotime(substr($from, 0, 10))) / 86400)) : 0;

        return array(
            'shop' => Configuration::get('PS_SHOP_NAME'),
            'reference' => $order->reference,
            'guest' => trim($customer->firstname.' '.$customer->lastname),
            'firstname' => $customer->firstname,
            'phone' => trim((string) $customer->phone),
            'email' => (Validate::isEmail($customer->email) && !Customer::hasNoEmail($customer->email)) ? $customer->email : '',
            'rooms' => $rooms,
            'room_count' => count($rooms),
            'from' => $from ? date('j M Y', strtotime($from)) : '',
            'to' => $to ? date('j M Y', strtotime($to)) : '',
            'from_short' => $from ? date('j M', strtotime($from)) : '',
            'to_short' => $to ? date('j M', strtotime($to)) : '',
            'nights' => $nights,
            'total' => Tools::displayPrice($total, $currency),
            'paid' => Tools::displayPrice($paid, $currency),
            'balance' => Tools::displayPrice(max(0, $total - $paid), $currency),
            'owes' => ($total - $paid) > 0.009,
            'payment' => (string) $order->payment,
        );
    }

    /** label => value rows as an email table; values are escaped here */
    public static function detailsHtml(array $details)
    {
        if (!$details) {
            return '';
        }
        $html = '<table width="100%" cellpadding="8" cellspacing="0" style="font-size:14px;border:1px solid #d9d4c7;margin:0 0 16px;">';
        $first = true;
        foreach ($details as $label => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $line = $first ? '' : 'border-top:1px solid #d9d4c7;';
            $html .= '<tr><td width="40%" style="background-color:#f3efe6;color:#6b7280;'.$line.'">'.Tools::safeOutput($label).'</td>'
                .'<td style="font-weight:bold;'.$line.'">'.Tools::safeOutput($value).'</td></tr>';
            $first = false;
        }

        return $html.'</table>';
    }

    public static function detailsText(array $details)
    {
        $lines = array();
        foreach ($details as $label => $value) {
            if ($value !== '' && $value !== null) {
                $lines[] = $label.': '.$value;
            }
        }

        return implode("\n", $lines);
    }

    /** The rows a staff email shows for a booking */
    protected function staffDetails(array $f)
    {
        return array(
            $this->l('Booking') => $f['reference'],
            $this->l('Guest') => $f['guest'],
            $this->l('Phone') => $f['phone'],
            $this->l('Email') => $f['email'],
            $this->l('Rooms') => implode(', ', $f['rooms']),
            $this->l('Check-in') => $f['from'],
            $this->l('Check-out') => $f['to'],
            $this->l('Nights') => $f['nights'] ? (string) $f['nights'] : '',
            $this->l('Payment method') => $f['payment'],
            $this->l('Total') => $f['total'],
            $this->l('Paid') => $f['paid'],
            $this->l('Still to pay') => $f['owes'] ? $f['balance'] : '',
        );
    }

    /** A new booking was created (website or front desk): alert the chosen staff. */
    public function hookActionValidateOrder($params)
    {
        if (!$this->canNotify() || empty($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return;
        }
        $f = self::bookingFacts($params['order']);
        // text: at most one SMS (160 characters)
        $message = sprintf(
            $this->l('New booking %1$s: %2$s, %3$d room(s), %4$s-%5$s. Total %6$s.'),
            $f['reference'], $f['guest'], $f['room_count'], $f['from_short'], $f['to_short'], $f['total']
        );
        $this->notifyRecipients(
            sprintf($this->l('New booking %1$s for %2$s'), $f['reference'], $f['guest']),
            $message,
            $f['reference'],
            $this->staffDetails($f)
        );
    }

    /** A payment was recorded (full or part), or a booking cancelled: alert the chosen staff. */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (!$this->canNotify() || empty($params['newOrderStatus']) || empty($params['id_order'])) {
            return;
        }
        $state = $params['newOrderStatus'];
        $full = !empty($state->paid);
        $partial = (int) $state->id === (int) Configuration::get('PS_OS_PARTIAL_PAYMENT_ACCEPTED');
        $cancelled = (int) $state->id === (int) Configuration::get('PS_OS_CANCELED');
        if (!$full && !$partial && !$cancelled) {
            return;
        }
        $order = new Order((int) $params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        $f = self::bookingFacts($order);
        if ($cancelled) {
            $message = sprintf($this->l('Cancelled %1$s: %2$s, %3$s-%4$s. Rooms are free again.'), $f['reference'], $f['guest'], $f['from_short'], $f['to_short']);
            $subject = sprintf($this->l('Booking %1$s cancelled (%2$s)'), $f['reference'], $f['guest']);
        } elseif ($full) {
            $message = sprintf($this->l('Paid in full %1$s: %2$s, %3$s.'), $f['reference'], $f['guest'], $f['total']);
            $subject = sprintf($this->l('Booking %1$s paid in full'), $f['reference']);
        } else {
            $message = sprintf($this->l('Part payment %1$s: %2$s paid %3$s of %4$s. Due %5$s.'), $f['reference'], $f['guest'], $f['paid'], $f['total'], $f['balance']);
            $subject = sprintf($this->l('Part payment on booking %1$s'), $f['reference']);
        }
        $this->notifyRecipients($subject, $message, $f['reference'], $this->staffDetails($f));
    }

    /**
     * On a back office page, when the signed-in employee is a chosen recipient
     * with no phone yet and a key is saved, load the prompt that asks for it.
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $employee = $this->context->employee;
        // everyone without a phone is asked once per session (owner, 2026-11-19);
        // the number is the same one shown on their employee form
        if (!Validate::isLoadedObject($employee) || $this->empPhone($employee->id) !== '') {
            return;
        }
        Media::addJsDef(array('sbNotifyPhone' => array(
            'url' => $this->context->link->getAdminLink(self::PHONE_TAB),
            'name' => $employee->firstname,
            'recipient' => (int) $this->isRecipient($employee->id),
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
        // ₵ is outside the GSM alphabet: one such character turns a text into
        // 70-character parts, so a 100-character message would cost two SMS
        $message = trim(str_replace(array('GH₵', '₵'), array('GHS ', 'GHS '), (string) $message));
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
