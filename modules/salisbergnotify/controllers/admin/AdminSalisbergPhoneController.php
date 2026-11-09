<?php
/**
 * A hidden page any signed-in employee may post their own phone to, answering the
 * login prompt. It saves only the current employee's number (never a posted id),
 * so a Hotel Staff member who cannot open Integrations can still add their phone.
 * The admin token the dispatcher requires is the CSRF guard.
 */
class AdminSalisbergPhoneController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    /** Any signed-in employee, whatever their profile. */
    public function viewAccess($disable = false)
    {
        return (bool) ($this->context->employee && $this->context->employee->id);
    }

    public function ajaxProcessSave()
    {
        $employee = $this->context->employee;
        if (!Validate::isLoadedObject($employee)) {
            die(Tools::jsonEncode(array('ok' => false, 'error' => $this->l('Please sign in again.'))));
        }
        $phone = trim((string) Tools::getValue('phone'));
        if ($phone === '' || !Validate::isPhoneNumber($phone)) {
            die(Tools::jsonEncode(array('ok' => false, 'error' => $this->l('Enter a valid phone number.'))));
        }
        $this->module->setEmpPhone((int) $employee->id, $phone);
        die(Tools::jsonEncode(array('ok' => true)));
    }
}
