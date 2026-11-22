{*
 Bookings > Today: four lists for the front desk, with the buttons on the rows.
 Data comes from Salisbergfrontdesk::getToday().
*}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-desk">
	{if $sb_on_dashboard && $sb_done == 'sb_check_in'}<div class="alert alert-success">{l s='The room is checked in.' mod='salisbergfrontdesk'}</div>{/if}
	{if $sb_on_dashboard && $sb_done == 'sb_check_out'}<div class="alert alert-success">{l s='The room is checked out and marked for cleaning.' mod='salisbergfrontdesk'}</div>{/if}
	{if !empty($sb_milestone)}
	<div class="sb-milestone" role="status">
		<span class="sb-milestone-badge">{$sb_milestone.n|intval}</span>
		<div>
			<strong>{l s='Congratulations! %s bookings.' sprintf=[$sb_milestone.n|intval] mod='salisbergfrontdesk'}</strong>
			<span>{l s='Booking %1$s, on %2$s, was number %3$s. Next: %4$s.' sprintf=[$sb_milestone.reference|escape:'html':'UTF-8', $sb_milestone.date|date_format:'%e %b %Y', $sb_milestone.n|intval, $sb_next_milestone|intval] mod='salisbergfrontdesk'}</span>
		</div>
		<a class="sb-milestone-close" href="{$sb_milestone_close|escape:'html':'UTF-8'}{$sb_milestone.n|intval}" aria-label="{l s='Close' mod='salisbergfrontdesk'}">&times;</a>
	</div>
	{/if}
	<div class="sb-desk-head">
		<div>
			<h2>{l s='Today at the desk' mod='salisbergfrontdesk'} <span class="sb-desk-date">{$sb_date|escape:'html':'UTF-8'}</span></h2>
		</div>
		<div class="sb-desk-links">
			<a class="btn btn-default" href="{$sb_housekeeping_link|escape:'html':'UTF-8'}"><i class="icon-home"></i> {l s='Housekeeping' mod='salisbergfrontdesk'}</a>
			<a class="btn btn-primary" href="{$sb_book_link|escape:'html':'UTF-8'}"><i class="icon-plus"></i> {l s='New booking' mod='salisbergfrontdesk'}</a>
		</div>
	</div>

	<div class="sb-desk-figures">
		<a class="sb-desk-figure" href="#sb-arrivals">
			<span>{l s='Arrivals' mod='salisbergfrontdesk'}</span><strong>{$sb_today.arrivals|count}</strong>
			<small>{if $sb_today.arrivals|count == 0}{l s='None today' mod='salisbergfrontdesk'}{elseif $sb_waiting}{$sb_waiting|intval} {l s='still to check in' mod='salisbergfrontdesk'}{else}{l s='All checked in' mod='salisbergfrontdesk'}{/if}</small>
			<i class="icon-chevron-right"></i>
		</a>
		<a class="sb-desk-figure" href="#sb-departures">
			<span>{l s='Leaving today' mod='salisbergfrontdesk'}</span><strong>{$sb_due_out|count}</strong>
			<small{if $sb_overdue|count} class="sb-desk-alert"{/if}>{if $sb_overdue|count}{$sb_overdue|count} {l s='overdue' mod='salisbergfrontdesk'}{else}{l s='None overdue' mod='salisbergfrontdesk'}{/if}</small>
			<i class="icon-chevron-right"></i>
		</a>
		<a class="sb-desk-figure" href="#sb-in-house">
			<span>{l s='Rooms in house' mod='salisbergfrontdesk'}</span><strong>{$sb_today.in_house|count}</strong>
			<small>{l s='Guests checked in now' mod='salisbergfrontdesk'}</small>
			<i class="icon-chevron-right"></i>
		</a>
		<a class="sb-desk-figure" href="#sb-unpaid">
			<span>{l s='Unpaid bookings' mod='salisbergfrontdesk'}</span><strong>{$sb_today.unpaid|count}</strong>
			<small{if $sb_today.unpaid|count} class="sb-desk-alert"{/if}>{if $sb_today.unpaid|count}{$sb_unpaid_owed|escape:'html':'UTF-8'} {l s='owed' mod='salisbergfrontdesk'}{else}{l s='Nothing owed' mod='salisbergfrontdesk'}{/if}</small>
			<i class="icon-chevron-right"></i>
		</a>
	</div>

	{if $sb_today.arrivals|count}
	<details class="panel sb-desk-panel" id="sb-arrivals"{if $sb_open_arrivals} open{/if}>
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-signin"></i> {l s='Arrivals' mod='salisbergfrontdesk'}</h3></summary>
		<div class="sb-desk-body">
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
				{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</details>
	{/if}

	{if $sb_due_out|count}
	<details class="panel sb-desk-panel" id="sb-departures"{if $sb_open_departures} open{/if}>
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-signout"></i> {l s='Leaving today' mod='salisbergfrontdesk'} <span class="sb-desk-count">{$sb_due_out|count}</span></h3></summary>
		<div class="sb-desk-body">
		<p class="sb-desk-note">{l s='Rooms due to leave today. Checking a room out marks it dirty for housekeeping.' mod='salisbergfrontdesk'}</p>
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
				{foreach from=$sb_due_out item=sb_row}
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
					<tr><td colspan="7" class="sb-desk-empty">{l s='Nobody is due to leave today.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</details>
	{/if}

	{if $sb_overdue|count}
	<details class="panel sb-desk-panel sb-desk-overdue sb-desk-act-bad" id="sb-overdue">
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-warning-sign"></i> {l s='Overdue' mod='salisbergfrontdesk'} <span class="sb-desk-count sb-desk-count-bad">{$sb_overdue|count}</span></h3></summary>
		<div class="sb-desk-body">
		<p class="sb-desk-note">{l s='Guests whose stay has ended but who were never checked out. Check them out, or open the booking if they are staying longer.' mod='salisbergfrontdesk'}</p>
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
				{foreach from=$sb_overdue item=sb_row}
					<tr>
						<td><strong>{$sb_row.room_num|escape:'html':'UTF-8'}</strong><br /><small>{$sb_row.room_type_name|escape:'html':'UTF-8'}</small></td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'} <span class="sb-chip sb-chip-bad">{if $sb_row.days_over == 1}{l s='1 day over' mod='salisbergfrontdesk'}{else}{$sb_row.days_over|intval} {l s='days over' mod='salisbergfrontdesk'}{/if}</span></td>
						<td>{if $sb_row.balance_text}<span class="sb-chip sb-chip-bad">{$sb_row.balance_text|escape:'html':'UTF-8'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Paid' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
						<td class="sb-desk-act">
							{if $sb_can_edit}
							<form method="post" action="{$sb_action|escape:'html':'UTF-8'}">
								<input type="hidden" name="id_booking" value="{$sb_row.id|intval}" />
								<button type="submit" name="sb_check_out" value="1" class="btn btn-primary"><i class="icon-signout"></i> {l s='Check out' mod='salisbergfrontdesk'}</button>
							</form>
							{else}
							<span class="sb-chip">{l s='In house' mod='salisbergfrontdesk'}</span>
							{/if}
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</details>
	{/if}

	{if $sb_checked_out|count}
	<details class="panel sb-desk-panel" id="sb-checked-out">
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-check"></i> {l s='Checked out today' mod='salisbergfrontdesk'}</h3></summary>
		<div class="sb-desk-body">
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
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_checked_out item=sb_row}
					<tr>
						<td><strong>{$sb_row.room_num|escape:'html':'UTF-8'}</strong><br /><small>{$sb_row.room_type_name|escape:'html':'UTF-8'}</small></td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}</td>
						<td>{if $sb_row.balance_text}<span class="sb-chip sb-chip-bad">{$sb_row.balance_text|escape:'html':'UTF-8'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Paid' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		</div>
	</details>
	{/if}

	<details class="panel sb-desk-panel" id="sb-in-house">
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-key"></i> {l s='Rooms in house' mod='salisbergfrontdesk'}</h3></summary>
		<div class="sb-desk-body">
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
	</details>

	<details class="panel sb-desk-panel{if $sb_today.unpaid|count} sb-desk-act-warn{/if}" id="sb-unpaid"{if $sb_open_unpaid} open{/if}>
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-money"></i> {l s='Unpaid bookings' mod='salisbergfrontdesk'} <span class="sb-desk-count">{$sb_today.unpaid|count}</span></h3></summary>
		<div class="sb-desk-body">
		<p class="sb-desk-note">{l s='Most urgent first: guests staying now, then stays that have ended, then the next arrivals. Open the booking to record a payment.' mod='salisbergfrontdesk'}</p>
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
				{foreach from=$sb_unpaid_shown item=sb_row}
					<tr>
						<td>{$sb_row.reference|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.guest|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.phone|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.room_nums|escape:'html':'UTF-8'}</td>
						<td>{$sb_row.stay|escape:'html':'UTF-8'}{if $sb_row.current} <span class="sb-chip sb-chip-warn">{l s='Staying now' mod='salisbergfrontdesk'}</span>{/if}</td>
						<td>{$sb_row.payment_short|escape:'html':'UTF-8'}</td>
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
		{if $sb_today.unpaid|count > $sb_unpaid_shown|count}
		<p class="sb-desk-more"><a href="{$sb_invoices_link|escape:'html':'UTF-8'}">{l s='See all' mod='salisbergfrontdesk'} {$sb_today.unpaid|count} {l s='unpaid bookings in Invoices' mod='salisbergfrontdesk'} <i class="icon-arrow-right"></i></a></p>
		{/if}
		</div>
	</details>
	{if isset($sb_booking_count)}
	<details class="panel sb-desk-panel sb-milestones" id="sb-milestones">
		<summary><h3><i class="icon-chevron-down"></i><i class="icon-trophy"></i> {l s='Milestones' mod='salisbergfrontdesk'} <span class="sb-desk-count">{$sb_booking_count|intval} {l s='bookings' mod='salisbergfrontdesk'}</span></h3></summary>
		<div class="sb-desk-body">
		<p class="sb-desk-note">{l s='Next milestone: %s bookings. Cancelled, refunded and deleted bookings do not count.' sprintf=[$sb_next_milestone|intval] mod='salisbergfrontdesk'}</p>
		{if $sb_milestones|count}
		<ul class="sb-milestone-list">
			{foreach $sb_milestones as $m}
			<li><strong>{$m.n|intval}</strong> {l s='bookings' mod='salisbergfrontdesk'} &middot; {$m.date|date_format:'%e %b %Y'} &middot; {$m.reference|escape:'html':'UTF-8'}</li>
			{/foreach}
		</ul>
		{/if}
		</div>
	</details>
	{/if}
</div>
<script>
(function () {
	function openPanel(id) {
		var panel = document.getElementById(id);
		if (panel && panel.tagName === 'DETAILS') {
			panel.open = true;
		}
	}
	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('a.sb-desk-figure') : null;
		if (!link) {
			return;
		}
		openPanel((link.getAttribute('href') || '').replace('#', ''));
	});
	if (window.location.hash) {
		openPanel(window.location.hash.slice(1));
	}
})();
</script>
