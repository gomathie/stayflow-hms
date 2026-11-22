<?php
// Guest names are tidied (P-15) and web addresses are refused in guest fields
// (AGENTS.md 2026-11-20). Email addresses are not links.
return array(
    'names: small or capital letters become Title Case; mixed case is kept' => function () {
        same('Ama Mensah', Customer::tidyName('ama mensah'), 'all small letters');
        same('Kofi', Customer::tidyName('KOFI'), 'all capitals');
        same('McDonald', Customer::tidyName('McDonald'), 'mixed case is kept as typed');
        same('Ama-Serwaa', Customer::tidyName('ama-serwaa'), 'each part of a double name');
    },
    'names: a title alone is not a name' => function () {
        foreach (array('Mr', 'mrs', 'Dr.', 'MISS') as $title) {
            check(Customer::isTitleOnly($title), "\"$title\" is only a title");
        }
        check(!Customer::isTitleOnly('Mrs Mensah'), 'negative control: a title with a name is a name');
        check(!Customer::isTitleOnly('Drew'), 'negative control: a name starting like a title is a name');
    },
    'links: web addresses are refused in names and addresses, email addresses are not' => function () {
        foreach (array('www.evil.com', 'http://x.co', 'bit.ly', 'visit scam.ru now', 'javascript:alert(1)') as $text) {
            check(Validate::containsLink($text), "\"$text\" is a link");
        }
        foreach (array('Ama Mensah', 'P.O. Box 4501, Accra', 'Plot 7, Spintex Rd.', 'ama@gmail.com') as $text) {
            check(!Validate::containsLink($text), "\"$text\" is not a link");
        }
        check(!Validate::isName('www.evil.com'), 'a name with a link is refused');
        check(Validate::isName('Ama'), 'negative control: a plain name is accepted');
    },
);
