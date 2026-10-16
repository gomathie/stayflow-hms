<?php
/**
 * Salisberg booking recycle-bin storage and lifecycle.
 * SPDX-License-Identifier: MIT
 * Copyright (c) Salisberg
 */

class SalisbergBookingArchive
{
    const TABLE = 'sb_deleted_booking';

    public static function ensureTable()
    {
        $db = Db::getInstance();
        $created = $db->execute(
            'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.self::TABLE.'` (
                `id_order` INT(10) UNSIGNED NOT NULL,
                `id_employee` INT(10) UNSIGNED NOT NULL,
                `employee_name` VARCHAR(128) NOT NULL,
                `deleted_at` DATETIME NOT NULL,
                `reason` VARCHAR(1000) NOT NULL,
                `guest_name` VARCHAR(128) NOT NULL,
                `guest_email` VARCHAR(128) NOT NULL,
                `guest_phone` VARCHAR(32) NOT NULL,
                `booking_total` DECIMAL(20,6) NOT NULL DEFAULT 0,
                `id_currency` INT(10) UNSIGNED NOT NULL,
                `hotel_ids` TEXT NOT NULL,
                `order_valid` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                `room_states` LONGTEXT NOT NULL,
                PRIMARY KEY (`id_order`),
                KEY `deleted_at` (`deleted_at`),
                KEY `id_employee` (`id_employee`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4'
        );
        if (!$created) {
            return false;
        }

        $hotelIdsColumn = $db->executeS(
            'SHOW COLUMNS FROM `'._DB_PREFIX_.self::TABLE.'` LIKE \'hotel_ids\''
        );
        if (!$hotelIdsColumn && !$db->execute(
            'ALTER TABLE `'._DB_PREFIX_.self::TABLE.'` ADD `hotel_ids` TEXT NULL'
        )) {
            return false;
        }

        $archivesWithoutHotels = $db->executeS(
            'SELECT `id_order` FROM `'._DB_PREFIX_.self::TABLE.'`
            WHERE `hotel_ids` IS NULL OR `hotel_ids` = \'\''
        );
        foreach ((array) $archivesWithoutHotels as $archive) {
            $hotelIds = self::getHotelIdsForOrder((int) $archive['id_order']);
            if ($hotelIds && !$db->update(
                self::TABLE,
                array('hotel_ids' => implode(',', $hotelIds)),
                '`id_order` = '.(int) $archive['id_order']
            )) {
                return false;
            }
        }

        return true;
    }

    public static function isArchived($idOrder)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT `id_order` FROM `'._DB_PREFIX_.self::TABLE.'`
            WHERE `id_order` = '.(int) $idOrder
        );
    }

    public static function archive($idOrder, $idEmployee, $reason)
    {
        $idOrder = (int) $idOrder;
        $idEmployee = (int) $idEmployee;
        $reason = trim(strip_tags((string) $reason));
        if (!$idOrder || !$idEmployee || $reason === '' || Tools::strlen($reason) > 1000) {
            throw new PrestaShopException('A valid booking and a deletion reason of at most 1000 characters are required.');
        }

        $db = Db::getInstance();
        if (!$db->execute('START TRANSACTION')) {
            throw new PrestaShopException('Could not start the booking archive transaction.');
        }

        try {
            $lockedOrder = $db->executeS(
                'SELECT `id_order` FROM `'._DB_PREFIX_.'orders`
                WHERE `id_order` = '.$idOrder.' FOR UPDATE'
            );
            if (!$lockedOrder) {
                throw new PrestaShopException('The booking no longer exists.');
            }
            if (self::isArchived($idOrder)) {
                throw new PrestaShopException('This booking is already in the recycle bin.');
            }

            $order = new Order($idOrder);
            if (!Validate::isLoadedObject($order)) {
                throw new PrestaShopException('The booking could not be loaded.');
            }

            $hotelIds = self::getHotelIdsForOrder($idOrder);
            if (!$hotelIds) {
                throw new PrestaShopException('This booking has no hotel association and cannot be archived safely.');
            }
            $employee = new Employee($idEmployee);
            if (!Validate::isLoadedObject($employee)
                || !self::employeeCanAccessHotels($employee, $hotelIds)) {
                throw new PrestaShopException('You do not have permission to archive this booking.');
            }

            $customer = new Customer((int) $order->id_customer);
            $guestName = Validate::isLoadedObject($customer)
                ? trim($customer->firstname.' '.$customer->lastname)
                : '';
            $guestEmail = Validate::isLoadedObject($customer) ? (string) $customer->email : '';
            $guestPhone = '';
            if (OrderCustomerGuestDetail::isCustomerGuestBooking($idOrder)) {
                $guest = Db::getInstance()->getRow(
                    'SELECT `firstname`, `lastname`, `email`, `phone`
                    FROM `'._DB_PREFIX_.'order_customer_guest_detail`
                    WHERE `id_order` = '.$idOrder
                );
                if ($guest) {
                    $guestName = trim($guest['firstname'].' '.$guest['lastname']);
                    $guestEmail = (string) $guest['email'];
                    $guestPhone = (string) $guest['phone'];
                }
            }
            if ($guestPhone === '' && Validate::isLoadedObject($customer)) {
                $guestPhone = (string) $customer->phone;
            }

            $employeeName = Validate::isLoadedObject($employee)
                ? trim($employee->firstname.' '.$employee->lastname)
                : (string) $idEmployee;
            $roomRows = $db->executeS(
                'SELECT `id`, `is_cancelled`, `is_refunded`, `is_back_order`
                FROM `'._DB_PREFIX_.'htl_booking_detail`
                WHERE `id_order` = '.$idOrder
            );
            $roomStates = array();
            foreach ((array) $roomRows as $roomRow) {
                $roomStates[] = array(
                    'id' => (int) $roomRow['id'],
                    'is_cancelled' => (int) $roomRow['is_cancelled'],
                );
            }

            $inserted = $db->insert(self::TABLE, array(
                'id_order' => $idOrder,
                'id_employee' => $idEmployee,
                'employee_name' => $employeeName,
                'deleted_at' => date('Y-m-d H:i:s'),
                'reason' => $reason,
                'guest_name' => $guestName,
                'guest_email' => $guestEmail,
                'guest_phone' => $guestPhone,
                'booking_total' => (float) $order->total_paid_tax_incl,
                'id_currency' => (int) $order->id_currency,
                'hotel_ids' => implode(',', $hotelIds),
                'order_valid' => (int) $order->valid,
                'room_states' => Tools::jsonEncode($roomStates),
            ));
            if (!$inserted) {
                throw new PrestaShopException('The booking archive record could not be saved.');
            }

            if (!$db->execute(
                'UPDATE `'._DB_PREFIX_.'orders` SET `valid` = 0
                WHERE `id_order` = '.$idOrder
            )) {
                throw new PrestaShopException('The booking could not be removed from active totals.');
            }
            if ($roomRows && !$db->execute(
                'UPDATE `'._DB_PREFIX_.'htl_booking_detail`
                SET `is_cancelled` = 1 WHERE `id_order` = '.$idOrder
            )) {
                throw new PrestaShopException('The booking rooms could not be released.');
            }
            if (!$db->execute('COMMIT')) {
                throw new PrestaShopException('The booking archive transaction could not be committed.');
            }
        } catch (Exception $exception) {
            $db->execute('ROLLBACK');
            throw $exception;
        }
    }

    public static function restore($idOrder, Employee $employee)
    {
        $idOrder = (int) $idOrder;
        if (!$idOrder) {
            throw new PrestaShopException('A valid booking is required.');
        }

        $db = Db::getInstance();
        if (!$db->execute('START TRANSACTION')) {
            throw new PrestaShopException('Could not start the restore transaction.');
        }

        try {
            $archiveRows = $db->executeS(
                'SELECT * FROM `'._DB_PREFIX_.self::TABLE.'`
                WHERE `id_order` = '.$idOrder.' FOR UPDATE'
            );
            if (!$archiveRows) {
                throw new PrestaShopException('This booking is not in the recycle bin.');
            }
            $archive = $archiveRows[0];
            $hotelIds = self::parseHotelIds($archive['hotel_ids']);
            if (!$hotelIds || !self::employeeCanAccessHotels($employee, $hotelIds)) {
                throw new PrestaShopException('You do not have permission to restore this booking.');
            }
            $orderRows = $db->executeS(
                'SELECT `id_order` FROM `'._DB_PREFIX_.'orders`
                WHERE `id_order` = '.$idOrder.' FOR UPDATE'
            );
            if (!$orderRows) {
                throw new PrestaShopException('The original booking no longer exists and cannot be restored.');
            }

            $roomRows = $db->executeS(
                'SELECT `id`, `id_room`, `date_from`, `date_to`, `is_refunded`, `is_back_order`
                FROM `'._DB_PREFIX_.'htl_booking_detail`
                WHERE `id_order` = '.$idOrder.' AND `id_room` > 0'
            );
            $roomStates = Tools::jsonDecode($archive['room_states'], true);
            if (!is_array($roomStates)) {
                throw new PrestaShopException('The saved room status information is invalid; this booking cannot be restored safely.');
            }
            $originalRoomStates = array();
            foreach ($roomStates as $roomState) {
                if (isset($roomState['id'])) {
                    $originalRoomStates[(int) $roomState['id']] = $roomState;
                }
            }
            foreach ($roomRows as $roomRow) {
                $originalState = isset($originalRoomStates[(int) $roomRow['id']])
                    ? $originalRoomStates[(int) $roomRow['id']]
                    : array();
                if (!isset($originalState['is_cancelled']) || (int) $originalState['is_cancelled']
                    || (int) $roomRow['is_refunded'] || (int) $roomRow['is_back_order']) {
                    continue;
                }
                if (strtotime($roomRow['date_to']) <= strtotime(date('Y-m-d'))) {
                    continue;
                }
                $conflicts = $db->executeS(
                    'SELECT `id` FROM `'._DB_PREFIX_.'htl_booking_detail`
                    WHERE `id_room` = '.(int) $roomRow['id_room'].'
                    AND `id_order` != '.$idOrder.'
                    AND `is_cancelled` = 0 AND `is_refunded` = 0 AND `is_back_order` = 0
                    AND `date_from` < \''.pSQL($roomRow['date_to']).'\'
                    AND `date_to` > \''.pSQL($roomRow['date_from']).'\'
                    LIMIT 1'
                );
                if ($conflicts) {
                    throw new PrestaShopException('The booking cannot be restored because one of its rooms has another active booking for the same dates.');
                }
            }

            foreach ($roomStates as $roomState) {
                $idRoomBooking = isset($roomState['id']) ? (int) $roomState['id'] : 0;
                if (!$idRoomBooking || !$db->execute(
                    'UPDATE `'._DB_PREFIX_.'htl_booking_detail`
                    SET `is_cancelled` = '.(isset($roomState['is_cancelled']) ? (int) $roomState['is_cancelled'] : 0).'
                    WHERE `id` = '.$idRoomBooking.' AND `id_order` = '.$idOrder
                )) {
                    throw new PrestaShopException('A room status could not be restored safely.');
                }
            }

            if (!$db->execute(
                'UPDATE `'._DB_PREFIX_.'orders` SET `valid` = '.(int) $archive['order_valid'].'
                WHERE `id_order` = '.$idOrder
            )) {
                throw new PrestaShopException('The booking could not be returned to active totals.');
            }
            if (!$db->delete(self::TABLE, '`id_order` = '.$idOrder)) {
                throw new PrestaShopException('The recycle-bin record could not be removed.');
            }
            if (!$db->execute('COMMIT')) {
                throw new PrestaShopException('The restore transaction could not be committed.');
            }
        } catch (Exception $exception) {
            $db->execute('ROLLBACK');
            throw $exception;
        }
    }

    public static function getEntries(Employee $employee)
    {
        $scope = self::getEmployeeArchiveScope($employee, 'archive');
        return Db::getInstance()->executeS(
            'SELECT * FROM `'._DB_PREFIX_.self::TABLE.'` archive
            WHERE 1 = 1'.$scope.'
            ORDER BY `deleted_at` DESC, `id_order` DESC'
        );
    }

    public static function getTotals(Employee $employee)
    {
        $scope = self::getEmployeeArchiveScope($employee, 'archive');
        return Db::getInstance()->executeS(
            'SELECT `id_currency`, COUNT(*) AS `booking_count`,
                SUM(`booking_total`) AS `booking_total`
            FROM `'._DB_PREFIX_.self::TABLE.'` archive
            WHERE 1 = 1'.$scope.'
            GROUP BY `id_currency`
            ORDER BY `id_currency`'
        );
    }

    private static function getHotelIdsForOrder($idOrder)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `id_hotel` FROM `'._DB_PREFIX_.'htl_booking_detail`
            WHERE `id_order` = '.(int) $idOrder.' AND `id_hotel` > 0
            UNION
            SELECT `id_hotel` FROM `'._DB_PREFIX_.'service_product_order_detail`
            WHERE `id_order` = '.(int) $idOrder.' AND `id_hotel` > 0'
        );
        $hotelIds = array();
        foreach ((array) $rows as $row) {
            $hotelIds[] = (int) $row['id_hotel'];
        }
        return array_values(array_unique($hotelIds));
    }

    private static function parseHotelIds($hotelIds)
    {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $hotelIds)))));
    }

    private static function employeeCanAccessHotels(Employee $employee, array $hotelIds)
    {
        if ($employee->isSuperAdmin()) {
            return true;
        }
        $accessible = HotelBranchInformation::getProfileAccessedHotels(
            (int) $employee->id_profile,
            1,
            1
        );
        return !array_diff(array_map('intval', $hotelIds), array_map('intval', (array) $accessible));
    }

    private static function getEmployeeArchiveScope(Employee $employee, $tableAlias)
    {
        if ($employee->isSuperAdmin()) {
            return '';
        }
        $accessible = HotelBranchInformation::getProfileAccessedHotels(
            (int) $employee->id_profile,
            1,
            1
        );
        $conditions = array();
        foreach ((array) $accessible as $idHotel) {
            $conditions[] = 'IF(FIND_IN_SET('.(int) $idHotel.', `'.$tableAlias.'`.`hotel_ids`) > 0, 1, 0)';
        }
        if (!$conditions) {
            return ' AND 1 = 0';
        }
        $hotelCount = '(LENGTH(`'.$tableAlias.'`.`hotel_ids`) - LENGTH(REPLACE(`'.$tableAlias.'`.`hotel_ids`, \',\', \'\')) + 1)';
        return ' AND ('.implode(' + ', $conditions).') = '.$hotelCount;
    }
}
