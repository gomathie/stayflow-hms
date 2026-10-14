<?php
/**
 * Guest details typed on the booking form (Bookings > Add new booking):
 * name, phone and email. If a guest with that email, or else that phone
 * number, is already on record, the booking uses them; otherwise the guest
 * is created. One request, so the desk never leaves the booking form.
 */
class AdminCustomersController extends AdminCustomersControllerCore
{
    public function ajaxProcessFindOrCreateGuest()
    {
        $firstname = trim((string) Tools::getValue('firstname'));
        $lastname = trim((string) Tools::getValue('lastname'));
        $phone = trim((string) Tools::getValue('phone'));
        $email = trim((string) Tools::getValue('email'));

        $errors = array();
        if ($firstname === '' || !Validate::isName($firstname)) {
            $errors[] = $this->l('Enter the first name, letters only.');
        }
        if ($lastname === '' || !Validate::isName($lastname)) {
            $errors[] = $this->l('Enter the last name, letters only.');
        }
        if ($phone !== '' && !Validate::isPhoneNumber($phone)) {
            $errors[] = $this->l('The phone number is not valid.');
        }
        if ($email !== '' && !Validate::isEmail($email)) {
            $errors[] = $this->l('The email address is not valid.');
        }
        if ($phone === '' && $email === '') {
            $errors[] = $this->l('Enter a phone number or an email address.');
        }
        if ($errors) {
            die(json_encode(array('errors' => $errors)));
        }

        $idCustomer = $this->findGuest($email, $phone);
        $created = false;
        if (!$idCustomer) {
            if ($this->tabAccess['add'] !== 1) {
                die(json_encode(array('errors' => array($this->l('You do not have permission to add a guest.')))));
            }
            $customer = new Customer();
            $customer->firstname = $firstname;
            $customer->lastname = $lastname;
            $customer->phone = $phone;
            // no email: a placeholder nothing is ever sent to (see processAdd)
            $customer->email = ($email !== '')
                ? $email
                : 'guest-'.date('YmdHis').'-'.Tools::passwdGen(4, 'NUMERIC').'@'.Customer::NO_EMAIL_DOMAIN;
            $customer->passwd = Tools::encrypt(Tools::passwdGen(16));
            $customer->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            $customer->active = 1;
            try {
                $saved = $customer->add();
            } catch (Exception $e) {
                PrestaShopLogger::addLog('Guest not created from the booking form: '.$e->getMessage(), 3);
                $saved = false;
            }
            if (!$saved) {
                die(json_encode(array('errors' => array($this->l('The guest could not be saved. Check the details and try again.')))));
            }
            $customer->updateGroup(array((int) $customer->id_default_group));
            $idCustomer = (int) $customer->id;
            $created = true;
        }

        $customer = new Customer((int) $idCustomer);
        die(json_encode(array(
            'created' => $created,
            'customer' => array(
                'id_customer' => (int) $customer->id,
                'firstname' => Tools::safeOutput($customer->firstname),
                'lastname' => Tools::safeOutput($customer->lastname),
                'email' => Customer::hasNoEmail($customer->email) ? '' : Tools::safeOutput($customer->email),
                'phone' => Tools::safeOutput($customer->phone),
            ),
        )));
    }

    /**
     * A guest already on record: by email first, since it is unique, then by
     * phone number compared digit for digit (spaces, dashes and a leading +
     * do not matter). The most recent match is used.
     *
     * @return int id_customer, or 0
     */
    protected function findGuest($email, $phone)
    {
        $live = '`deleted` = 0 AND `active` = 1'.Shop::addSqlRestriction(Shop::SHARE_CUSTOMER);
        if ($email !== '') {
            $id = (int) Db::getInstance()->getValue(
                'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
                WHERE `email` = \''.pSQL($email).'\' AND '.$live.' ORDER BY `id_customer` DESC'
            );
            if ($id) {
                return $id;
            }
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (Tools::strlen($digits) >= 7) {
            return (int) Db::getInstance()->getValue(
                'SELECT `id_customer` FROM `'._DB_PREFIX_.'customer`
                WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(`phone`, \' \', \'\'), \'-\', \'\'), \'+\', \'\'), \'(\', \'\'), \')\', \'\') = \''.pSQL($digits).'\'
                AND '.$live.' ORDER BY `id_customer` DESC'
            );
        }

        return 0;
    }
}
