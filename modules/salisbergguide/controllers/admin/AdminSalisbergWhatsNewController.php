<?php
/**
 * "What's New": a read-only list of recent changes, written for the people
 * who use the back office.
 *
 * Who may open it is an ordinary menu permission (Administration >
 * Permissions, row "What's New"), so it can be given to or taken from any
 * profile. The page itself shows each reader only the changes for their role.
 */
class AdminSalisbergWhatsNewController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('What\'s New');
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
        $this->content .= $this->module->renderGuide('whats_new.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
