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
<table id="summary-tab" width="100%" cellpadding="5" cellspacing="0">
	<tr>
		<th class="header small" valign="middle">{l s='Invoice number' pdf='true'}</th>
		<th class="header small" valign="middle">{l s='Invoice date' pdf='true'}</th>
		<th class="header small" valign="middle">{l s='Booking reference' pdf='true'}</th>
		<th class="header small" valign="middle">{l s='Booked on' pdf='true'}</th>
		<th class="header small" valign="middle">{l s='Status' pdf='true'}</th>
	</tr>
	<tr>
		<td class="center white">{$sb.number|escape:'html':'UTF-8'}</td>
		<td class="center white">{$sb.date|escape:'html':'UTF-8'}</td>
		<td class="center white bold">{$sb.booking_reference|escape:'html':'UTF-8'}</td>
		<td class="center white">{$sb.booking_date|escape:'html':'UTF-8'}</td>
		<td class="center white bold status-{$sb.status|escape:'html':'UTF-8'}">{$sb.status_label|escape:'html':'UTF-8'}</td>
	</tr>
</table>