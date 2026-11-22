<?php
/**
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 *
 * A room's own price.
 *
 * Rooms of one room type normally share the room type's price. A room can carry
 * its own price (a room with a fridge and a steam iron at 400 beside others at
 * 300). A room with no price of its own takes the room type's price.
 *
 * Entered in Rooms & Services > Manage Room Types > (a room type) > Rooms, in a
 * column added to that table through the platform's room-list hooks; kept in the
 * table sb_room_rate (one row per room that has a price). What distinguishes the
 * room (fridge, steam iron) is written in that table's existing Extra Information
 * box, which the Room board shows on the room's card.
 *
 * Where the price applies: to bookings made at the front desk (New booking),
 * where staff choose a particular room. The website sells a room type at the
 * room type's price: a guest does not choose a room there, so the price they
 * were shown must not change with the room the hotel gives them.
 */
class SalisbergRoomRate
{
    public static function ensureTable()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'sb_room_rate` (
                `id_room` INT UNSIGNED NOT NULL,
                `price` DECIMAL(20,6) NOT NULL,
                `date_upd` DATETIME NOT NULL,
                PRIMARY KEY (`id_room`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8'
        );
    }

    /** @return float|null the room's own price, or null when it takes the room type's price */
    public static function get($idRoom)
    {
        $price = Db::getInstance()->getValue(
            'SELECT `price` FROM `'._DB_PREFIX_.'sb_room_rate` WHERE `id_room` = '.(int) $idRoom
        );

        return $price === false || $price === null ? null : (float) $price;
    }

    /**
     * Reads what was typed in the Own price box: null for an empty box (the room
     * takes the room type's price), a number, or false when it is not a valid price.
     */
    public static function parsePrice($raw)
    {
        $raw = str_replace(array(' ', ','), array('', '.'), trim((string) $raw));
        if ($raw === '') {
            return null;
        }
        if (!preg_match('/^\d{1,9}(\.\d{1,2})?$/', $raw) || (float) $raw <= 0) {
            return false;
        }

        return (float) $raw;
    }

    /** @return string[] messages for the controller's error list; empty when the price is fine */
    public static function validate(array $roomInfo, $label)
    {
        if (isset($roomInfo['sb_price']) && self::parsePrice($roomInfo['sb_price']) === false) {
            return array(sprintf(Tools::displayError('The own price of room %s is not valid. Enter a number such as 400 or 350.50, or leave it empty to use the room type price.'), $label));
        }

        return array();
    }

    /** Stores a room's own price; null removes it, so the room takes the room type's price again. */
    public static function save($idRoom, $price)
    {
        if ($price === null) {
            return self::delete($idRoom);
        }

        return Db::getInstance()->execute(
            'REPLACE INTO `'._DB_PREFIX_.'sb_room_rate` (`id_room`, `price`, `date_upd`)
            VALUES ('.(int) $idRoom.', '.(float) $price.', NOW())'
        );
    }

    public static function delete($idRoom)
    {
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'sb_room_rate` WHERE `id_room` = '.(int) $idRoom);
    }

    /**
     * Hook actionRoomTypeTotalPriceModifier. The platform has just added up the
     * room type's price for the nights; when the room is known and has a price of
     * its own, the difference between that price and the room type's price is
     * added for every night, with the same tax rate. The room's price therefore
     * replaces the room type's regular price, and special prices set for the room
     * type on certain dates (a season, a promotion) still apply on top of it.
     * Only for staff at the front desk, see the note at the top of this file.
     */
    public static function adjustTotals(array $params)
    {
        $idRoom = (int) $params['id_room'];
        $employee = Context::getContext()->employee;
        if (!$idRoom || !Validate::isLoadedObject($employee) || empty($params['total_prices'])) {
            return;
        }
        $price = self::get($idRoom);
        if ($price === null) {
            return;
        }
        $nothing = null;
        // the room type's regular price per night before tax, read the way the platform reads it
        $typeExcl = (float) Product::getPriceStatic(
            (int) $params['id_room_type'], 0, 0, 6, null, 0, (int) $params['use_reduc'], 1, 0,
            null, null, null, $nothing, 1, 1, null, 1, 0, 0, (int) $params['id_group']
        );
        $own = (float) Tools::convertPrice($price, (int) $params['id_currency']);
        $nights = (int) round((strtotime(date('Y-m-d', strtotime($params['date_to']))) - strtotime(date('Y-m-d', strtotime($params['date_from'])))) / 86400);
        $delta = ($own - $typeExcl) * max(1, $nights);
        $totals = &$params['total_prices'];
        $totals['total_price_tax_excl'] = max(0, $totals['total_price_tax_excl'] + $delta);
        $totals['total_price_tax_incl'] = max(0, $totals['total_price_tax_incl'] + $delta * (1 + (float) $params['tax_rate'] / 100));
    }
}
