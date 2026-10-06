<?php
/**
 * Admin Guide
 */
class AdminSalisbergAdminGuideController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Admin Guide');
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

    /**
     * The admin guide describes pages only managers and administrators may
     * use, so the menu permission alone is not trusted.
     */
    public function viewAccess($disable = false)
    {
        return $this->module->canReadAdminGuide();
    }

    /**
     * Section 15 of the guide: a SuperAdmin shows or hides menu sections.
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitSbMenu') && $this->context->employee->isSuperAdmin()) {
            foreach (array_keys(Salisbergguide::$menuToggles) as $className) {
                $this->module->setMenuVisible($className, (int) Tools::getValue('sb_menu_'.$className) === 1);
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergAdminGuide').'&conf=6#sb-a15');
        }

        return parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->module->renderGuide('admin_guide.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
