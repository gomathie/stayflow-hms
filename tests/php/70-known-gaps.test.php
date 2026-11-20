<?php
// Characterization: pins today's behaviour of a known gap, it does not endorse it.
return array(
    'GAP: Track my booking needs an email, so a phone-only guest cannot use it' => function () {
        /*
         * A guest who booked with a phone number only (P-24) has the placeholder
         * email, which they do not know. The guest tracking page asks for the
         * booking reference and an email, so they cannot track their booking.
         * The change that lets them track by phone must invert this test.
         */
        $source = file_get_contents(_PS_ROOT_DIR_.'/controllers/front/GuestTrackingController.php');
        check($source !== false, 'precondition: the guest tracking controller exists');
        check(strpos($source, "Tools::getValue('email')") !== false, 'tracking reads an email address');
        check(strpos($source, "Tools::getValue('phone')") === false, 'tracking does not accept a phone number');
    },
);
