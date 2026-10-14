{*
 Bookings > Today: four lists for the front desk, with the buttons on the rows.
 Data comes from Salisbergfrontdesk::getToday().
*}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-desk">
	<div class="sb-desk-head">
		<div>
			<h2>{l s='Today at the desk' mod='salisbergfrontdesk'}</h2>
			<p>{$sb_date|escape:'html':'UTF-8'}</p>
		</div>
		<div class="sb-desk-links">
			<a class="btn btn-default" href="{$sb_housekeeping_link|escape:'html':'UTF-8'}"><i class="icon-home"></i> {l s='Housekeeping' mod='salisbergfrontdesk'}</a>
			<a class="btn btn-primary" href="{$sb_book_link|escape:'html':'UTF-8'}"><i class="icon-th"></i> {l s='Open room board' mod='salisbergfrontdesk'}</a>
		</div>
	</div>

	<div class="sb-desk-figures">
		<a class="sb-desk-figure" href="#sb-arrivals"><span>{l s='Arrivals' mod='salisbergfrontdesk'}</span><strong>{$sb_today.arrivals|count}</strong></a>
		<a class="sb-desk-figure" href="#sb-departures"><span>{l s='Departures' mod='salisbergfrontdesk'}</span><strong>{$sb_today.departures|count}</strong></a>
		<a class="sb-desk-figure" href="#sb-in-house"><span>{l s='Rooms in house' mod='salisbergfrontdesk'}</span><strong>{$sb_today.in_house|count}</strong></a>
		<a class="sb-desk-figure" href="#sb-unpaid"><span>{l s='Unpaid bookings' mod='salisbergfrontdesk'}</span><strong>{$sb_today.unpaid|count}</strong></a>
	</div>

	<div class="panel sb-desk-panel" id="sb-arrivals">
		<h3><i class="icon-signin"></i> {l s='Arrivals' mod='salisbergfrontdesk'}</h3>
		<p class="sb-desk-note">{l s='Rooms due to arrive today, rooms due earlier that have not arrived yet, and rooms checked in today.' mod='salisbergfrontdesk'}</p>
		<div class="sb-desk-scroll">
			<table class="table">
				<thead>
					<tr>
						<th>{l s='Room' mod='salisbergfrontdesk'}</th>
						<th>{l s='Guest' mod='salisbergfrontdesk'}</th>
						<th>{l s='Phone' mod='salisbergfrontdesk'}</th>
						<th>{l s='Adults + children' mod='salisbergfrontdesk'}</th>
						<th>{l s='Stay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Still to pay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Room is' mod='salisbergfrontdesk'}</th>
						<th>{l s='Booking' mod='salisbergfrontdesk'}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_today.arrivals item=sb_row}
					<tr>
						<td><strong>{$sb_row.room_num|escape:'html':'UTF-8'}</strong><br /><small>{$sb_row.room_type_name|escape:'html':'UTF-8'}</small></td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.guests|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}{if $sb_row.late} <span class="sb-chip sb-chip-warn">{l s='Late' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td>{if $sb_row.balance_text}<span class="sb-chip sb-chip-bad">{$sb_row.balance_text|escape:'html':'UTF-8'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Paid' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td>{include file="./room_state.tpl" sb_state=$sb_row.room_label}</td>
						<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
						<td class="sb-desk-act">
							{if $sb_row.id_status == $sb_expected}
								{if $sb_can_edit}
								<form method="post" action="{$sb_action|escape:'html':'UTF-8'}">
									<input type="hidden" name="id_booking" value="{$sb_row.id|intval}" />
									<button type="submit" name="sb_check_in" value="1" class="btn btn-success"><i class="icon-signin"></i> {l s='Check in' mod='salisbergfrontdesk'}</button>
								</form>
								{else}
								<span class="sb-chip">{l s='Expected' mod='salisbergfrontdesk'}</span>
								{/if}
							{else}
								<span class="sb-chip sb-chip-good">{l s='Checked in' mod='salisbergfrontdesk'}</span>
							{/if}
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="9" class="sb-desk-empty">{l s='No arrivals today.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>

	<div class="panel sb-desk-panel" id="sb-departures">
		<h3><i class="icon-signout"></i> {l s='Departures' mod='salisbergfrontdesk'}</h3>
		<p class="sb-desk-note">{l s='Rooms due to leave today, rooms that should already have left, and rooms checked out today. Checking a room out marks it dirty for housekeeping.' mod='salisbergfrontdesk'}</p>
		<div class="sb-desk-scroll">
			<table class="table">
				<thead>
					<tr>
						<th>{l s='Room' mod='salisbergfrontdesk'}</th>
						<th>{l s='Guest' mod='salisbergfrontdesk'}</th>
						<th>{l s='Phone' mod='salisbergfrontdesk'}</th>
						<th>{l s='Stay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Still to pay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Booking' mod='salisbergfrontdesk'}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_today.departures item=sb_row}
					<tr>
						<td><strong>{$sb_row.room_num|escape:'html':'UTF-8'}</strong><br /><small>{$sb_row.room_type_name|escape:'html':'UTF-8'}</small></td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}{if $sb_row.overdue} <span class="sb-chip sb-chip-bad">{l s='Overdue' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td>{if $sb_row.balance_text}<span class="sb-chip sb-chip-bad">{$sb_row.balance_text|escape:'html':'UTF-8'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Paid' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
						<td class="sb-desk-act">
							{if $sb_row.id_status == $sb_in}
								{if $sb_can_edit}
								<form method="post" action="{$sb_action|escape:'html':'UTF-8'}">
									<input type="hidden" name="id_booking" value="{$sb_row.id|intval}" />
									<button type="submit" name="sb_check_out" value="1" class="btn btn-primary"><i class="icon-signout"></i> {l s='Check out' mod='salisbergfrontdesk'}</button>
								</form>
								{else}
								<span class="sb-chip">{l s='In house' mod='salisbergfrontdesk'}</span>
								{/if}
							{else}
								<span class="sb-chip sb-chip-good">{l s='Checked out' mod='salisbergfrontdesk'}</span>
							{/if}
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="7" class="sb-desk-empty">{l s='No departures today.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>

	<div class="panel sb-desk-panel" id="sb-in-house">
		<h3><i class="icon-key"></i> {l s='Rooms in house' mod='salisbergfrontdesk'}</h3>
		<p class="sb-desk-note">{l s='Every room with a guest checked in right now.' mod='salisbergfrontdesk'}</p>
		<div class="sb-desk-scroll">
			<table class="table">
				<thead>
					<tr>
						<th>{l s='Room' mod='salisbergfrontdesk'}</th>
						<th>{l s='Guest' mod='salisbergfrontdesk'}</th>
						<th>{l s='Phone' mod='salisbergfrontdesk'}</th>
						<th>{l s='Adults + children' mod='salisbergfrontdesk'}</th>
						<th>{l s='Stay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Still to pay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Booking' mod='salisbergfrontdesk'}</th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_today.in_house item=sb_row}
					<tr>
						<td><strong>{$sb_row.room_num|escape:'html':'UTF-8'}</strong><br /><small>{$sb_row.room_type_name|escape:'html':'UTF-8'}</small></td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.guests|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}{if $sb_row.overdue} <span class="sb-chip sb-chip-bad">{l s='Overdue' mod='salisbergfrontdesk'}</span>{elseif $sb_row.leaves_today} <span class="sb-chip sb-chip-warn">{l s='Leaves today' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td>{if $sb_row.balance_text}<span class="sb-chip sb-chip-bad">{$sb_row.balance_text|escape:'html':'UTF-8'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Paid' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
					</tr>
				{foreachelse}
					<tr><td colspan="7" class="sb-desk-empty">{l s='No guests are checked in.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>

	<div class="panel sb-desk-panel" id="sb-unpaid">
		<h3><i class="icon-money"></i> {l s='Unpaid bookings' mod='salisbergfrontdesk'}</h3>
		<p class="sb-desk-note">{l s='Bookings with money still to pay, for stays that are coming, under way, or ended in the last 30 days. Open the booking to record a payment.' mod='salisbergfrontdesk'}</p>
		<div class="sb-desk-scroll">
			<table class="table">
				<thead>
					<tr>
						<th>{l s='Booking' mod='salisbergfrontdesk'}</th>
						<th>{l s='Guest' mod='salisbergfrontdesk'}</th>
						<th>{l s='Phone' mod='salisbergfrontdesk'}</th>
						<th>{l s='Rooms' mod='salisbergfrontdesk'}</th>
						<th>{l s='Stay' mod='salisbergfrontdesk'}</th>
						<th>{l s='Payment' mod='salisbergfrontdesk'}</th>
						<th class="text-right">{l s='Total' mod='salisbergfrontdesk'}</th>
						<th class="text-right">{l s='Paid' mod='salisbergfrontdesk'}</th>
						<th class="text-right">{l s='Still to pay' mod='salisbergfrontdesk'}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_today.unpaid item=sb_row}
					<tr>
						<td>{$sb_row.reference|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.room_nums|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.payment|escape:'html':'UTF-8'}</td>
						<td class="text-right">{$sb_row.total_text|escape:'html':'UTF-8'}</td>
						<td class="text-right">{$sb_row.paid_text|escape:'html':'UTF-8'}</td>
						<td class="text-right"><strong>{$sb_row.balance_text|escape:'html':'UTF-8'}</strong></td>
						<td class="sb-desk-act"><a class="btn btn-default" href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}"><i class="icon-money"></i> {l s='Open booking' mod='salisbergfrontdesk'}</a></td>
					</tr>
				{foreachelse}
					<tr><td colspan="10" class="sb-desk-empty">{l s='Nothing is owed.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
