<?php
/**
 * Administration > Integrations. SuperAdmin only. The API key is written once
 * and never shown again. A blank key box keeps the saved key.
 */
class AdminSalisbergIntegrationsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Integrations');
    }

    public function viewAccess($disable = false)
    {
        return (bool) ($this->context->employee && $this->context->employee->isSuperAdmin());
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
        if (!$this->context->employee || !$this->context->employee->isSuperAdmin()) {
            $this->errors[] = $this->l('You do not have permission to change this.');

            return;
        }
        if (Tools::isSubmit('sb_test_one')) {
            $this->sendTestToEmployee((int) Tools::getValue('sb_test_one'));
        } elseif (Tools::isSubmit('sb_save')) {
            $this->save();
        } elseif (Tools::isSubmit('sb_test')) {
            $this->sendTest();
        } elseif (Tools::isSubmit('sb_save_recipients')) {
            $this->saveRecipients();
        } elseif (Tools::isSubmit('sb_save_mail')) {
            $this->saveMail();
        } elseif (Tools::isSubmit('sb_test_mail')) {
            $this->sendTestMail();
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $messages = array(
            'saved' => $this->l('The integration settings are saved.'),
            'removed' => $this->l('The API key is removed. No text will be sent.'),
            'test_ok' => $this->l('mNotify accepted the test text.'),
            'test_fail' => $this->l('The test text was not accepted. Check the key, the sender name and the phone number, then try again.'),
            'recipients' => $this->l('The booking-alert recipients are saved.'),
            'mail_saved' => $this->l('The email settings are saved. Send a test email to check them.'),
        );
        $done = (string) Tools::getValue('sb_done');
        if (isset($messages[$done])) {
            $this->confirmations[] = $messages[$done];
        }

        $recipients = $this->module->alertRecipients();
        $employees = array();
        $rows = Db::getInstance()->executeS(
            'SELECT `id_employee`, `firstname`, `lastname`, `email` FROM `'._DB_PREFIX_.'employee`
            WHERE `active` = 1 ORDER BY `lastname` ASC'
        );
        foreach ((array) $rows as $row) {
            $id = (int) $row['id_employee'];
            $employees[] = array(
                'id' => $id,
                'name' => $row['firstname'].' '.$row['lastname'],
                'email' => $row['email'],
                'is_recipient' => in_array($id, $recipients, true),
                'phone' => $this->module->empPhone($id),
            );
        }
        $method = (int) Configuration::get('PS_MAIL_METHOD');
        $missingPhone = 0;
        foreach ($employees as $emp) {
            if ($emp['is_recipient'] && $emp['phone'] === '') {
                $missingPhone++;
            }
        }
        // the overview: one card per integration; a new integration adds one entry here
        $cards = array(
            array(
                'anchor' => 'sb-int-sms', 'icon' => 'icon-mobile', 'title' => $this->l('Text messages'), 'provider' => 'mNotify',
                'ok' => $this->module->hasKey(),
                'status' => $this->module->hasKey() ? sprintf($this->l('On, sender %s'), $this->module->sender()) : $this->l('Off: no API key saved'),
            ),
            array(
                'anchor' => 'sb-int-alerts', 'icon' => 'icon-bell', 'title' => $this->l('Booking alerts'), 'provider' => $this->l('Staff'),
                'ok' => count($recipients) > 0 && !$missingPhone,
                'status' => sprintf($this->l('%1$d people chosen, %2$d without a phone'), count($recipients), $missingPhone),
            ),
            array(
                'anchor' => 'sb-int-mail', 'icon' => 'icon-envelope', 'title' => $this->l('Email'), 'provider' => $method === 2 ? 'SMTP' : 'PHP mail',
                'ok' => $method === 2,
                'status' => $method === 3 ? $this->l('Off: no email is sent') : ($method === 2 ? sprintf($this->l('On, %s'), (string) Configuration::get('PS_MAIL_SERVER')) : $this->l('Not set up: no mail server saved')),
            ),
            array(
                'anchor' => '', 'icon' => 'icon-bug', 'title' => $this->l('Error tracking'), 'provider' => 'Sentry',
                'ok' => (bool) getenv('SENTRY_DSN'),
                'status' => getenv('SENTRY_DSN') ? $this->l('On') : $this->l('Off: SENTRY_DSN not set on the server'),
            ),
        );
        $this->context->smarty->assign(array(
            'sb_cards' => $cards,
            'sb_has_key' => $this->module->hasKey(),
            'sb_sender' => $this->module->sender(),
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergIntegrations'),
            'sb_employees' => $employees,
            'sb_mail_enabled' => $method !== 3,
            'sb_mail_method' => $method === 2 ? $this->l('SMTP') : ($method === 3 ? $this->l('off') : $this->l('PHP mail')),
            'sb_mail_server' => $method === 2 ? (string) Configuration::get('PS_MAIL_SERVER') : '',
            'sb_mail_link' => '#sb-int-mail',
            'sb_mail' => array(
                'on' => $method === 2,
                'server' => (string) Configuration::get('PS_MAIL_SERVER'),
                'port' => (int) Configuration::get('PS_MAIL_SMTP_PORT') ?: 587,
                'enc' => (string) Configuration::get('PS_MAIL_SMTP_ENCRYPTION') ?: 'tls',
                'user' => (string) Configuration::get('PS_MAIL_USER'),
                'has_pass' => (bool) Configuration::get('PS_MAIL_PASSWD'),
                'from' => (string) Configuration::get('PS_SHOP_EMAIL'),
            ),
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/integrations.tpl');
        $this->context->smarty->assign('content', $this->content);
    }

    protected function save()
    {
        $sender = trim((string) Tools::getValue('sb_sender'));
        $key = trim((string) Tools::getValue('sb_api_key'));
        $remove = (int) Tools::getValue('sb_remove_key') === 1;

        if (!preg_match('/^[A-Za-z0-9]{1,11}$/', $sender)) {
            $this->errors[] = $this->l('The sender name must be letters and numbers only, at most 11 characters.');
        }
        if ($remove && $key !== '') {
            $this->errors[] = $this->l('Enter a new key, or remove the saved key. Not both.');
        } elseif ($key !== '' && !preg_match('/^[A-Za-z0-9._\-]{8,128}$/', $key)) {
            $this->errors[] = $this->l('The API key does not look right. Paste the key from mNotify, with no spaces.');
        }
        if ($this->errors) {
            return;
        }

        // html=true so a key is stored exactly as pasted
        Configuration::updateValue(Salisbergnotify::SENDER, $sender);
        if ($remove) {
            Configuration::updateValue(Salisbergnotify::KEY, '', true);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergIntegrations').'&sb_done=removed');
        }
        if ($key !== '') {
            Configuration::updateValue(Salisbergnotify::KEY, $key, true);
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergIntegrations').'&sb_done=saved');
    }

    /**
     * The mail server, saved in the platform's own settings (PS_MAIL_*), the
     * same ones every email in the app is sent with. Advanced Parameters,
     * E-mail shows no form, so this is where the server is entered.
     */
    protected function saveMail()
    {
        $on = (int) Tools::getValue('sb_mail_on') === 1;
        $server = trim((string) Tools::getValue('sb_mail_server'));
        $port = (int) Tools::getValue('sb_mail_port');
        $enc = (string) Tools::getValue('sb_mail_enc');
        $user = trim((string) Tools::getValue('sb_mail_user'));
        $pass = (string) Tools::getValue('sb_mail_pass');
        $from = trim((string) Tools::getValue('sb_mail_from'));

        if (!in_array($enc, array('off', 'tls', 'ssl'), true)) {
            $enc = 'tls';
        }
        if ($on) {
            if (!preg_match('/^[A-Za-z0-9.\-]{3,255}$/', $server)) {
                $this->errors[] = $this->l('Enter the mail server name, for example smtp.example.com.');
            }
            if ($port < 1 || $port > 65535) {
                $this->errors[] = $this->l('Enter the port, for example 587 (TLS) or 465 (SSL).');
            }
            if ($user === '' || !Validate::isEmail($user) && !preg_match('/^[A-Za-z0-9._@\-]{2,255}$/', $user)) {
                $this->errors[] = $this->l('Enter the mail account user name, usually the full email address.');
            }
            if (!Validate::isEmail($from)) {
                $this->errors[] = $this->l('Enter the address emails are sent from, for example booking@salisberg.com.');
            }
            if ($pass === '' && !Configuration::get('PS_MAIL_PASSWD')) {
                $this->errors[] = $this->l('Enter the mail account password.');
            }
        }
        if ($this->errors) {
            return;
        }

        Configuration::updateValue('PS_MAIL_METHOD', $on ? 2 : 3);
        if ($on) {
            Configuration::updateValue('PS_MAIL_SERVER', $server);
            Configuration::updateValue('PS_MAIL_SMTP_PORT', $port);
            Configuration::updateValue('PS_MAIL_SMTP_ENCRYPTION', $enc);
            Configuration::updateValue('PS_MAIL_USER', $user);
            Configuration::updateValue('PS_SHOP_EMAIL', $from);
            // an empty box keeps the saved password; html=true stores it exactly as typed
            if ($pass !== '') {
                Configuration::updateValue('PS_MAIL_PASSWD', $pass, true);
            }
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergIntegrations').'&sb_done=mail_saved#sb-int-mail');
    }

    /** One test email through the saved server, reported here in plain words */
    protected function sendTestMail()
    {
        $to = trim((string) Tools::getValue('sb_mail_test_to'));
        if (!Validate::isEmail($to)) {
            $this->errors[] = $this->l('Enter an email address to send the test to.');

            return;
        }
        if ((int) Configuration::get('PS_MAIL_METHOD') !== 2) {
            $this->errors[] = $this->l('Save the mail server first.');

            return;
        }
        $result = Mail::sendMailTest(
            true,
            Configuration::get('PS_MAIL_SERVER'),
            sprintf($this->l('This is a test email from %s. If you can read it, emails from the app reach people.'), Configuration::get('PS_SHOP_NAME')),
            sprintf($this->l('%s: test email'), Configuration::get('PS_SHOP_NAME')),
            'text/html',
            $to,
            Configuration::get('PS_SHOP_EMAIL'),
            Configuration::get('PS_MAIL_USER'),
            Configuration::get('PS_MAIL_PASSWD'),
            Configuration::get('PS_MAIL_SMTP_PORT'),
            Configuration::get('PS_MAIL_SMTP_ENCRYPTION')
        );
        if ($result === true) {
            $this->confirmations[] = sprintf($this->l('The mail server accepted the test email to %s. Check that inbox (and its spam folder).'), Tools::safeOutput($to));
        } else {
            PrestaShopLogger::addLog('Test email failed: '.(is_string($result) ? $result : 'unknown error'), 2);
            $this->errors[] = $this->l('The test email was not sent. Check the server, port, encryption, user name and password. The server\'s reply is in Advanced Parameters, Logs.');
        }
    }

    protected function saveRecipients()
    {
        $ids = (array) Tools::getValue('sb_recipients');
        $this->module->setRecipients($ids);
        // a phone may be set here too, beside the staff member doing it themselves
        foreach ($ids as $id) {
            $id = (int) $id;
            $phone = trim((string) Tools::getValue('sb_emp_phone_'.$id));
            if ($phone !== '' && !Validate::isPhoneNumber($phone)) {
                $this->errors[] = sprintf($this->l('The phone number for one recipient is not valid: %s'), Tools::safeOutput($phone));

                return;
            }
            // only overwrite when a value was typed, so a blank box keeps the saved number
            if (Tools::getIsset('sb_emp_phone_'.$id)) {
                $this->module->setEmpPhone($id, $phone);
            }
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergIntegrations').'&sb_done=recipients');
    }

    /**
     * "Test" on a recipient row (SuperAdmin only, like this whole page): one
     * short text to that person's saved phone. The result is reported for that
     * person by name, so a missing or wrong number is obvious.
     */
    protected function sendTestToEmployee($idEmployee)
    {
        $employee = new Employee($idEmployee);
        if (!Validate::isLoadedObject($employee)) {
            $this->errors[] = $this->l('That staff member could not be found.');

            return;
        }
        $name = trim($employee->firstname.' '.$employee->lastname);
        if (!$this->module->hasKey()) {
            $this->errors[] = $this->l('Save an API key before sending a test.');

            return;
        }
        $phone = $this->module->empPhone($idEmployee);
        if ($phone === '') {
            $this->errors[] = sprintf($this->l('%s has no phone saved. Type it in their row and click Save recipients, then test again.'), Tools::safeOutput($name));

            return;
        }
        $ok = $this->module->deliver(
            $phone,
            sprintf($this->l('%s: test booking alert. If you got this, texts reach you. No action needed.'), Configuration::get('PS_SHOP_NAME')),
            'test-'.$idEmployee
        );
        if ($ok) {
            $this->confirmations[] = sprintf($this->l('Test text accepted for %1$s (%2$s).'), Tools::safeOutput($name), Tools::safeOutput($phone));
        } else {
            $this->errors[] = sprintf($this->l('The test text to %1$s (%2$s) was not accepted. Check the number, the key and the sender name. Details are in Advanced Parameters, Logs.'), Tools::safeOutput($name), Tools::safeOutput($phone));
        }
    }

    protected function sendTest()
    {
        if (!$this->module->hasKey()) {
            $this->errors[] = $this->l('Save an API key before sending a test.');

            return;
        }
        $phone = trim((string) Tools::getValue('sb_test_phone'));
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) < 9 || strlen($digits) > 15) {
            $this->errors[] = $this->l('Enter a phone number to send the test to.');

            return;
        }
        $ok = $this->module->deliver(
            $phone,
            $this->l('Salisberg test message. You can ignore this text.'),
            'test'
        );
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergIntegrations').'&sb_done='.($ok ? 'test_ok' : 'test_fail'));
    }
}
