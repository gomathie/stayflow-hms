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
        $idTab = (int) Tab::getIdFromClassName(self::TAB);
        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
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

        return $this->installTab() && $this->registerHook('actionSalisbergSendSms');
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
