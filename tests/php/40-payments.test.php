<?php
// Marking a booking paid records only what is still owed (OrderHistory caps
// the automatic payment, AGENTS.md 2026-10-14), and a booking whose payment
// module has been removed keeps its saved payment name (2026-11-20).
function sb_unpaid_order_id()
{
    return (int) Db::getInstance()->getValue(
        'SELECT o.`id_order` FROM `'._DB_PREFIX_.'orders` o
        WHERE o.`current_state` = '.(int) Configuration::get('PS_OS_AWAITING_PAYMENT').'
        AND o.`total_paid_real` = 0 AND o.`total_paid_tax_incl` > 200
        AND EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'order_invoice` i WHERE i.`id_order` = o.`id_order`)
        ORDER BY o.`id_order` DESC', false
    );
}

function sb_paid_total($reference)
{
    return (float) Db::getInstance()->getValue(
        'SELECT COALESCE(SUM(`amount`), 0) FROM `'._DB_PREFIX_.'order_payment` WHERE `order_reference` = \''.pSQL($reference).'\'', false
    );
}

function sb_mark_paid(Order $order)
{
    $history = new OrderHistory();
    $history->id_order = (int) $order->id;
    $history->id_employee = 1;
    $history->changeIdOrderState((int) Configuration::get('PS_OS_PAYMENT_ACCEPTED'), $order);
    $history->add();
}

return array(
    'payments: marking paid after a part payment records only the rest' => function () {
        $id = sb_unpaid_order_id();
        check($id > 0, 'precondition: a local unpaid booking with an invoice exists (make one in the e2e suite)');
        rolledBack(function () use ($id) {
            $order = new Order($id);
            $total = (float) $order->total_paid_tax_incl;
            same(0.0, sb_paid_total($order->reference), 'precondition: nothing paid yet');
            $invoice = $order->getInvoicesCollection()->getFirst();
            $order->addOrderPayment(100, 'Cash', null, null, null, $invoice ?: null);
            same(100.0, round(sb_paid_total($order->reference), 2), 'negative control: the part payment is recorded');

            sb_mark_paid(new Order($id));
            same(round($total, 2), round(sb_paid_total($order->reference), 2),
                'after marking paid, the payments add up to the total, not total + 100');
        });
    },
    'payments: a booking whose payment module is gone keeps its payment name' => function () {
        $id = sb_unpaid_order_id();
        check($id > 0, 'precondition: a local unpaid booking with an invoice exists');
        rolledBack(function () use ($id) {
            Db::getInstance()->update('orders', array('module' => 'sbremovedmodule', 'payment' => 'Old method'), 'id_order = '.$id);
            check(!Module::getInstanceByName('sbremovedmodule'), 'precondition: that module does not exist');
            $order = new Order($id);
            sb_mark_paid($order);
            $name = Db::getInstance()->getValue(
                'SELECT `payment_method` FROM `'._DB_PREFIX_.'order_payment` WHERE `order_reference` = \''.pSQL($order->reference).'\' ORDER BY `id_order_payment` DESC', false
            );
            same('Old method', $name, 'the payment row carries the name saved on the booking');
        });
    },
);
