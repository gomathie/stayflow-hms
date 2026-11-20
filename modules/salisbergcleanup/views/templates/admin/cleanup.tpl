{* Advanced Parameters > Data clean-up. Data from AdminSalisbergCleanupController. *}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-clean">
	<div class="alert alert-info">
		{l s='Paid bookings and invoices are kept for %d years and are never removed here. A deleted booking stays in the recycle bin for %d days first. A backup is made every night; removing data here cannot be undone from this page.' sprintf=[$sb_keep_years, $sb_bin_days] mod='salisbergcleanup'}
	</div>

	<div class="panel">
		<h3><i class="icon-list"></i> {l s='What could be removed now' mod='salisbergcleanup'}</h3>
		<div class="sb-clean-scroll">
		<table class="table">
			<thead><tr><th>{l s='Kind' mod='salisbergcleanup'}</th><th>{l s='Older than' mod='salisbergcleanup'}</th><th class="text-right">{l s='Items' mod='salisbergcleanup'}</th><th>{l s='Cleaned' mod='salisbergcleanup'}</th></tr></thead>
			<tbody>
			{foreach from=$sb_summary key=sb_key item=sb_row}
				<tr>
					<td><a href="{$sb_action|escape:'html':'UTF-8'}&amp;sb_kind={$sb_key|escape:'html':'UTF-8'}">{$sb_row.name|escape:'html':'UTF-8'}</a></td>
					<td>{l s='%d days' sprintf=[$sb_row.days] mod='salisbergcleanup'}</td>
					<td class="text-right"><strong>{$sb_row.count|intval}</strong></td>
					<td>{if $sb_row.locked}{l s='Not yet: see below' mod='salisbergcleanup'}{elseif $sb_row.auto}{l s='Automatically, daily' mod='salisbergcleanup'}{else}{l s='When you run it' mod='salisbergcleanup'}{/if}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		</div>
	</div>

	<div class="panel">
		<h3><i class="icon-trash"></i> {l s='Remove old data' mod='salisbergcleanup'}</h3>
		<form method="get" action="index.php" class="sb-clean-choose">
			<input type="hidden" name="controller" value="AdminSalisbergCleanup" />
			<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
			<div class="form-group">
				<label for="sb_kind">{l s='What' mod='salisbergcleanup'}</label>
				<select id="sb_kind" name="sb_kind" class="form-control" onchange="this.form.sb_days.value=''; this.form.submit()">
					{foreach from=$sb_kinds key=sb_key item=sb_def}
					<option value="{$sb_key|escape:'html':'UTF-8'}"{if $sb_key == $sb_kind} selected="selected"{/if}>{$sb_def.name|escape:'html':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>
			<div class="form-group">
				<label for="sb_days">{l s='Older than (days)' mod='salisbergcleanup'}</label>
				<input type="number" id="sb_days" name="sb_days" class="form-control" min="{$sb_min|intval}" max="3650" value="{$sb_days|intval}" />
			</div>
			<button type="submit" class="btn btn-default"><i class="icon-search"></i> {l s='Show what would go' mod='salisbergcleanup'}</button>
		</form>
		<p class="help-block">{$sb_kinds[$sb_kind].help|escape:'html':'UTF-8'} {l s='Shortest age allowed: %d days.' sprintf=[$sb_min] mod='salisbergcleanup'}</p>

		<div class="sb-clean-result">
			<p class="sb-clean-count"><strong>{$sb_count|intval}</strong> {l s='items last changed before %s.' sprintf=[$sb_cutoff] mod='salisbergcleanup'}</p>
			{if $sb_sample}
			<ul class="sb-clean-sample">
				{foreach from=$sb_sample item=sb_item}<li><span>{$sb_item.label|escape:'html':'UTF-8'}</span> <small>{$sb_item.date|escape:'html':'UTF-8'}</small></li>{/foreach}
				{if $sb_count > $sb_sample|@count}<li class="text-muted">{l s='and %d more' sprintf=[$sb_count - $sb_sample|@count] mod='salisbergcleanup'}</li>{/if}
			</ul>
			{/if}

			{if !empty($sb_kinds[$sb_kind].locked)}
				<p class="alert alert-warning">{l s='Removing bookings for good is not offered yet: no deleted booking is older than the %d-year keep. They stay in the recycle bin, out of every list and report.' sprintf=[$sb_keep_years] mod='salisbergcleanup'}</p>
			{elseif $sb_count}
			<form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;sb_kind={$sb_kind|escape:'html':'UTF-8'}&amp;sb_days={$sb_days|intval}" class="sb-clean-confirm">
				<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
				<input type="hidden" name="sb_kind" value="{$sb_kind|escape:'html':'UTF-8'}" />
				<input type="hidden" name="sb_days" value="{$sb_days|intval}" />
				<label for="sb_confirm">{l s='To remove them, type %d' sprintf=[$sb_count] mod='salisbergcleanup'}</label>
				<input type="text" id="sb_confirm" name="sb_confirm" class="form-control" inputmode="numeric" autocomplete="off" />
				<button type="submit" name="sb_clean" value="1" class="btn btn-danger"><i class="icon-trash"></i> {l s='Remove' mod='salisbergcleanup'}</button>
			</form>
			{/if}
		</div>
	</div>

	<div class="panel">
		<h3><i class="icon-time"></i> {l s='Automatic clean-up' mod='salisbergcleanup'}</h3>
		<p>{l s='Once a day, from the first back office page opened that day: unfinished booking drafts and abandoned carts older than 30 days, and system logs older than 90 days.' mod='salisbergcleanup'}
		{if $sb_last_auto}{l s='Last run: %s.' sprintf=[$sb_last_auto] mod='salisbergcleanup'}{/if}</p>
		<form method="post" action="{$sb_action|escape:'html':'UTF-8'}" class="form-inline">
			<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
			<label class="radio-inline"><input type="radio" name="sb_auto_on" value="1"{if $sb_auto_on} checked="checked"{/if} /> {l s='On' mod='salisbergcleanup'}</label>
			<label class="radio-inline"><input type="radio" name="sb_auto_on" value="0"{if !$sb_auto_on} checked="checked"{/if} /> {l s='Off' mod='salisbergcleanup'}</label>
			<button type="submit" name="sb_auto" value="1" class="btn btn-default">{l s='Save' mod='salisbergcleanup'}</button>
		</form>
	</div>

	<div class="panel">
		<h3><i class="icon-wrench"></i> {l s='Database check' mod='salisbergcleanup'}</h3>
		<p>{l s='Finds and removes leftover rows that point to records which no longer exist. It does not remove bookings, guests or rooms.' mod='salisbergcleanup'}</p>
		<form method="post" action="{$sb_action|escape:'html':'UTF-8'}">
			<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
			<button type="submit" name="sb_check" value="1" class="btn btn-default"><i class="icon-check"></i> {l s='Check and fix' mod='salisbergcleanup'}</button>
		</form>
	</div>
</div>
