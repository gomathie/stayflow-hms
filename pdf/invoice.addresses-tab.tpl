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
<table id="addresses-tab" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td width="48%">
			<span class="label">{l s='From' pdf='true'}</span><br/>
			{if !empty($hotel_address)}
				{$hotel_address}
			{elseif isset($order_invoice)}
				{$order_invoice->shop_address}
			{/if}
		</td>
		<td width="4%"></td>
		<td width="48%">
			<span class="label">{l s='Billed to' pdf='true'}</span><br/>
			<span class="bold">{$sb.guest_name|escape:'html':'UTF-8'}</span><br/>
			{if $sb.guest_phone}{$sb.guest_phone|escape:'html':'UTF-8'}<br/>{/if}
			{if $sb.guest_email}{$sb.guest_email|escape:'html':'UTF-8'}<br/>{/if}
			{if $invoice_address && isset($addresses.invoice) && $addresses.invoice->address1}
				{$addresses.invoice->address1|escape:'html':'UTF-8'}{if $addresses.invoice->city}, {$addresses.invoice->city|escape:'html':'UTF-8'}{/if}
			{/if}
		</td>
	</tr>
</table>