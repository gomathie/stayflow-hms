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
     * The admin guide describes pages only administrators may use, so the
     * menu permission alone is not trusted.
     */
    public function viewAccess($disable = false)
    {
        return $this->context->employee->isSuperAdmin();
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->module->renderGuide('admin_guide.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
