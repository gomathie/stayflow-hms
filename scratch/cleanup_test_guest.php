<?php
require '/var/www/html/config/config.inc.php';

$customer = new Customer(6);
if (!Validate::isLoadedObject($customer)
    || $customer->firstname !== 'Hotel'
    || $customer->lastname !== 'Staff Test'
    || (int) Db::getInstance()->getValue(
        'SELECT COUNT(*) FROM `'._DB_PREFIX_.'orders` WHERE `id_customer` = '.(int) $customer->id
    ) !== 0
) {
    fwrite(STDERR, "The test guest did not match the safe cleanup conditions.\n");
    exit(1);
}

$addresses = (array) Db::getInstance()->executeS(
    'SELECT `id_address` FROM `'._DB_PREFIX_.'address` WHERE `id_customer` = '.(int) $customer->id
);
foreach ($addresses as $row) {
    $address = new Address((int) $row['id_address']);
    if (Validate::isLoadedObject($address)) {
        $address->delete();
    }
}
if (!$customer->delete()) {
    fwrite(STDERR, "The unused test guest could not be removed.\n");
    exit(1);
}
echo "removed\n";
