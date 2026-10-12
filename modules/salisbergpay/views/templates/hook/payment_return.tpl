{if $status == 'ok'}
    <p class="alert alert-success">
        {if $cart_room_bookings|count > 1}{l s='Your bookings have been created.' mod='salisbergpay'}{else}{l s='Your booking has been created.' mod='salisbergpay'}{/if}
    </p>
    <div class="box">
        {if $sbpay_method == 'momo'}
            <p>{l s='Please send your payment by Mobile Money:' mod='salisbergpay'}</p>
            <p>
                {l s='Amount:' mod='salisbergpay'} <span class="price"><strong>{$total_to_pay}</strong></span><br />
                {if $sbpay_momo.network}{l s='Network:' mod='salisbergpay'} <strong>{$sbpay_momo.network|escape:'html':'UTF-8'}</strong><br />{/if}
                {if $sbpay_momo.name}{l s='Account name:' mod='salisbergpay'} <strong>{$sbpay_momo.name|escape:'html':'UTF-8'}</strong><br />{/if}
                {l s='Number:' mod='salisbergpay'} <strong>{$sbpay_momo.number|escape:'html':'UTF-8'}</strong><br />
                {l s='Payment reference:' mod='salisbergpay'} <strong>{$reference|escape:'html':'UTF-8'}</strong>
            </p>
            {if $sbpay_momo.note}<p>{$sbpay_momo.note|escape:'html':'UTF-8'|nl2br}</p>{/if}
            <p>{l s='We will confirm your booking as soon as the payment arrives.' mod='salisbergpay'}</p>
        {elseif $sbpay_method == 'bank'}
            <p>{l s='Please send your payment by bank transfer:' mod='salisbergpay'}</p>
            <p>
                {l s='Amount:' mod='salisbergpay'} <span class="price"><strong>{$total_to_pay}</strong></span><br />
                {if $sbpay_bank.bank}{l s='Bank:' mod='salisbergpay'} <strong>{$sbpay_bank.bank|escape:'html':'UTF-8'}</strong><br />{/if}
                {if $sbpay_bank.branch}{l s='Branch:' mod='salisbergpay'} <strong>{$sbpay_bank.branch|escape:'html':'UTF-8'}</strong><br />{/if}
                {if $sbpay_bank.name}{l s='Account name:' mod='salisbergpay'} <strong>{$sbpay_bank.name|escape:'html':'UTF-8'}</strong><br />{/if}
                {l s='Account number:' mod='salisbergpay'} <strong>{$sbpay_bank.account|escape:'html':'UTF-8'}</strong><br />
                {l s='Payment reference:' mod='salisbergpay'} <strong>{$reference|escape:'html':'UTF-8'}</strong>
            </p>
            {if $sbpay_bank.note}<p>{$sbpay_bank.note|escape:'html':'UTF-8'|nl2br}</p>{/if}
            <p>{l s='We will confirm your booking as soon as the payment arrives.' mod='salisbergpay'}</p>
        {else}
            <p>{l s='Please pay in cash at reception when you arrive:' mod='salisbergpay'}</p>
            <p>
                {l s='Amount:' mod='salisbergpay'} <span class="price"><strong>{$total_to_pay}</strong></span><br />
                {l s='Booking reference:' mod='salisbergpay'} <strong>{$reference|escape:'html':'UTF-8'}</strong>
            </p>
        {/if}
    </div>
{else}
    <p class="warning">
        {l s='We noticed a problem with your booking. If you think this is an error, please' mod='salisbergpay'}
        <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='contact us' mod='salisbergpay'}</a>.
    </p>
{/if}
