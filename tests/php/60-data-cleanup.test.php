<?php
// Data clean-up keeps what the owner decided (CLEANUP-PLAN.md, 2026-11-20):
// each kind has a shortest age, deleted bookings are never removed for good
// before 90 days in the bin and 6 years of age, and nothing is removed by a preview.
require_once _PS_MODULE_DIR_.'salisbergcleanup/classes/SalisbergDataCleanup.php';

return array(
    'data clean-up: shortest ages, the 6-year keep, the locked recycle bin' => function () {
        same(6, SalisbergDataCleanup::KEEP_YEARS, 'paid bookings are kept 6 years');
        same(90, SalisbergDataCleanup::BIN_DAYS, 'deleted bookings stay 90 days in the bin');
        $kinds = SalisbergDataCleanup::kinds();
        same(90, (int) $kinds['guests']['min'], 'guests: shortest age 90 days');
        same(2, (int) $kinds['drafts']['min'], 'drafts: shortest age 2 days');
        check(!empty($kinds['bin']['locked']), 'the recycle bin cannot be emptied from the tool');

        // asking for 1 day still means at least the minimum
        $cutoff = strtotime(SalisbergDataCleanup::cutoff(1, 90));
        check($cutoff <= strtotime('-89 days'), 'a 1-day request for guests is raised to 90 days');

        $removed = SalisbergDataCleanup::remove('bin', date('Y-m-d H:i:s'), 'rule test');
        same(0, $removed['removed'], 'remove() refuses the locked recycle bin');
    },
);
