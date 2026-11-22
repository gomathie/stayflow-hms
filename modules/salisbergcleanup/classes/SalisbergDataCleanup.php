<?php
/**
 * Data clean-up: removes old data on purpose, by kind and by date.
 *
 * Owner rules (CLEANUP-PLAN.md, 2026-11-20):
 *  - paid bookings and invoices are kept for 6 years (KEEP_YEARS);
 *  - a deleted booking stays in the recycle bin for 90 days (BIN_DAYS) first;
 *  - unfinished drafts and abandoned carts older than 30 days and logs older
 *    than 90 days are cleaned automatically, once a day (runAutomatic()).
 *
 * Every kind has the same three parts: how many would go (count), a few
 * examples (sample) and the removal itself (remove). Nothing here touches a
 * booking that exists, a guest who has one, or a cart holding a booking.
 */
class SalisbergDataCleanup
{
    const KEEP_YEARS = 6;
    const BIN_DAYS = 90;
    const SAMPLE = 8;
    /** Most rows removed in one run, so a request never runs for minutes */
    const BATCH = 500;

    /** @return array kind => definition shown on the page */
    public static function kinds()
    {
        return array(
            'drafts' => array('name' => 'Unfinished booking drafts', 'auto' => 30, 'default_days' => 30, 'min' => 2,
                'help' => 'Bookings started on New booking and never finished. Removing one releases the room it was holding.'),
            'carts' => array('name' => 'Abandoned carts', 'auto' => 30, 'default_days' => 30, 'min' => 2,
                'help' => 'Website and back office carts that never became a booking. Rooms they held are released.'),
            'logs' => array('name' => 'System logs', 'auto' => 90, 'default_days' => 90, 'min' => 7,
                'help' => 'Lines under Advanced Parameters, Logs.'),
            'messages' => array('name' => 'Closed Inbox conversations', 'auto' => 0, 'default_days' => 365, 'min' => 30,
                'help' => 'Contact form conversations marked Closed. Open ones are never removed.'),
            'guests' => array('name' => 'Guests with no bookings', 'auto' => 0, 'default_days' => 365, 'min' => 90,
                'help' => 'Guest records with no booking at all and nothing in a cart. A guest with any booking is kept.'),
            'bin' => array('name' => 'Recycle bin, removed for good', 'auto' => 0, 'default_days' => self::BIN_DAYS, 'min' => self::BIN_DAYS, 'locked' => true,
                'help' => 'A deleted booking can only be removed for good once it has been in the recycle bin for 90 days and the booking is more than 6 years old.'),
        );
    }

    /** The cut-off date for "older than $days days", never sooner than $min days */
    public static function cutoff($days, $min = 1)
    {
        $days = max((int) $min, (int) $days);

        return date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
    }

    /** @return int how many items of $kind are older than $cutoff */
    public static function count($kind, $cutoff)
    {
        $sql = self::select($kind, $cutoff, 'COUNT(*)');

        return $sql ? (int) Db::getInstance()->getValue($sql, false) : 0;
    }

    /** @return array a few examples, each array('label' => ..., 'date' => ...) */
    public static function sample($kind, $cutoff)
    {
        $columns = array(
            'drafts' => 'CONCAT("Draft #", d.`id_walkin_draft`) AS label, d.`date_upd` AS date',
            'carts' => 'CONCAT("Cart #", c.`id_cart`) AS label, c.`date_upd` AS date',
            'logs' => 'LEFT(l.`message`, 80) AS label, l.`date_add` AS date',
            'messages' => 'CONCAT(ct.`email`, " (#", ct.`id_customer_thread`, ")") AS label, ct.`date_upd` AS date',
            'guests' => 'CONCAT(cu.`firstname`, " ", cu.`lastname`) AS label, cu.`date_add` AS date',
            'bin' => 'CONCAT("Booking ", o.`reference`) AS label, b.`deleted_at` AS date',
        );
        $sql = isset($columns[$kind]) ? self::select($kind, $cutoff, $columns[$kind]) : '';
        if (!$sql) {
            return array();
        }

        return (array) Db::getInstance()->executeS($sql.' ORDER BY date ASC LIMIT '.(int) self::SAMPLE, true, false);
    }

