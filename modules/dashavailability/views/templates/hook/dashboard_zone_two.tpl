{**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
*}

<div class="col-md-12 col-lg-12">
	<section id="dashavailability" class="panel widget allow_push sb-avail">
		<header class="panel-heading">
			<i class="icon-bar-chart"></i>
			{l s='Rooms free' mod='dashavailability'}
			<span class="panel-heading-action">
				<a class="list-toolbar-btn" href="javascript:void(0);" onclick="refreshAvailabilityBarData();" title="{l s='Refresh' mod='dashavailability'}">
					<i class="process-icon-refresh"></i>
				</a>
			</span>
		</header>
		<div class="sb-avail-toolbar">
			<div class="sb-avail-from">
				<button class="sb-avail-date" type="button" id="avail_datepicker" onclick="availDatePicker()">
					<i class="icon-calendar-empty"></i>
					<span>{l s='From' mod='dashavailability'} <strong class="bar-date">{$dateFromLabel|escape:'html':'UTF-8'}</strong></span>
					<i class="icon-caret-down"></i>
				</button>
				<input type="text" id="bardate" name="datepickerFrom" value="{$dateFromBar|escape:'html':'UTF-8'}" aria-hidden="true" tabindex="-1">
			</div>
			<div class="sb-avail-range" role="group" aria-label="{l s='Period' mod='dashavailability'}">
				<button type="button" class="avail-bar-btn bar-btn-active" data-days="7" aria-pressed="true">{l s='7 nights' mod='dashavailability'}</button>
				<button type="button" class="avail-bar-btn" data-days="14" aria-pressed="false">{l s='14 nights' mod='dashavailability'}</button>
				<button type="button" class="avail-bar-btn" data-days="30" aria-pressed="false">{l s='30 nights' mod='dashavailability'}</button>
			</div>
		</div>
		<dl class="sb-avail-summary">
			<div><dt>{l s='Tightest night' mod='dashavailability'}</dt><dd id="sb_avail_low">&nbsp;</dd></div>
			<div><dt>{l s='On average' mod='dashavailability'}</dt><dd id="sb_avail_avg">&nbsp;</dd></div>
		</dl>
		<div class="avil-chart-svg" id="availability_line_chart1">
			<svg></svg>
		</div>
	</section>
</div>
