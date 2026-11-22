{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<table id="payment-tab" width="100%" cellpadding="5" cellspacing="0">
	<tr>
		<th colspan="3" class="header-left section">{l s='Payments received' pdf='true'}</th>
	</tr>
	{foreach from=$sb.payments item=payment}
		<tr>
			<td class="white small" width="30%">{$payment.date|escape:'html':'UTF-8'}</td>
			<td class="white small" width="40%">{$payment.method|escape:'html':'UTF-8'}{if $payment.reference}<br /><span class="muted">{$payment.reference|escape:'html':'UTF-8'}</span>{/if}</td>
			<td class="white small right" width="30%">{displayPrice currency=$payment.id_currency price=$payment.amount}</td>
		</tr>
	{foreachelse}
		<tr>
			<td colspan="3" class="white small muted">{l s='No payment has been received yet.' pdf='true'}</td>
		</tr>
	{/foreach}
</table>
{if $sb.how_to_pay}
	<br /><br />
	<table id="pay-tab" width="100%" cellpadding="5" cellspacing="0">
		<tr>
			<th class="header-left section">{l s='How to pay the balance' pdf='true'}</th>
		</tr>
		{foreach from=$sb.how_to_pay item=way}
			<tr>
				<td class="white small">
					<span class="bold">{$way.title|escape:'html':'UTF-8'}</span><br />
					{foreach from=$way.lines item=line name=lines}{$line|escape:'html':'UTF-8'}{if !$smarty.foreach.lines.last}<br />{/if}{/foreach}
				</td>
			</tr>
		{/foreach}
		<tr>
			<td class="white small muted">{l s='Please quote your booking reference:' pdf='true'} {$sb.booking_reference|escape:'html':'UTF-8'}</td>
		</tr>
	</table>
{/if}