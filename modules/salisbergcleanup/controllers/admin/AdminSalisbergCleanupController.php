<?php
/**
 * Advanced Parameters > Data clean-up. SuperAdmin only.
 *
 * Choose a kind and an age, see how many items would go and a few examples,
 * then type that number to remove them. Nothing is removed by the preview.
 */
class AdminSalisbergCleanupController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Data clean-up');
    }

    public function viewAccess($disable = false)
    {
        return $this->context->employee && $this->context->employee->isSuperAdmin();
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

    /** The kind and age on the form, cleaned; the age never below the kind's minimum */
    protected function getChoice()
    {
        $kinds = SalisbergDataCleanup::kinds();
        $kind = (string) Tools::getValue('sb_kind', 'drafts');
        if (!isset($kinds[$kind])) {
            $kind = 'drafts';
        }
        $min = isset($kinds[$kind]['min']) ? (int) $kinds[$kind]['min'] : 1;
        $days = (int) Tools::getValue('sb_days', $kinds[$kind]['default_days']);
        if ($days < $min) {
            $days = $min;
        }
        if ($days > 3650) {
            $days = 3650;
        }

        return array($kind, $days, SalisbergDataCleanup::cutoff($days, $min));
    }

    public function postProcess()
    {
        if (!$this->viewAccess()) {
            return false;
        }
        if (Tools::isSubmit('sb_clean') || Tools::isSubmit('sb_check') || Tools::isSubmit('sb_auto')) {
            if (Tools::getValue('token') !== Tools::getAdminTokenLite('AdminSalisbergCleanup')) {
                $this->errors[] = $this->l('The page had expired. Please try again.');
                return false;
            }
        }

        if (Tools::isSubmit('sb_auto')) {
            Configuration::updateValue('SBCLEAN_AUTO', (int) Tools::getValue('sb_auto_on'));
            $this->confirmations[] = $this->l('Automatic clean-up saved.');
        }

        if (Tools::isSubmit('sb_check')) {
            $logs = Salisbergcleanup::checkAndFix();
            Hook::exec('actionCleanData', array('method' => 'checkAndFix'));
            Cache::getInstance()->flush();
            if ($logs) {
                $this->context->smarty->assign('logs', $logs);
                $this->confirmations[] = $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/integrity.tpl');
            } else {
                $this->confirmations[] = $this->l('The database check found nothing to fix.');
            }
        }

        if (Tools::isSubmit('sb_clean')) {
            list($kind, $days, $cutoff) = $this->getChoice();
            $kinds = SalisbergDataCleanup::kinds();
            $expected = SalisbergDataCleanup::count($kind, $cutoff);
            $typed = trim((string) Tools::getValue('sb_confirm'));
            if (!empty($kinds[$kind]['locked'])) {
                $this->errors[] = $this->l('This kind cannot be removed from here yet.');
            } elseif (!$expected) {
                $this->errors[] = $this->l('There is nothing to remove for this choice.');
            } elseif ($typed !== (string) $expected) {
                $this->errors[] = sprintf($this->l('To confirm, type the number of items that will be removed: %d.'), $expected);
            } else {
                $employee = $this->context->employee;
                $done = SalisbergDataCleanup::remove($kind, $cutoff, trim($employee->firstname.' '.$employee->lastname).' (#'.(int) $employee->id.')');
                $left = SalisbergDataCleanup::count($kind, $cutoff);
                $this->confirmations[] = sprintf($this->l('%d removed.'), $done['removed'])
                    .($done['failed'] ? ' '.sprintf($this->l('%d could not be removed; see Advanced Parameters, Logs.'), $done['failed']) : '')
                    .($left ? ' '.sprintf($this->l('%d more remain: run it again to continue.'), $left) : '');
            }
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        list($kind, $days, $cutoff) = $this->getChoice();
        $kinds = SalisbergDataCleanup::kinds();
        $summary = array();
        foreach ($kinds as $key => $def) {
            $c = SalisbergDataCleanup::cutoff($def['default_days'], isset($def['min']) ? $def['min'] : 1);
            $summary[$key] = array('name' => $def['name'], 'days' => $def['default_days'], 'auto' => $def['auto'],
                'locked' => !empty($def['locked']), 'count' => SalisbergDataCleanup::count($key, $c));
        }
        $this->context->smarty->assign(array(
            'sb_kinds' => $kinds,
            'sb_kind' => $kind,
            'sb_days' => $days,
            'sb_min' => isset($kinds[$kind]['min']) ? (int) $kinds[$kind]['min'] : 1,
            'sb_cutoff' => date('j M Y', strtotime($cutoff)),
            'sb_count' => SalisbergDataCleanup::count($kind, $cutoff),
            'sb_sample' => SalisbergDataCleanup::sample($kind, $cutoff),
            'sb_summary' => $summary,
            'sb_auto_on' => (int) Configuration::get('SBCLEAN_AUTO'),
            'sb_last_auto' => (string) Configuration::get('SBCLEAN_LAST_AUTO'),
            'sb_keep_years' => SalisbergDataCleanup::KEEP_YEARS,
            'sb_bin_days' => SalisbergDataCleanup::BIN_DAYS,
            'sb_action' => $this->context->link->getAdminLink('AdminSalisbergCleanup'),
            'sb_token' => Tools::getAdminTokenLite('AdminSalisbergCleanup'),
            'sb_css' => $this->module->getPathUri().'views/css/cleanup.css?v=1',
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/cleanup.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
