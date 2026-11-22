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

{if isset($cart_htl_data) && $cart_htl_data}
	<table class="bordered-table" width="100%" cellpadding="5" cellspacing="0">
		<thead>
			<tr>
				<th colspan="7" class="header-left section">{l s='Your stay' pdf='true'}</th>
			</tr>
			<tr>
				<th class="product header small" width="21%">{l s='Room' pdf='true'}</th>
				<th class="product header small" width="13%">{l s='Check-in' pdf='true'}</th>
				<th class="product header small" width="13%">{l s='Check-out' pdf='true'}</th>
				<th class="product header small" width="10%">{l s='Nights' pdf='true'}</th>
				<th class="product header small" width="12%">{l s='Guests' pdf='true'}</th>
				<th class="product header small" width="16%">{l s='Rate per night' pdf='true'}</th>
				<th class="product header small" width="15%">{l s='Amount' pdf='true'}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$cart_htl_data key=data_k item=data_v}
				{foreach from=$data_v['date_diff'] key=rm_k item=rm_v}
					{cycle values=["color_line_even", "color_line_odd"] assign=bgcolor_class}
					<tr class="{$bgcolor_class}">
						<td class="product left" width="21%">
							<span class="bold">{$data_v['name']|escape:'html':'UTF-8'}</span>{if $rm_v['num_rm'] > 1} x {$rm_v['num_rm']|intval}{/if}
							{if !empty($rm_v['room_nums'])}<br /><span class="muted">{if $rm_v['num_rm'] > 1}{l s='Rooms' pdf='true'}{else}{l s='Room' pdf='true'}{/if} {foreach from=$rm_v['room_nums'] item=room_num name=room_nums}{$room_num|escape:'html':'UTF-8'}{if !$smarty.foreach.room_nums.last}, {/if}{/foreach}</span>{/if}
						</td>
						<td class="product center" width="13%">{$rm_v['stay_from']|escape:'html':'UTF-8'}</td>
						<td class="product center" width="13%">{$rm_v['stay_to']|escape:'html':'UTF-8'}</td>
						<td class="product center" width="10%">{$rm_v['num_days']|intval}</td>
						<td class="product center" width="12%">
							{$rm_v['adults']|intval} {if $rm_v['adults'] > 1}{l s='adults' pdf='true'}{else}{l s='adult' pdf='true'}{/if}{if $rm_v['children']}<br />{$rm_v['children']|intval} {if $rm_v['children'] > 1}{l s='children' pdf='true'}{else}{l s='child' pdf='true'}{/if}{/if}
						</td>
						<td class="product right" width="16%">{displayPrice currency=$order->id_currency price=$rm_v['avg_paid_unit_price_tax_excl']}</td>
						<td class="product right" width="15%">{displayPrice currency=$order->id_currency price=$rm_v['amount']}</td>
					</tr>
				{/foreach}
			{/foreach}
		</tbody>
	</table>
	<tr>
		<td colspan="12" height="10"></td>
	</tr>
{/if}