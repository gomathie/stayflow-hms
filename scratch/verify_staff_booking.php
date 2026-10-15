<?php
require '/var/www/html/config/config.inc.php';

$idDraft = isset($argv[1]) ? (int) $argv[1] : 0;
$idOrder = isset($argv[2]) ? (int) $argv[2] : 0;
$row = Db::getInstance()->getRow(
    'SELECT d.`status` AS `draft_status`, d.`id_employee`, e.`email` AS `employee_email`,
        d.`id_order`, o.`reference`, o.`current_state`, osl.`name` AS `order_state`,
        o.`payment`, o.`total_paid_tax_incl`, o.`total_paid_real`,
        b.`id` AS `id_booking`, b.`id_status` AS `booking_status`, b.`room_num`, b.`date_from`, b.`date_to`,
        c.`firstname`, c.`lastname`, c.`phone`, c.`email` AS `guest_email`
    FROM `'._DB_PREFIX_.'sb_walkin_draft` d
    INNER JOIN `'._DB_PREFIX_.'employee` e ON e.`id_employee` = d.`id_employee`
    INNER JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = d.`id_order`
    LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON osl.`id_order_state` = o.`current_state`
        AND osl.`id_lang` = '.(int) Configuration::get('PS_LANG_DEFAULT').'
    INNER JOIN `'._DB_PREFIX_.'htl_booking_detail` b ON b.`id_order` = o.`id_order`
    INNER JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`
    WHERE d.`id_walkin_draft` = '.$idDraft.' AND o.`id_order` = '.$idOrder
);
echo Tools::jsonEncode($row).PHP_EOL;
if (!$row
    || $row['draft_status'] !== 'complete'
    || $row['employee_email'] !== 'staff.test@example.com'
    || $row['payment'] !== 'Pay later'
    || (float) $row['total_paid_real'] !== 0.0
    || $row['firstname'] !== 'Hotel'
    || $row['lastname'] !== 'Staff Test'
    || (int) $row['booking_status'] !== (int) HotelBookingDetail::STATUS_ALLOTED
) {
    exit(1);
}
