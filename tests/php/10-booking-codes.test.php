<?php
// Booking codes are SB-YYMMDD-NN: the booking day and its running number for
// that day (Order::generateReference, AGENTS.md 2026-11-19).
return array(
    'booking codes: SB-YYMMDD-NN, numbered in order within the day' => function () {
        $db = Db::getInstance();
        check((bool) $db->executeS('SHOW TABLES LIKE \''._DB_PREFIX_.'sb_reference_counter\'', true, false),
            'precondition: the day counter table exists');
        rolledBack(function () {
            $first = Order::generateReference();
            $second = Order::generateReference();
            check((bool) preg_match('/^SB-'.date('ymd').'-(\d{2,})$/', $first, $a), "code has the form SB-".date('ymd')."-NN, got $first");
            check((bool) preg_match('/^SB-'.date('ymd').'-(\d{2,})$/', $second, $b), "second code has the same form, got $second");
            same((int) $a[1] + 1, (int) $b[1], 'the next booking of the day gets the next number');
        });
    },
);