    /**
     * The one query per kind, so count, sample and remove can never disagree.
     * Returns '' for a kind that is not available.
     */
    protected static function select($kind, $cutoff, $columns)
    {
        $p = _DB_PREFIX_;
        $cutoff = pSQL($cutoff);
        switch ($kind) {
            case 'drafts':
                if (!self::tableExists('sb_walkin_draft')) {
                    return '';
                }
                return 'SELECT '.$columns.' FROM `'.$p.'sb_walkin_draft` d
                    WHERE d.`status` = \'active\' AND d.`id_order` = 0 AND d.`date_upd` < \''.$cutoff.'\'
                    AND NOT EXISTS (SELECT 1 FROM `'.$p.'orders` o WHERE o.`id_cart` = d.`id_cart`)';
            case 'carts':
                return 'SELECT '.$columns.' FROM `'.$p.'cart` c
                    WHERE c.`date_upd` < \''.$cutoff.'\'
                    AND NOT EXISTS (SELECT 1 FROM `'.$p.'orders` o WHERE o.`id_cart` = c.`id_cart`)'
                    .(self::tableExists('sb_walkin_draft')
                        ? ' AND NOT EXISTS (SELECT 1 FROM `'.$p.'sb_walkin_draft` d WHERE d.`id_cart` = c.`id_cart` AND d.`status` = \'active\')'
                        : '');
            case 'logs':
                return 'SELECT '.$columns.' FROM `'.$p.'log` l WHERE l.`date_add` < \''.$cutoff.'\'';
            case 'messages':
                return 'SELECT '.$columns.' FROM `'.$p.'customer_thread` ct
                    WHERE ct.`status` = '.(int) CustomerThread::QLO_CUSTOMER_THREAD_STATUS_CLOSED.'
                    AND ct.`date_upd` < \''.$cutoff.'\'';
            case 'guests':
                return 'SELECT '.$columns.' FROM `'.$p.'customer` cu
                    WHERE cu.`deleted` = 0 AND cu.`date_add` < \''.$cutoff.'\' AND cu.`date_upd` < \''.$cutoff.'\'
                    AND NOT EXISTS (SELECT 1 FROM `'.$p.'orders` o WHERE o.`id_customer` = cu.`id_customer`)
                    AND NOT EXISTS (SELECT 1 FROM `'.$p.'htl_booking_detail` bd WHERE bd.`id_customer` = cu.`id_customer`)
                    AND NOT EXISTS (SELECT 1 FROM `'.$p.'cart` c
                        INNER JOIN `'.$p.'htl_cart_booking_data` cb ON cb.`id_cart` = c.`id_cart`
                        WHERE c.`id_customer` = cu.`id_customer`)';
            case 'bin':
                if (!self::tableExists('sb_deleted_booking')) {
                    return '';
                }
                // Both rules at once: 90 days in the bin, and older than the 6-year keep
                return 'SELECT '.$columns.' FROM `'.$p.'sb_deleted_booking` b
                    INNER JOIN `'.$p.'orders` o ON o.`id_order` = b.`id_order`
                    WHERE b.`deleted_at` < \''.pSQL(self::cutoff(self::BIN_DAYS, self::BIN_DAYS)).'\'
                    AND o.`date_add` < \''.pSQL(date('Y-m-d H:i:s', strtotime('-'.self::KEEP_YEARS.' years'))).'\'';
        }

        return '';
    }

