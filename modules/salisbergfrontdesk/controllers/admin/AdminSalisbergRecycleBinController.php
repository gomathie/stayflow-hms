<?php
/**
 * Salisberg booking recycle bin.
 * SPDX-License-Identifier: MIT
 * Copyright (c) Salisberg
 */

class AdminSalisbergRecycleBinController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Recycle bin');
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS($this->module->getPathUri().'views/css/frontdesk.css');
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('sb_restore_booking')) {
            return parent::postProcess();
        }

        if (empty($this->tabAccess['edit'])) {
            $this->errors[] = $this->l('You do not have permission to restore bookings.');
            return;
        }

        try {
            SalisbergBookingArchive::restore(
                (int) Tools::getValue('id_order'),
                $this->context->employee
            );
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminSalisbergRecycleBin').'&sb_restored=1'
            );
        } catch (Exception $exception) {
            $this->errors[] = $exception->getMessage();
        }
    }

    public function initContent()
    {
        if (Tools::getValue('sb_restored')) {
            $this->confirmations[] = $this->l('The booking was restored.');
        }
        parent::initContent();

        $entries = SalisbergBookingArchive::getEntries($this->context->employee);
        foreach ($entries as &$entry) {
            $currency = new Currency((int) $entry['id_currency']);
            $entry['booking_total_display'] = Tools::displayPrice(
                (float) $entry['booking_total'],
                $currency
            );
            $entry['deleted_at_display'] = Tools::displayDate(
                $entry['deleted_at'],
                null,
                true
            );
        }
        unset($entry);

        $totals = SalisbergBookingArchive::getTotals($this->context->employee);
        foreach ($totals as &$total) {
            $currency = new Currency((int) $total['id_currency']);
            $total['booking_total_display'] = Tools::displayPrice(
                (float) $total['booking_total'],
                $currency
            );
        }
        unset($total);

        $this->context->smarty->assign(array(
            'sb_deleted_bookings' => $entries,
            'sb_deleted_totals' => $totals,
            'sb_can_restore' => !empty($this->tabAccess['edit']),
            'sb_recycle_bin_token' => Tools::getAdminTokenLite('AdminSalisbergRecycleBin'),
        ));
        $this->content .= $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/recycle_bin.tpl'
        );
        $this->context->smarty->assign('content', $this->content);
    }
}
