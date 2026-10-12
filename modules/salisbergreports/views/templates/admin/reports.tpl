{*
 Bookings > Reports: the form, then the chosen report on screen.
 The PDF is built from the same data by pdf_header.tpl, pdf_content.tpl and pdf_footer.tpl.
*}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-reports">
	<form method="get" action="index.php" class="panel sb-reports-form">
		<input type="hidden" name="controller" value="AdminSalisbergReports" />
		<input type="hidden" name="token" value="{$sb_token|escape:'html':'UTF-8'}" />
		<div class="sb-reports-field">
			<label for="sb_report">{l s='Report' mod='salisbergreports'}</label>
			<select name="sb_report" id="sb_report">
				{foreach from=$sb_reports key=sb_key item=sb_name}
				<option value="{$sb_key|escape:'html':'UTF-8'}"{if $sb_key == $sb_type} selected="selected"{/if}>{$sb_name|escape:'html':'UTF-8'}</option>
				{/foreach}
			</select>
		</div>
		<div class="sb-reports-field">
			<label for="sb_period">{l s='Period' mod='salisbergreports'}</label>
			<select name="sb_period" id="sb_period">
				{foreach from=$sb_periods key=sb_key item=sb_name}
				<option value="{$sb_key|escape:'html':'UTF-8'}"{if $sb_key == $sb_period} selected="selected"{/if}>{$sb_name|escape:'html':'UTF-8'}</option>
				{/foreach}
			</select>
		</div>
		<div class="sb-reports-field sb-reports-dates">
			<label for="sb_from">{l s='From' mod='salisbergreports'}</label>
			<input type="date" name="sb_from" id="sb_from" value="{$sb_from|escape:'html':'UTF-8'}" />
		</div>
		<div class="sb-reports-field sb-reports-dates">
			<label for="sb_to">{l s='To' mod='salisbergreports'}</label>
			<input type="date" name="sb_to" id="sb_to" value="{$sb_to|escape:'html':'UTF-8'}" />
		</div>
		<div class="sb-reports-actions">
			<button type="submit" class="btn btn-primary"><i class="icon-search"></i> {l s='Show report' mod='salisbergreports'}</button>
			<a class="btn btn-default" href="{$sb_pdf_link|escape:'html':'UTF-8'}"><i class="icon-download"></i> {l s='Download PDF' mod='salisbergreports'}</a>
		</div>
	</form>

	<div class="panel sb-report">
		<div class="sb-report-head">
			<h3>{$sb_report.title|escape:'html':'UTF-8'}</h3>
			<p>{$sb_report.period|escape:'html':'UTF-8'}</p>
		</div>
		<div class="sb-report-summary">
			{foreach from=$sb_report.summary key=sb_label item=sb_value}
			<div class="sb-report-figure"><span>{$sb_label|escape:'html':'UTF-8'}</span><strong>{$sb_value|escape:'html':'UTF-8'}</strong></div>
			{/foreach}
		</div>
		<p class="sb-report-note">{$sb_report.note|escape:'html':'UTF-8'}</p>
		{foreach from=$sb_report.tables item=sb_table}
		{if $sb_table.heading}<h4>{$sb_table.heading|escape:'html':'UTF-8'} ({$sb_table.rows|@count})</h4>{/if}
		<div class="sb-report-scroll">
			<table class="table">
				<thead>
					<tr>{foreach from=$sb_table.columns item=sb_col}<th class="text-{$sb_col[1]|escape:'html':'UTF-8'}">{$sb_col[0]|escape:'html':'UTF-8'}</th>{/foreach}</tr>
				</thead>
				<tbody>
					{foreach from=$sb_table.rows item=sb_row}
					<tr>{foreach from=$sb_row key=sb_i item=sb_cell}<td class="text-{$sb_table.columns[$sb_i][1]|escape:'html':'UTF-8'}">{$sb_cell|escape:'html':'UTF-8'}</td>{/foreach}</tr>
					{foreachelse}
					<tr><td colspan="{$sb_table.columns|@count}" class="sb-report-empty">{l s='Nothing in this period.' mod='salisbergreports'}</td></tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{/foreach}
	</div>
</div>
<script type="text/javascript">
	{literal}
	// the two date boxes are only needed for "Choose dates"; picking a date switches to it
	(function () {
		var period = document.getElementById('sb_period');
		var dates = document.querySelectorAll('.sb-reports-dates');
		function show() { for (var i = 0; i < dates.length; i++) { dates[i].style.display = period.value === 'custom' ? '' : 'none'; } }
		period.addEventListener('change', show);
		show();
	})();
	{/literal}
</script>
