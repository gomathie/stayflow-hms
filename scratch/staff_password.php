<?php
require '/var/www/html/config/config.inc.php';

$action = isset($argv[1]) ? $argv[1] : '';
$email = 'staff.test@example.com';
$backup = '/tmp/salisberg-staff-password-backup';
$employee = Db::getInstance()->getRow(
    'SELECT `id_employee`, `passwd` FROM `'._DB_PREFIX_.'employee` WHERE `email` = \''.pSQL($email).'\''
);
if (!$employee) {
    fwrite(STDERR, "Hotel Staff test account was not found.\n");
    exit(1);
}

if ($action === 'prepare') {
    if (file_exists($backup)) {
        fwrite(STDERR, "A password backup already exists; restore it before preparing again.\n");
        exit(1);
    }
    file_put_contents($backup, $employee['passwd']);
    chmod($backup, 0600);
    $hasher = new PasswordHashing();
    $saved = Db::getInstance()->update('employee', array(
        'passwd' => pSQL($hasher->passwordHash('FrontDesk-Test-Only-2026!')),
    ), '`id_employee` = '.(int) $employee['id_employee']);
    echo $saved ? "prepared\n" : "failed\n";
    exit($saved ? 0 : 1);
}

if ($action === 'restore') {
    if (!file_exists($backup)) {
        fwrite(STDERR, "The password backup does not exist.\n");
        exit(1);
    }
    $saved = Db::getInstance()->update('employee', array(
        'passwd' => pSQL(file_get_contents($backup)),
    ), '`id_employee` = '.(int) $employee['id_employee']);
    if ($saved) {
        unlink($backup);
    }
    echo $saved ? "restored\n" : "failed\n";
    exit($saved ? 0 : 1);
}

fwrite(STDERR, "Use prepare or restore.\n");
exit(1);
