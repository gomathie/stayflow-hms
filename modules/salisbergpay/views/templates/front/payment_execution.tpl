{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='salisbergpay'}">{l s='Checkout' mod='salisbergpay'}</a><span class="navigation-pipe">{$navigationPipe}</span>{$sbpay_method.title|escape:'html':'UTF-8'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='salisbergpay'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./errors.tpl"}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='salisbergpay'}
    </p>
{else}
    <form action="{$link->getModuleLink('salisbergpay', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
        <input type="hidden" name="method" value="{$sbpay_method.code|escape:'html':'UTF-8'}" />
        <input type="hidden" name="token" value="{$sbpay_token|escape:'html':'UTF-8'}" />
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {$sbpay_method.title|escape:'html':'UTF-8'}
            </h3>
            <p>
                {l s='The total amount of your booking is' mod='salisbergpay'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1 && $display_tax_label}
                    {l s='(tax incl.)' mod='salisbergpay'}
                {/if}
            </p>
            {if $sbpay_method.code == 'momo'}
                <p>{l s='After you confirm, send this amount by Mobile Money to:' mod='salisbergpay'}</p>
                <p>
                    {if $sbpay_momo.network}{l s='Network:' mod='salisbergpay'} <strong>{$sbpay_momo.network|escape:'html':'UTF-8'}</strong><br />{/if}
                    {if $sbpay_momo.name}{l s='Account name:' mod='salisbergpay'} <strong>{$sbpay_momo.name|escape:'html':'UTF-8'}</strong><br />{/if}
                    {l s='Number:' mod='salisbergpay'} <strong>{$sbpay_momo.number|escape:'html':'UTF-8'}</strong>
                </p>
                <p>{l s='You will get a booking reference on the next page. Use it as the payment reference so we can match your payment.' mod='salisbergpay'}</p>
                <p>{l s='Your booking is confirmed once we receive the payment.' mod='salisbergpay'}</p>
            {elseif $sbpay_method.code == 'bank'}
                <p>{l s='After you confirm, transfer this amount to:' mod='salisbergpay'}</p>
                <p>
                    {if $sbpay_bank.bank}{l s='Bank:' mod='salisbergpay'} <strong>{$sbpay_bank.bank|escape:'html':'UTF-8'}</strong><br />{/if}
                    {if $sbpay_bank.branch}{l s='Branch:' mod='salisbergpay'} <strong>{$sbpay_bank.branch|escape:'html':'UTF-8'}</strong><br />{/if}
                    {if $sbpay_bank.name}{l s='Account name:' mod='salisbergpay'} <strong>{$sbpay_bank.name|escape:'html':'UTF-8'}</strong><br />{/if}
                    {l s='Account number:' mod='salisbergpay'} <strong>{$sbpay_bank.account|escape:'html':'UTF-8'}</strong>
                </p>
                <p>{l s='You will get a booking reference on the next page. Use it as the payment reference so we can match your payment.' mod='salisbergpay'}</p>
                <p>{l s='Your booking is confirmed once we receive the payment.' mod='salisbergpay'}</p>
            {else}
                <p>{l s='You will pay this amount in cash at reception when you arrive.' mod='salisbergpay'}</p>
                <p>{l s='Your room is reserved now. Please bring your booking reference, shown on the next page.' mod='salisbergpay'}</p>
            {/if}
            <p>{l s='Please confirm your booking by clicking "I confirm my booking".' mod='salisbergpay'}</p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='salisbergpay'}
            </a>
            {if !$restrict_order}
                <button class="btn pull-right button button-medium confirm_order" type="submit">
                    <span>{l s='I confirm my booking' mod='salisbergpay'}&nbsp;<i class="icon-chevron-right right"></i></span>
                </button>
            {/if}
        </p>
    </form>
{/if}
