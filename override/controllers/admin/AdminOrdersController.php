<?php
/**
 * The old AdminOrders cart form is no longer a booking entry. Room board and
 * its guided flow own new bookings; existing orders still use this controller.
 */
class AdminOrdersController extends AdminOrdersControllerCore
{
    public function init()
    {
        parent::init();
        if (Tools::isSubmit('addorder') || Tools::isSubmit('submitAddOrder')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminSalisbergRoomBoard'));
        }
    }
}
