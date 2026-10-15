<?php
/**
 * Bookings > Housekeeping: every room with its state (clean, dirty, out of
 * service) and buttons to change it. Changing anything needs the Edit right
 * on this page.
 */
class AdminSalisbergHousekeepingController extends ModuleAdminController
{
    /** Button on the page => the state it sets */
    protected $states = array('clean', 'dirty', 'out', 'back');

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = $this->l('Housekeeping');
    }

    public function initToolbar()
    {
        // no add/save buttons: the buttons are on the rows
        $this->toolbar_btn = array();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $this->page_header_toolbar_btn = array();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('sb_room_state')) {
            $state = (string) Tools::getValue('sb_room_state');
            if ((int) $this->tabAccess['edit'] !== 1) {
                $this->errors[] = $this->l('You do not have permission to change this.');
            } elseif (!in_array($state, $this->states, true)) {
                $this->errors[] = $this->l('This room could not be found.');
            } elseif ($error = $this->module->setRoomState((int) Tools::getValue('id_room'), $state)) {
                $this->errors[] = $error;
            } else {
                // back to the page with a plain address, so a refresh cannot repeat the change
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminSalisbergHousekeeping')
                    .'&sb_done='.$state.'&sb_show='.urlencode($this->getFilter())
                );
            }
        }

        return parent::postProcess();
    }

    /** Which rooms to list: all, or one state */
    protected function getFilter()
    {
        $show = (string) Tools::getValue('sb_show', 'all');

        return in_array($show, array('all', 'clean', 'dirty', 'out'), true) ? $show : 'all';
    }

    public function initContent()
    {
        parent::initContent();
        $messages = array(
            'clean' => $this->l('The room is marked clean.'),
            'dirty' => $this->l('The room is marked dirty.'),
            'out' => $this->l('The room is out of service and is no longer offered to guests.'),
            'back' => $this->l('The room is back in service.'),
        );
        $done = (string) Tools::getValue('sb_done');
        if (isset($messages[$done])) {
            $this->confirmations[] = $messages[$done];
        }

        $rooms = $this->module->getRooms();
        $counts = array('all' => count($rooms), 'clean' => 0, 'dirty' => 0, 'out' => 0);
        foreach ($rooms as $room) {
            ++$counts[$room['state']];
        }
        $show = $this->getFilter();
        if ($show != 'all') {
            foreach ($rooms as $key => $room) {
                if ($room['state'] != $show) {
                    unset($rooms[$key]);
                }
            }
        }

        $self = $this->context->link->getAdminLink('AdminSalisbergHousekeeping');
        $this->context->smarty->assign(array(
            'sb_rooms' => $rooms,
            'sb_counts' => $counts,
            'sb_show' => $show,
            'sb_can_edit' => (int) $this->tabAccess['edit'] === 1,
            'sb_action' => $self,
            'sb_today_link' => $this->context->link->getAdminLink('AdminDashboard').'#sb-arrivals',
            'sb_css' => $this->module->getPathUri().'views/css/frontdesk.css',
        ));
        $this->content .= $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/housekeeping.tpl');
        $this->context->smarty->assign('content', $this->content);
    }
}
