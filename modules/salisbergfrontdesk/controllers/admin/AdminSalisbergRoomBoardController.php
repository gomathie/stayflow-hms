<?php
/**
 * Bookings > Room board: availability for a selected stay and the entry
 * point into the guided same-day walk-in flow.
 *
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 */
class AdminSalisbergRoomBoardController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Room board');
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

    protected function requestedStay()
    {
        $service = $this->module->getWalkInService();
        $defaults = $service->defaultStay();
        $values = array(
            'arrival' => Tools::getValue('arrival', $defaults['arrival']),
            'departure' => Tools::getValue('departure', $defaults['departure']),
            'nights' => Tools::getValue('nights', $defaults['nights']),
            'adults' => Tools::getValue('adults', $defaults['adults']),
            'children' => Tools::getValue('children', $defaults['children']),
            'id_hotel' => Tools::getValue('id_hotel', $defaults['id_hotel']),
        );

        return $service->normaliseStay($values);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('sb_start_booking')) {
            if ((int) $this->tabAccess['edit'] !== 1) {
                $this->errors[] = $this->l('You do not have permission to create a booking.');
                return parent::postProcess();
            }
            $request = $this->requestedStay();
            foreach ($request['errors'] as $error) {
                $this->errors[] = $error;
            }
            $idRoom = (int) Tools::getValue('id_room');
            if (!$idRoom) {
                $this->errors[] = $this->l('Select a room first.');
            }
            if (!$this->errors) {
                try {
                    $service = $this->module->getWalkInService();
                    $draft = $service->createDraft($request['stay'], $idRoom);
                    Tools::redirectAdmin(
                        $this->context->link->getAdminLink('AdminSalisbergWalkIn')
                        .'&id_walkin_draft='.(int) $draft['id_walkin_draft'].'&step=2'
                    );
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $service = $this->module->getWalkInService();
        $request = $this->requestedStay();
        $groups = array();
        if (!$request['errors']) {
            $groups = $service->getRoomBoard($request['stay']);
        }
        $selected = (int) Tools::getValue('id_room');
        $selectedRoom = false;
        foreach ($groups as $group) {
            foreach ($group['rooms'] as $room) {
                if (($selected && (int) $room['id_room'] == $selected) || (!$selectedRoom && $room['available'])) {
                    $selectedRoom = $room;
                    if ($selected) {
                        break 2;
                    }
                }
            }
        }

        $this->context->smarty->assign(array(
            'sb_stay' => $request['stay'],
            'sb_field_errors' => $request['errors'],
            'sb_groups' => $groups,
            'sb_selected_room' => $selectedRoom,
            'sb_business_date' => $service->getBusinessDate(),
            'sb_is_walkin' => $request['stay']['arrival'] == $service->getBusinessDate(),
            'sb_can_book' => (int) $this->tabAccess['edit'] === 1,
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergRoomBoard'),
            'sb_today_link' => $this->context->link->getAdminLink('AdminDashboard').'#sb-arrivals',
            'sb_housekeeping_link' => $this->context->link->getAdminLink('AdminSalisbergHousekeeping'),
        ));
        $this->content .= $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/room_board.tpl'
        );
        $this->context->smarty->assign('content', $this->content);
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri().'views/css/frontdesk.css');
        $this->addJS($this->module->getPathUri().'views/js/room-board.js');
    }
}
