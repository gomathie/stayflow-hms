<?php
/**
 * Rule tests: pin how the money and safety rules behave today, so a change
 * that alters one fails here first. Runs inside the LOCAL app container
 * (tests/php/run.sh). Each test that writes runs in a transaction that is
 * rolled back, so the local data is left as it was.
 *
 * A test file returns array('name' => function () { ... }). Inside a test:
 *   check($condition, 'what was expected');   same($expected, $actual, 'what');
 */
require '/var/www/html/config/config.inc.php';

// what a web request would have: price code needs a currency, a cart and a customer
$sbContext = Context::getContext();
$sbContext->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
$sbContext->cart = new Cart();
$sbContext->customer = new Customer();
$sbContext->country = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));

$GLOBALS['sb_checks'] = 0;
class SbTestFailure extends Exception {}

function check($condition, $message)
{
    $GLOBALS['sb_checks']++;
    if (!$condition) {
        throw new SbTestFailure($message);
    }
}

function same($expected, $actual, $message)
{
    check($expected === $actual, $message.' (expected '.var_export($expected, true).', got '.var_export($actual, true).')');
}

/** Runs $fn inside a transaction that is always rolled back */
function rolledBack(callable $fn)
{
    $db = Db::getInstance();
    $db->execute('START TRANSACTION');
    try {
        return $fn();
    } finally {
        $db->execute('ROLLBACK');
    }
}

$files = glob(__DIR__.'/*.test.php');
sort($files);
$passed = $failed = 0;
foreach ($files as $file) {
    foreach ((array) require $file as $name => $test) {
        $before = $GLOBALS['sb_checks'];
        try {
            $test();
            $passed++;
            printf("  ok    %s (%d checks)\n", $name, $GLOBALS['sb_checks'] - $before);
        } catch (Throwable $e) {
            $failed++;
            printf("  FAIL  %s\n        %s\n", $name, $e->getMessage());
        }
    }
}
printf("Tests: %d passed, %d failed, %d checks\n", $passed, $failed, $GLOBALS['sb_checks']);
exit($failed ? 1 : 0);
