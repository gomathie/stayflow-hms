<?php
/**
 * Bookings > Reports: choose a report and a period, see it on screen,
 * download it as a PDF. Read-only: nothing is saved.
 */
class AdminSalisbergReportsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Reports');
    }

    public function initToolbar()
    {
        // read-only page: no add/save buttons
        $this->toolbar_btn = array();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn = array();
    }

    /** The choices on the form, cleaned */
    protected function getChoice()
    {
        $reports = $this->module->getReports();
        $periods = $this->module->getPeriods();
        $type = (string) Tools::getValue('sb_report', 'bookings');
        $period = (string) Tools::getValue('sb_period', 'today');
        if (!isset($reports[$type])) {
            $type = 'bookings';
        }
        if (!isset($periods[$period])) {
            $period = 'today';
        }
        list($from, $to) = $this->module->resolvePeriod($period, Tools::getValue('sb_from'), Tools::getValue('sb_to'));

        return array($type, $period, $from, $to);
    }

    public function postProcess()
    {
        if (Tools::getValue('sb_pdf')) {
            list($type, , $from, $to) = $this->getChoice();
            $this->module->downloadPdf($this->module->buildReport($type, $from, $to));
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        list($type, $period, $from, $to) = $this->getChoice();
        $self = $this->context->link->getAdminLink('AdminSalisbergReports');
        $this->context->smarty->assign(array(
            'sb_reports' => $this->module->getReports(),
            'sb_periods' => $this->module->getPeriods(),
            'sb_type' => $type,
            'sb_period' => $period,
            'sb_from' => $from,
            'sb_to' => $to,
            'sb_action' => $self,
            'sb_token' => Tools::getAdminTokenLite('AdminSalisbergReports'),
            'sb_pdf_link' => $self.'&sb_pdf=1&sb_report='.urlencode($type).'&sb_period=custom&sb_from='.urlencode($from).'&sb_to='.urlencode($to),
            'sb_report' => $this->module->buildReport($type, $from, $to),
            'sb_css' => $this->module->getPathUri().'views/css/reports.css',
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/reports.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
