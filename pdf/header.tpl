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

{* Top of every PDF: the logo, then the name of the document, its number and its date *}
<table style="width: 100%">
<tr>
	<td style="width: 45%">
		{if $logo_path}
			<img src="{$logo_path}" width="150" />
		{/if}
	</td>
	<td style="width: 55%; text-align: right;">
		<table style="width: 100%">
			<tr>
				<td style="font-weight: bold; font-size: 20pt; color: #12352c; width: 100%">{if isset($header)}{$header|escape:'html':'UTF-8'|upper}{/if}</td>
			</tr>
			<tr>
				<td style="font-size: 11pt; color: #b98a2f; font-weight: bold;">{$title|escape:'html':'UTF-8'}</td>
			</tr>
			<tr>
				<td style="font-size: 9pt; color: #6b7280">{$date|escape:'html':'UTF-8'}</td>
			</tr>
		</table>
	</td>
</tr>
</table>
<table style="width: 100%"><tr><td style="border-bottom: 2px solid #12352c; font-size: 2pt;">&nbsp;</td></tr></table>
