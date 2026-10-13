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
<table id="total-tab" width="100%" cellpadding="5" cellspacing="0">
	{if isset($footer.room_price_tax_excl) && $footer.room_price_tax_excl}
		<tr>
			<td class="grey" width="50%">{l s='Rooms' pdf='true'}</td>
			<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$footer.room_price_tax_excl}</td>
		</tr>
	{/if}
	{if isset($footer.service_products_price_tax_excl) && $footer.service_products_price_tax_excl}
		<tr>
			<td class="grey" width="50%">{l s='Other items' pdf='true'}</td>
			<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$footer.service_products_price_tax_excl}</td>
		</tr>
	{/if}
	{if isset($footer.additional_service_price_tax_excl) && $footer.additional_service_price_tax_excl}
		<tr>
			<td class="grey" width="50%">{l s='Extra services' pdf='true'}</td>
			<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=($footer.additional_service_price_tax_excl)}</td>
		</tr>
	{/if}
	{if isset($footer.total_convenience_fee_te) && $footer.total_convenience_fee_te}
		<tr>
			<td class="grey" width="50%">{l s='Fees' pdf='true'}</td>
			<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$footer.total_convenience_fee_te}</td>
		</tr>
	{/if}
	<tr>
		<td class="grey" width="50%">{l s='Subtotal' pdf='true'}</td>
		<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$footer.total_without_discount_te}</td>
	</tr>
	{if $footer.total_tax_without_discount > 0}
		<tr>
			<td class="grey" width="50%">{l s='Tax' pdf='true'}</td>
			<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$footer.total_tax_without_discount}</td>
		</tr>
	{/if}
	{if $footer.product_discounts_tax_incl > 0}
		<tr>
			<td class="grey" width="50%">{l s='Discount' pdf='true'}</td>
			<td class="white right" width="50%">- {displayPrice currency=$order->id_currency price=$footer.product_discounts_tax_incl}</td>
		</tr>
	{/if}
	<tr class="bold">
		<td class="total-line" width="50%">{l s='Total' pdf='true'}</td>
		<td class="total-line right" width="50%">{displayPrice currency=$order->id_currency price=$sb.total}</td>
	</tr>
	<tr>
		<td class="grey" width="50%">{l s='Paid' pdf='true'}</td>
		<td class="white right" width="50%">{displayPrice currency=$order->id_currency price=$sb.paid}</td>
	</tr>
	<tr class="bold">
		<td class="due-{$sb.status|escape:'html':'UTF-8'}" width="50%">{l s='Balance due' pdf='true'}</td>
		<td class="due-{$sb.status|escape:'html':'UTF-8'} right" width="50%">{displayPrice currency=$order->id_currency price=$sb.balance}</td>
	</tr>
</table>