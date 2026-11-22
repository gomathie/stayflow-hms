<?php
/**
 * Test accounts for the end-to-end suite, created or refreshed on every run.
 * Runs inside the local app container only (support/global-setup.js).
 * Prints one JSON line: the two accounts and their password.
 */
require '/var/www/html/config/config.inc.php';

$password = getenv('E2E_PASSWORD') ?: 'E2eTest#2026';
$lang = (int) Configuration::get('PS_LANG_DEFAULT');
$out = array();
foreach (array('manager' => 'Hotel Manager', 'staff' => 'Hotel Staff') as $key => $profileName) {
    $idProfile = (int) Db::getInstance()->getValue(
        'SELECT `id_profile` FROM `'._DB_PREFIX_.'profile_lang` WHERE `name` = \''.pSQL($profileName).'\' AND `id_lang` = '.$lang
    );
    if (!$idProfile) {
        fwrite(STDERR, "profile $profileName not found\n");
        exit(1);
    }
    $email = 'e2e.'.$key.'@example.com';
    $id = (int) Employee::employeeExists($email) ? (int) Db::getInstance()->getValue('SELECT `id_employee` FROM `'._DB_PREFIX_.'employee` WHERE `email` = \''.pSQL($email).'\'') : 0;
    $e = new Employee($id ?: null);
    $e->firstname = 'Test';
    $e->lastname = ucfirst($key);
    $e->email = $email;
    $e->id_profile = $idProfile;
    $e->id_lang = $lang;
    $e->active = 1;
    $e->bo_theme = 'default';
    $e->default_tab = (int) Tab::getIdFromClassName('AdminDashboard');
    $e->passwd = Tools::encrypt($password);
    $id ? $e->update() : $e->add();
    $e->setTestAccount(true);
    $out[$key] = $email;
}
$out['password'] = $password;
// the hotel's room list (a category page), for the website booking test
$out['hotel_category'] = (int) Db::getInstance()->getValue('SELECT `id_category` FROM `'._DB_PREFIX_.'htl_branch_info` WHERE `active` = 1 ORDER BY `id`');
echo json_encode($out)."\n";
