<?php
// A room's own price (Rooms tab, Own price) replaces the room type's price at
// the desk, where staff choose the room, but never on the website, where the
// guest pays the room type price whichever room they get (AGENTS.md 2026-10-19).
require_once _PS_MODULE_DIR_.'salisbergfrontdesk/classes/SalisbergRoomRate.php';

function sb_room_total($idProduct, $idRoom, $from, $to)
{
    $t = HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice($idProduct, $from, $to, null, 0, 0, 0, $idRoom);
    return round((float) $t['total_price_tax_excl'], 2);
}

return array(
    'own room price: used at the desk, not on the website' => function () {
        $room = Db::getInstance()->getRow(
            'SELECT ri.`id` AS id_room, ri.`id_product` FROM `'._DB_PREFIX_.'htl_room_information` ri
            INNER JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = ri.`id_product` AND p.`active` = 1
            WHERE NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'sb_room_rate` r WHERE r.`id_room` = ri.`id`)
            ORDER BY ri.`id`', false
        );
        check(!empty($room['id_room']), 'precondition: a room without an own price exists');
        $from = date('Y-m-d', strtotime('+400 days'));
        $to = date('Y-m-d', strtotime('+402 days')); // two nights
        $ctx = Context::getContext();
        $employee = $ctx->employee;

        rolledBack(function () use ($room, $from, $to, $ctx, $employee) {
            $ctx->employee = null;
            $website = sb_room_total($room['id_product'], $room['id_room'], $from, $to);
            check($website > 0, 'precondition: the room type has a price');
            $perNight = $website / 2;

            SalisbergRoomRate::save((int) $room['id_room'], $perNight + 100);
            same($website, sb_room_total($room['id_product'], $room['id_room'], $from, $to),
                'website (no employee): still the room type price');

            $ctx->employee = new Employee(1);
            same(round($website + 200, 2), sb_room_total($room['id_product'], $room['id_room'], $from, $to),
                'desk (employee signed in): the own price, 100 more per night for 2 nights');
        });
        $ctx->employee = $employee;
    },
);
