{*
 Bookings > Invoices: the invoices themselves, newest first, with what is
 paid and what is still owed. Data comes from
 AdminInvoicesController::renderInvoiceList().
*}
<div class="panel">
	<div class="panel-heading"><i class="icon-file-text"></i> {l s='Invoices'}</div>
	<form method="get" action="index.php" class="form-inline" style="margin-bottom:15px;">
		<input type="hidden" name="controller" value="AdminInvoices" />
		<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
		<div class="form-group">
			<label for="sb_q">{l s='Find'}</label>
			<input type="text" class="form-control" id="sb_q" name="sb_q" value="{$sb_filters.q|escape:'html':'UTF-8'}" placeholder="{l s='Invoice number, booking reference or guest'}" style="width:280px;" />
		</div>
		<div class="form-group">
			<label for="sb_status">{l s='Status'}</label>
			<select class="form-control" id="sb_status" name="sb_status">
				{foreach from=$sb_statuses key=sb_key item=sb_name}
				<option value="{$sb_key|escape:'html':'UTF-8'}"{if $sb_key == $sb_filters.status} selected="selected"{/if}>{$sb_name|escape:'html':'UTF-8'}</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group">
			<label for="sb_from">{l s='From'}</label>
			<input type="date" class="form-control" id="sb_from" name="sb_from" value="{$sb_filters.from|escape:'html':'UTF-8'}" />
		</div>
		<div class="form-group">
			<label for="sb_to">{l s='To'}</label>
			<input type="date" class="form-control" id="sb_to" name="sb_to" value="{$sb_filters.to|escape:'html':'UTF-8'}" />
		</div>
		<button type="submit" class="btn btn-primary"><i class="icon-search"></i> {l s='Show'}</button>
		<a class="btn btn-default" href="{$sb_self|escape:'html':'UTF-8'}">{l s='Reset'}</a>
	</form>

	<div class="row">
		<div class="col-sm-3"><div class="well well-sm"><small class="text-muted">{l s='Invoices shown'}</small><br /><strong style="font-size:18px;">{$sb_totals.count|intval}</strong></div></div>
		<div class="col-sm-3"><div class="well well-sm"><small class="text-muted">{l s='Total billed'}</small><br /><strong style="font-size:18px;">{$sb_totals.total|escape:'html':'UTF-8'}</strong></div></div>
		<div class="col-sm-3"><div class="well well-sm"><small class="text-muted">{l s='Paid'}</small><br /><strong style="font-size:18px;">{$sb_totals.paid|escape:'html':'UTF-8'}</strong></div></div>
		<div class="col-sm-3"><div class="well well-sm"><small class="text-muted">{l s='Still to pay'}</small><br /><strong style="font-size:18px;">{$sb_totals.balance|escape:'html':'UTF-8'}</strong></div></div>
	</div>

	<div class="table-responsive-row clearfix">
		<table class="table">
			<thead>
				<tr>
					<th>{l s='Invoice'}</th>
					<th>{l s='Date'}</th>
					<th>{l s='Booking'}</th>
					<th>{l s='Guest'}</th>
					<th>{l s='Stay'}</th>
					<th class="text-right">{l s='Total'}</th>
					<th class="text-right">{l s='Paid'}</th>
					<th class="text-right">{l s='Still to pay'}</th>
					<th>{l s='Status'}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$sb_invoices item=sb_row}
				<tr>
					<td><strong>{$sb_row.number|escape:'html':'UTF-8'}</strong></td>
					<td>{$sb_row.date|escape:'html':'UTF-8'}</td>
					<td><a href="{$sb_booking_link|escape:'html':'UTF-8'}{$sb_row.id_order|intval}">{$sb_row.reference|escape:'html':'UTF-8'}</a></td>
					<td>{$sb_row.guest|escape:'html':'UTF-8'}{if $sb_row.phone}<br /><small class="text-muted">{$sb_row.phone|escape:'html':'UTF-8'}</small>{/if}</td>
					<td>{$sb_row.stay|escape:'html':'UTF-8'}</td>
					<td class="text-right">{$sb_row.total|escape:'html':'UTF-8'}</td>
					<td class="text-right">{$sb_row.paid|escape:'html':'UTF-8'}</td>
					<td class="text-right"><strong>{$sb_row.balance|escape:'html':'UTF-8'}</strong></td>
					<td>
						{if $sb_row.status == 'paid'}<span class="label label-success">{l s='Paid'}</span>
						{elseif $sb_row.status == 'part'}<span class="label label-warning">{l s='Partly paid'}</span>
						{else}<span class="label label-danger">{l s='Not yet paid'}</span>{/if}
					</td>
					<td class="text-right">
						<a class="btn btn-default" target="_blank" href="{$sb_pdf_link|escape:'html':'UTF-8'}{$sb_row.id_order_invoice|intval}"><i class="icon-file-text"></i> {if $sb_row.status == 'paid'}{l s='Receipt'}{else}{l s='Invoice'}{/if} (PDF)</a>
					</td>
				</tr>
			{foreachelse}
				<tr><td colspan="10" class="text-center text-muted" style="padding:24px;">{l s='No invoices match.'}</td></tr>
			{/foreach}
			</tbody>
		</table>
	</div>
	{if $sb_limited}
		<p class="help-block">{l s='Only the newest %d are shown. Use Find or the dates to narrow the list.' sprintf=[$sb_limit]}</p>
	{/if}
</div>
