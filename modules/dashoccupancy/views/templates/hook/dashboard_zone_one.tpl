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
{* Salisberg: tonight as one bar, and the occupancy rate for the chosen period (owner, 2026-11-19). Values arrive from hookDashboardData. *}
<section id="dashoccupancy" class="panel widget allow_push sb-occupancy">
	<header class="panel-heading">
		<i class="icon-bar-chart"></i>
		<span>{l s='Occupancy' mod='dashoccupancy'}</span>
		<i class="icon-info-circle label-tooltip" data-toggle="tooltip" data-original-title="{l s='Tonight counts rooms with a guest checked in now, as on the desk. The rate is booked room-nights divided by room-nights available in the chosen period.' mod='dashoccupancy'}"></i>
		<span class="panel-heading-action">
			<a class="list-toolbar-btn" href="javascript:void(0);" title="{l s='Refresh' mod='dashoccupancy'}" onclick="refreshDashboard('dashoccupancy'); return false;">
				<i class="process-icon-refresh"></i>
			</a>
		</span>
	</header>
	<div class="sb-occ-tonight">
		<span class="sb-occ-label">{l s='Tonight' mod='dashoccupancy'}</span>
		<p class="sb-occ-big"><strong id="do_tonight_in">0</strong> <span>{l s='of' mod='dashoccupancy'} <span id="do_tonight_total">0</span> {l s='rooms' mod='dashoccupancy'}</span> <em id="do_tonight_pct">0%</em></p>
		<div class="sb-occ-bar" id="sbOccBar" role="img" aria-label="{l s='Rooms tonight: in house, free and out of service' mod='dashoccupancy'}"></div>
		<ul class="sb-occ-legend" id="sbOccLegend"></ul>
	</div>
	<div class="sb-occ-rate">
		<span class="sb-occ-label">{l s='Occupancy rate' mod='dashoccupancy'} <span id="do_rate_period"></span></span>
		<p class="sb-occ-big"><strong id="do_rate_pct">0%</strong> <span id="do_rate_detail"></span></p>
	</div>
</section>
