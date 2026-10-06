<?php
/**
 * Staff Guide
 */
class AdminSalisbergStaffGuideController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Staff Guide');
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

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->module->renderGuide('staff_guide.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