    /**
     * Removes up to BATCH items of $kind older than $cutoff.
     * @return array('removed' => int, 'failed' => int)
     */
    public static function remove($kind, $cutoff, $who = 'automatic')
    {
        $p = _DB_PREFIX_;
        $done = array('removed' => 0, 'failed' => 0);
        $kinds = self::kinds();
        if (!isset($kinds[$kind]) || !empty($kinds[$kind]['locked'])) {
            return $done;
        }
        $db = Db::getInstance();

        if ($kind == 'logs') {
            $ids = $db->executeS(self::select($kind, $cutoff, 'l.`id_log`').' LIMIT '.(int) self::BATCH, true, false);
            $ids = array_map('intval', array_column((array) $ids, 'id_log'));
            if ($ids && $db->execute('DELETE FROM `'.$p.'log` WHERE `id_log` IN ('.implode(',', $ids).')')) {
                $done['removed'] = count($ids);
            }
        } elseif ($kind == 'drafts') {
            $rows = $db->executeS(self::select($kind, $cutoff, 'd.`id_walkin_draft`, d.`id_cart`').' LIMIT '.(int) self::BATCH, true, false);
            foreach ((array) $rows as $row) {
                $ok = self::removeCart((int) $row['id_cart'])
                    && $db->update('sb_walkin_draft', array('status' => 'discarded', 'date_upd' => date('Y-m-d H:i:s')), '`id_walkin_draft` = '.(int) $row['id_walkin_draft']);
                $done[$ok ? 'removed' : 'failed']++;
            }
        } elseif ($kind == 'carts') {
            $rows = $db->executeS(self::select($kind, $cutoff, 'c.`id_cart`').' LIMIT '.(int) self::BATCH, true, false);
            foreach ((array) $rows as $row) {
                $done[self::removeCart((int) $row['id_cart']) ? 'removed' : 'failed']++;
            }
        } elseif ($kind == 'messages') {
            $rows = $db->executeS(self::select($kind, $cutoff, 'ct.`id_customer_thread`').' LIMIT '.(int) self::BATCH, true, false);
            foreach ((array) $rows as $row) {
                $thread = new CustomerThread((int) $row['id_customer_thread']);
                $done[Validate::isLoadedObject($thread) && $thread->delete() ? 'removed' : 'failed']++;
            }
        } elseif ($kind == 'guests') {
            $rows = $db->executeS(self::select($kind, $cutoff, 'cu.`id_customer`').' LIMIT '.(int) self::BATCH, true, false);
            foreach ((array) $rows as $row) {
                $customer = new Customer((int) $row['id_customer']);
                $done[Validate::isLoadedObject($customer) && $customer->delete() ? 'removed' : 'failed']++;
            }
        }

        if ($done['removed'] || $done['failed']) {
            PrestaShopLogger::addLog(
                sprintf('Data clean-up (%s): %s, older than %s: %d removed, %d failed', $who, $kind, $cutoff, $done['removed'], $done['failed']),
                $done['failed'] ? 2 : 1, null, 'SalisbergDataCleanup', 0, true
            );
        }

        return $done;
    }

    /** A cart with no booking: releases its rooms, then deletes it */
    protected static function removeCart($idCart)
    {
        if (!$idCart || Order::getOrderByCartId($idCart)) {
            return false;
        }
        Db::getInstance()->delete('htl_cart_booking_data', '`id_cart` = '.(int) $idCart.' AND `id_order` = 0');
        $cart = new Cart($idCart);

        return !Validate::isLoadedObject($cart) || $cart->delete();
    }

    /**
     * The daily automatic clean-up (owner, 2026-11-20): drafts and carts older
     * than 30 days, logs older than 90. Runs at most once a day, from the first
     * back office page opened that day, and only when switched on.
     */
    public static function runAutomatic()
    {
        $today = date('Y-m-d');
        if (!Configuration::get('SBCLEAN_AUTO') || Configuration::get('SBCLEAN_LAST_AUTO') == $today) {
            return;
        }
        // mark first, so two pages opened at once do not both run it
        Configuration::updateValue('SBCLEAN_LAST_AUTO', $today);
        foreach (self::kinds() as $kind => $def) {
            if (!empty($def['auto'])) {
                self::remove($kind, self::cutoff($def['auto'], $def['auto']), 'automatic');
            }
        }
    }

    protected static function tableExists($table)
    {
        return (bool) Db::getInstance()->executeS('SHOW TABLES LIKE \''.pSQL(_DB_PREFIX_.$table).'\'', true, false);
    }
}
