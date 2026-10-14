{*
 Bookings > Housekeeping: every room, its state, and the buttons to change it.
 Data comes from Salisbergfrontdesk::getRooms().
*}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-desk">
	<div class="sb-desk-head">
		<div>
			<h2>{l s='Housekeeping' mod='salisbergfrontdesk'}</h2>
			<p>{l s='A room turns dirty when its guest is checked out. Mark it clean when it is ready for the next guest.' mod='salisbergfrontdesk'}</p>
		</div>
		<div class="sb-desk-links">
			<a class="btn btn-default" href="{$sb_today_link|escape:'html':'UTF-8'}"><i class="icon-calendar"></i> {l s='Today' mod='salisbergfrontdesk'}</a>
		</div>
	</div>

	<div class="sb-desk-figures">
		<a class="sb-desk-figure{if $sb_show == 'all'} sb-on{/if}" href="{$sb_action|escape:'html':'UTF-8'}&amp;sb_show=all"><span>{l s='All rooms' mod='salisbergfrontdesk'}</span><strong>{$sb_counts.all|intval}</strong></a>
		<a class="sb-desk-figure{if $sb_show == 'clean'} sb-on{/if}" href="{$sb_action|escape:'html':'UTF-8'}&amp;sb_show=clean"><span>{l s='Clean' mod='salisbergfrontdesk'}</span><strong>{$sb_counts.clean|intval}</strong></a>
		<a class="sb-desk-figure{if $sb_show == 'dirty'} sb-on{/if}" href="{$sb_action|escape:'html':'UTF-8'}&amp;sb_show=dirty"><span>{l s='Dirty' mod='salisbergfrontdesk'}</span><strong>{$sb_counts.dirty|intval}</strong></a>
		<a class="sb-desk-figure{if $sb_show == 'out'} sb-on{/if}" href="{$sb_action|escape:'html':'UTF-8'}&amp;sb_show=out"><span>{l s='Out of service' mod='salisbergfrontdesk'}</span><strong>{$sb_counts.out|intval}</strong></a>
	</div>

	<div class="panel sb-desk-panel">
		<div class="sb-desk-scroll">
			<table class="table">
				<thead>
					<tr>
						<th>{l s='Room' mod='salisbergfrontdesk'}</th>
						<th>{l s='Room type' mod='salisbergfrontdesk'}</th>
						<th>{l s='Floor' mod='salisbergfrontdesk'}</th>
						<th>{l s='Now' mod='salisbergfrontdesk'}</th>
						<th>{l s='Room is' mod='salisbergfrontdesk'}</th>
						<th>{l s='Last changed' mod='salisbergfrontdesk'}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$sb_rooms item=sb_room}
					<tr>
						<td><strong>{$sb_room.room_num|escape:'html':'UTF-8'}</strong></td>
						<td>{$sb_room.room_type|escape:'html':'UTF-8'}</td>
						<td>{$sb_room.floor|escape:'html':'UTF-8'}</td>
						<td>
							{if $sb_room.guest_in}{l s='Guest in room:' mod='salisbergfrontdesk'} {$sb_room.guest_in|escape:'html':'UTF-8'}
							{elseif $sb_room.arriving}<span class="sb-chip sb-chip-warn">{l s='Guest arrives today' mod='salisbergfrontdesk'}</span>
							{else}<span class="sb-desk-muted">{l s='Empty' mod='salisbergfrontdesk'}</span>{/if}
							{if $sb_room.state == 'out' && $sb_room.coming}<br /><span class="sb-chip sb-chip-bad">{l s='Has coming bookings: move them to another room' mod='salisbergfrontdesk'} ({$sb_room.coming|intval})</span>{/if}
						</td>
						<td>{include file="./room_state.tpl" sb_state=$sb_room.state}</td>
						<td>{if $sb_room.changed}{$sb_room.changed|escape:'html':'UTF-8'}{if $sb_room.changed_by}<br /><small>{$sb_room.changed_by|escape:'html':'UTF-8'}</small>{/if}{/if}</td>
						<td class="sb-desk-act">
							{if $sb_can_edit}
							<form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;sb_show={$sb_show|escape:'html':'UTF-8'}">
								<input type="hidden" name="id_room" value="{$sb_room.id_room|intval}" />
								{if $sb_room.fixed}
									<span class="sb-desk-muted">{l s='Closed for set dates in its room type' mod='salisbergfrontdesk'}</span>
								{elseif $sb_room.state == 'out'}
									<button type="submit" name="sb_room_state" value="back" class="btn btn-default"><i class="icon-undo"></i> {l s='Back in service' mod='salisbergfrontdesk'}</button>
								{else}
									{if $sb_room.state == 'dirty'}
									<button type="submit" name="sb_room_state" value="clean" class="btn btn-success"><i class="icon-check"></i> {l s='Mark clean' mod='salisbergfrontdesk'}</button>
									{else}
									<button type="submit" name="sb_room_state" value="dirty" class="btn btn-default"><i class="icon-remove"></i> {l s='Mark dirty' mod='salisbergfrontdesk'}</button>
									{/if}
									<button type="submit" name="sb_room_state" value="out" class="btn btn-default"><i class="icon-wrench"></i> {l s='Out of service' mod='salisbergfrontdesk'}</button>
								{/if}
							</form>
							{/if}
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="7" class="sb-desk-empty">{l s='No rooms to show.' mod='salisbergfrontdesk'}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
	</div>
</div>
