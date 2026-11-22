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

{assign var=color_header value="#F3EFE6"}
{assign var=color_border value="#12352C"}
{assign var=color_border_lighter value="#D9D4C7"}
{assign var=color_line_even value="#FFFFFF"}
{assign var=color_line_odd value="#FAF8F3"}
{assign var=font_size_text value="9pt"}
{assign var=font_size_header value="9pt"}
{assign var=font_size_product value="9pt"}
{assign var=height_header value="20px"}
{assign var=table_padding value="4px"}

<style>
	table, th, td {
		margin: 0!important;
		padding: 0!important;
		vertical-align: middle;
		font-size: {$font_size_text};
		white-space: nowrap;
	}

	table.product {
		border: 1px solid {$color_border};
		border-collapse: collapse;
	}

	table#addresses-tab tr td {
		font-size: 10pt;
		line-height: 140%;
	}

	table#summary-tab {
		padding: {$table_padding};
		border: 1px solid {$color_border_lighter};
	}
	table#total-tab {
		padding: {$table_padding};
		border: 1px solid {$color_border_lighter};
	}
	table#tax-tab {
		padding: {$table_padding};
		border: 1px solid {$color_border_lighter};
	}
	table#payment-tab, table#pay-tab {
		padding: {$table_padding};
		border: 1px solid {$color_border_lighter};
	}
	/* Section titles: the brand green band */
	th.section {
		background-color: #12352C;
		color: #FFFFFF;
		font-size: 9pt;
	}
	.label {
		color: #B98A2F;
		font-size: 8pt;
		font-weight: bold;
	}
	.muted {
		color: #6B7280;
	}
	td.total-line {
		background-color: #12352C;
		color: #FFFFFF;
	}
	/* What is owed: red while money is due, green once settled */
	td.due-unpaid, td.due-part {
		background-color: #FBEAE8;
		color: #B42318;
	}
	td.due-paid {
		background-color: #E7F4EC;
		color: #1E6B3C;
	}
	td.status-unpaid, td.status-part {
		color: #B42318;
	}
	td.status-paid {
		color: #1E6B3C;
	}
	table.bordered-table td, table.bordered-table th {
		border: 1px solid {$color_border_lighter};
		margin-bottom: -1px;
	}
	.border-right-td {
		border-right: 1px solid {$color_border_lighter};
	}
	.pull-right {
		float: right;
	}
	th.product {
		border-bottom: 1px solid {$color_border};
	}

	tr.discount th.header {
		border-top: 1px solid {$color_border};
	}

	tr.product td {
		border-bottom: 1px solid {$color_border_lighter};
	}

	tr.color_line_even {
		background-color: {$color_line_even};
	}

	tr.color_line_odd {
		background-color: {$color_line_odd};
	}

	tr.customization_data td {
	}

	td.product {
		vertical-align: middle;
		font-size: {$font_size_product};
	}

	th.header {
		font-size: {$font_size_header};
		height: {$height_header};
		background-color: {$color_header};
		vertical-align: middle;
		text-align: center;
		font-weight: bold;
	}

	th.header-right {
		font-size: {$font_size_header};
		height: {$height_header};
		background-color: {$color_header};
		vertical-align: middle;
		text-align: right;
		font-weight: bold;
	}

	th.header-left {
		font-size: {$font_size_header};
		height: {$height_header};
		background-color: {$color_header};
		vertical-align: middle;
		text-align: left;
		font-weight: bold;
	}

	th.payment {
		background-color: {$color_header};
		vertical-align: middle;
		font-weight: bold;
	}

	th.tva {
		background-color: {$color_header};
		vertical-align: middle;
		font-weight: bold;
	}

	tr.separator td {
		border-top: 1px solid #000000;
	}

	.left {
		text-align: left;
	}

	.fright {
		float: right;
	}

	.right {
		text-align: right;
	}

	.center {
		text-align: center;
	}

	.bold {
		font-weight: bold;
	}

	.border {
		border: 1px solid black;
	}
	.no_top_border {
		border-top:hidden;
		border-bottom:1px solid black;
		border-left:1px solid black;
		border-right:1px solid black;
	}

	.grey {
		background-color: {$color_header};

	}

	/* This is used for the border size */
	.white {
		background-color: #FFFFFF;
	}

	.big,
	tr.big td{
		font-size: 110%;
	}

	.small, table.small th, table.small td {
		font-size:small;
	}
	.tr-border-top td, .tr-border-top th {
		border-top:1px solid {$color_border_lighter};
	}
</style>
