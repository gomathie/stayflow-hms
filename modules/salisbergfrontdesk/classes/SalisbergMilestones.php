<?php
/**
 * Booking milestones: 50, 100, 200 ... 900, 1,000, then every 500.
 *
 * A booking counts unless it is cancelled, refunded or in the recycle bin.
 * Reached milestones are kept in Configuration (SBFD_MILESTONES) as a list of
 * {n, date, reference}, newest last. Each employee's dismissal of the banner
 * is SBFD_MILESTONE_SEEN_<id_employee>: the highest milestone they closed.
 *
 * Copyright Salisberg. MIT licence.
 */
class SalisbergMilestones
{
    const KEY = 'SBFD_MILESTONES';
    const SEEN = 'SBFD_MILESTONE_SEEN_';

    /** The milestones up to and including $limit, in order */
    public static function steps($limit)
    {
        $steps = array();
        for ($n = 50; $n <= $limit; $n = self::next($n)) {
            $steps[] = $n;
        }

        return $steps;
    }

    /** The milestone after $n */
    public static function next($n)
    {
        if ($n < 100) {
            return 100;
        }
        if ($n < 1000) {
            return $n + 100;
        }

        return $n + 500;
    }

    /** SQL for the bookings that count, oldest first */
    protected static function countingSql()
    {
        $excluded = array_filter(array(
            (int) Configuration::get('PS_OS_CANCELED'),
            (int) Configuration::get('PS_OS_REFUND'),
        ));

        return 'FROM `'._DB_PREFIX_.'orders` o
            LEFT JOIN `'._DB_PREFIX_.SalisbergBookingArchive::TABLE.'` a ON a.`id_order` = o.`id_order`
            WHERE a.`id_order` IS NULL'
            .($excluded ? ' AND o.`current_state` NOT IN ('.implode(',', $excluded).')' : '');
    }

    public static function count()
    {
        return (int) Db::getInstance()->getValue('SELECT COUNT(*) '.self::countingSql(), false);
    }

    /** @return array[] reached milestones, oldest first */
    public static function reached()
    {
        $list = json_decode((string) Configuration::get(self::KEY), true);

        return is_array($list) ? $list : array();
    }

    /**
     * Records any milestone the count has passed. Returns the ones new since
     * the last call. On the very first call every milestone already passed is
     * recorded with the booking that reached it, but reported as not new, so a
     * site that already has hundreds of bookings is not flooded with alerts.
     *
     * @return array[] newly reached milestones
     */
    public static function update()
    {
        $first = Configuration::get(self::KEY) === false;
        $reached = self::reached();
        $known = array();
        foreach ($reached as $m) {
            $known[(int) $m['n']] = true;
        }
        $count = self::count();
        $new = array();
        foreach (self::steps($count) as $n) {
            if (isset($known[$n])) {
                continue;
            }
            // executeS, not getRow: getRow appends its own LIMIT 1
            $rows = Db::getInstance()->executeS(
                'SELECT o.`reference`, o.`date_add` '.self::countingSql().' ORDER BY o.`id_order` LIMIT '.(int) ($n - 1).', 1',
                true,
                false
            );
            $row = $rows ? $rows[0] : null;
            $m = array(
                'n' => $n,
                'date' => $row ? substr($row['date_add'], 0, 10) : date('Y-m-d'),
                'reference' => $row ? $row['reference'] : '',
            );
            $reached[] = $m;
            if (!$first) {
                $new[] = $m;
            }
        }
        if ($new || $first || count($reached) != count($known)) {
            Configuration::updateValue(self::KEY, json_encode($reached));
        }

        return $new;
    }

    /** The newest milestone this employee has not closed yet, or null */
    public static function unseen($idEmployee)
    {
        $reached = self::reached();
        if (!$reached) {
            return null;
        }
        $latest = end($reached);
        $seen = (int) Configuration::get(self::SEEN.(int) $idEmployee);

        return ((int) $latest['n'] > $seen) ? $latest : null;
    }

    public static function markSeen($idEmployee, $n)
    {
        Configuration::updateValue(self::SEEN.(int) $idEmployee, (int) $n);
    }
}
