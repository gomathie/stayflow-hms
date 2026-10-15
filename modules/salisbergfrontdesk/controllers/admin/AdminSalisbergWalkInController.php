<?php
/**
 * Guided booking workspace: Stay, Guest, Payment and Review.
 *
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 */
class AdminSalisbergWalkInController extends ModuleAdminController
{
    protected $draft;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Guided booking');
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

    protected function loadDraft()
    {
        if ($this->draft === null) {
            $this->draft = $this->module->getWalkInService()->getDraft((int) Tools::getValue('id_walkin_draft'));
        }

        return $this->draft;
    }

    public function ajaxProcessSearchGuests()
    {
        $rows = $this->module->getWalkInService()->searchGuests((string) Tools::getValue('q'));
        $this->ajaxDie(Tools::jsonEncode(array('guests' => $rows)));
    }

    protected function redirectStep($step, $extra = '')
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminSalisbergWalkIn')
            .'&id_walkin_draft='.(int) Tools::getValue('id_walkin_draft')
            .'&step='.(int) $step.$extra
        );
    }

    protected function guestFieldErrors()
    {
        if ((int) Tools::getValue('id_customer')) {
            return array();
        }
        $errors = array();
        $firstname = trim((string) Tools::getValue('firstname'));
        $lastname = trim((string) Tools::getValue('lastname'));
        $phone = trim((string) Tools::getValue('phone'));
        $email = trim((string) Tools::getValue('email'));
        if ($firstname === '' || !Validate::isName($firstname)) {
            $errors['firstname'] = $this->l('Enter a valid first name.');
        }
        if ($lastname === '' || !Validate::isName($lastname)) {
            $errors['lastname'] = $this->l('Enter a valid last name.');
        }
        if ($phone === '' || !Validate::isPhoneNumber($phone)) {
            $errors['phone'] = $this->l('Enter a valid phone number.');
        }
        if ($email !== '' && !Validate::isEmail($email)) {
            $errors['email'] = $this->l('Enter a valid email address or leave it blank.');
        }

        return $errors;
    }

    public function postProcess()
    {
        $draft = $this->loadDraft();
        if (!$draft) {
            $this->errors[] = $this->l('This booking draft could not be found.');
            return parent::postProcess();
        }
        if (!Tools::isSubmit('sb_save_stay')
            && !Tools::isSubmit('sb_save_guest')
            && !Tools::isSubmit('sb_save_payment')
            && !Tools::isSubmit('sb_finalize')
            && !Tools::isSubmit('sb_discard_draft')
        ) {
            return parent::postProcess();
        }
        if ((int) $this->tabAccess['edit'] !== 1) {
            $this->errors[] = $this->l('You do not have permission to change this booking.');
            return parent::postProcess();
        }

        $service = $this->module->getWalkInService();
        try {
            if (Tools::isSubmit('sb_discard_draft')) {
                $service->discardDraft($draft);
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminSalisbergRoomBoard').'&discarded=1'
                );
            }
            if (Tools::isSubmit('sb_save_stay')) {
                $request = $service->normaliseStay(array(
                    'arrival' => Tools::getValue('arrival'),
                    'departure' => Tools::getValue('departure'),
                    'nights' => Tools::getValue('nights'),
                    'adults' => Tools::getValue('adults'),
                    'children' => Tools::getValue('children'),
                    'id_hotel' => $draft['state']['stay']['id_hotel'],
                ));
                if ($request['errors']) {
                    throw new Exception(implode(' ', $request['errors']));
                }
                $service->saveStay(
                    $draft,
                    $request['stay'],
                    (int) Tools::getValue('id_room'),
                    (string) Tools::getValue('charge_tax', '1') === '1'
                );
                $this->redirectStep(2, '&saved=stay');
            }
            if (Tools::isSubmit('sb_save_guest')) {
                $service->saveGuest($draft, array(
                    'id_customer' => (int) Tools::getValue('id_customer'),
                    'firstname' => Tools::getValue('firstname'),
                    'lastname' => Tools::getValue('lastname'),
                    'phone' => Tools::getValue('phone'),
                    'email' => Tools::getValue('email'),
                    'company' => Tools::getValue('company'),
                    'special_requests' => Tools::getValue('special_requests'),
                ));
                $this->redirectStep(3, '&saved=guest');
            }
            if (Tools::isSubmit('sb_save_payment')) {
                $service->savePayment($draft, array(
                    'method' => Tools::getValue('method'),
                    'arrangement' => Tools::getValue('arrangement'),
                    'amount' => Tools::getValue('amount'),
                    'confirmed' => Tools::getValue('confirmed'),
                    'reference' => Tools::getValue('reference'),
                    'tendered' => Tools::getValue('tendered'),
                ));
                $this->redirectStep(4, '&saved=payment');
            }
            if (Tools::isSubmit('sb_finalize')) {
                $result = $service->finalize(
                    $draft,
                    (string) Tools::getValue('sb_finalize'),
                    (bool) Tools::getValue('accept_price')
                );
                if (!empty($result['price_changed'])) {
                    $this->redirectStep(4, '&price_changed=1');
                }
                $this->redirectStep(4, '&success=1');
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $draft = $this->loadDraft();
        if (!$draft) {
            $this->content .= '<div class="alert alert-danger">'.Tools::safeOutput($this->l('This booking draft could not be found.')).'</div>';
            $this->context->smarty->assign('content', $this->content);
            return;
        }
        $service = $this->module->getWalkInService();
        $success = $service->success($draft);
        $step = min(4, max(1, (int) Tools::getValue('step', $draft['step'])));
        if (!$success && $step > (int) $draft['step']) {
            $step = (int) $draft['step'];
        }
        $stay = $draft['state']['stay'];
        $board = $service->getRoomBoard($stay);
        $rooms = array();
        foreach ($board as $group) {
            foreach ($group['rooms'] as $room) {
                if ($room['available'] || (int) $room['id_room'] == (int) $stay['id_room']) {
                    $rooms[] = $room;
                }
            }
        }
        $taxChoice = false;
        if (Module::isEnabled('salisbergpay') && ($pay = Module::getInstanceByName('salisbergpay'))) {
            $taxChoice = $pay->taxChoiceAllowed();
        }
        $saved = (string) Tools::getValue('saved');
        $messages = array(
            'stay' => $this->l('Stay details saved.'),
            'guest' => $this->l('Guest saved.'),
            'payment' => $this->l('Payment details saved.'),
        );
        if (isset($messages[$saved])) {
            $this->confirmations[] = $messages[$saved];
        }

        $guestForm = isset($draft['state']['guest']) ? $draft['state']['guest'] : array();
        $guestFieldErrors = array();
        $duplicateGuest = false;
        if (Tools::isSubmit('sb_save_guest')) {
            $guestForm = array(
                'id_customer' => (int) Tools::getValue('id_customer'),
                'firstname' => trim((string) Tools::getValue('firstname')),
                'lastname' => trim((string) Tools::getValue('lastname')),
                'phone' => trim((string) Tools::getValue('phone')),
                'email' => trim((string) Tools::getValue('email')),
                'company' => trim((string) Tools::getValue('company')),
                'special_requests' => trim((string) Tools::getValue('special_requests')),
            );
            $guestFieldErrors = $this->guestFieldErrors();
            if (!(int) $guestForm['id_customer']) {
                $duplicateGuest = $service->findDuplicateGuest($guestForm['email'], $guestForm['phone']);
            }
        }

        $paymentForm = isset($draft['state']['payment']) ? $draft['state']['payment'] : array();
        if (Tools::isSubmit('sb_save_payment')) {
            $paymentForm = array(
                'method' => (string) Tools::getValue('method'),
                'arrangement' => (string) Tools::getValue('arrangement', 'full'),
                'intended_amount' => (string) Tools::getValue('amount'),
                'confirmed' => (bool) Tools::getValue('confirmed'),
                'reference' => trim((string) Tools::getValue('reference')),
                'cash_tendered' => (string) Tools::getValue('tendered'),
            );
        }

        $editStayLink = $this->context->link->getAdminLink('AdminSalisbergRoomBoard')
            .'&arrival='.urlencode($stay['arrival'])
            .'&departure='.urlencode($stay['departure'])
            .'&nights='.(int) $stay['nights']
            .'&adults='.(int) $stay['adults']
            .'&children='.(int) $stay['children']
            .'&id_room='.(int) $stay['id_room'];

        $this->context->smarty->assign(array(
            'sb_draft' => $draft,
            'sb_state' => $draft['state'],
            'sb_step' => $step,
            'sb_room' => $service->currentRoomSummary($draft),
            'sb_rooms' => $rooms,
            'sb_methods' => $service->paymentMethods(),
            'sb_payment' => $service->getPaymentSummary($draft['state']),
            'sb_guest_form' => $guestForm,
            'sb_guest_field_errors' => $guestFieldErrors,
            'sb_duplicate_guest' => $duplicateGuest,
            'sb_payment_form' => $paymentForm,
            'sb_tax_choice' => $taxChoice,
            'sb_success' => $success,
            'sb_is_walkin' => $stay['arrival'] == $service->getBusinessDate(),
            'sb_price_changed' => !empty($draft['state']['price_changed']),
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergWalkIn').'&id_walkin_draft='.(int) $draft['id_walkin_draft'],
            'sb_search_url' => $this->context->link->getAdminLink('AdminSalisbergWalkIn').'&ajax=1&action=searchGuests',
            'sb_board_link' => $this->context->link->getAdminLink('AdminSalisbergRoomBoard'),
            'sb_edit_stay_link' => $editStayLink,
            'sb_order_link' => $success ? $this->context->link->getAdminLink('AdminOrders').'&vieworder&id_order='.(int) $success['id_order'] : '',
            'sb_pdf_link' => $success ? $this->context->link->getAdminLink('AdminPdf').'&submitAction=generateInvoicePDF&id_order='.(int) $success['id_order'] : '',
        ));
        $this->content .= $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/walkin.tpl'
        );
        $this->context->smarty->assign('content', $this->content);
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri().'views/css/frontdesk.css');
        $this->addJS($this->module->getPathUri().'views/js/walkin.js');
    }
}
