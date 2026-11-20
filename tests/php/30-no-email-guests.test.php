<?php
// A guest with no email (a walk-in, or a website guest since P-24) gets a
// placeholder address that nothing is ever sent to (Mail::Send).
return array(
    'placeholder email: recognised, and Mail::Send sends nothing to it' => function () {
        $placeholder = 'guest-20261120-1234@'.Customer::NO_EMAIL_DOMAIN;
        check(Customer::hasNoEmail($placeholder), 'the placeholder is recognised');
        check(!Customer::hasNoEmail('ama@gmail.com'), 'negative control: a real address is not a placeholder');
        // returns true at once (no mail server is needed), and never tries the transport
        same(true, Mail::Send(1, 'contact', 'test', array(), $placeholder), 'sending to a placeholder succeeds without sending');
    },
);
