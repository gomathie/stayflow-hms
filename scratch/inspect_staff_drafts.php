<?php
require '/var/www/html/config/config.inc.php';

$rows = Db::getInstance()->executeS(
    'SELECT d.`id_walkin_draft`, d.`id_cart`, d.`step`, d.`status`, d.`id_order`, d.`date_upd`, d.`state`
    FROM `'._DB_PREFIX_.'sb_walkin_draft` d
    INNER JOIN `'._DB_PREFIX_.'employee` e ON e.`id_employee` = d.`id_employee`
    WHERE e.`email` = \'staff.test@example.com\'
    ORDER BY d.`id_walkin_draft` DESC LIMIT 5'
);
foreach ($rows as &$row) {
    $state = Tools::jsonDecode($row['state'], true);
    $row['state'] = array(
        'stay' => isset($state['stay']) ? $state['stay'] : array(),
        'guest' => isset($state['guest']) ? $state['guest'] : array(),
        'payment' => isset($state['payment']) ? $state['payment'] : array(),
        'price_changed' => isset($state['price_changed']) ? $state['price_changed'] : array(),
    );
}
unset($row);
echo Tools::jsonEncode($rows).PHP_EOL;
