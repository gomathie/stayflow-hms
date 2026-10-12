{* Body of the PDF. TCPDF understands simple tables and inline styles only. *}
<table cellpadding="6" cellspacing="0" style="width: 100%;">
	<tr>
		{foreach from=$sb_report.summary key=sb_label item=sb_value}
		<td style="background-color: #f6f1e6; border-right: 3px solid #ffffff;">
			<span style="font-size: 8pt; color: #666666;">{$sb_label|escape:'html':'UTF-8'}</span><br />
			<span style="font-size: 13pt; font-weight: bold; color: #12352c;">{$sb_value|escape:'html':'UTF-8'}</span>
		</td>
		{/foreach}
	</tr>
</table>
<br />
<span style="font-size: 8pt; color: #666666;">{$sb_report.note|escape:'html':'UTF-8'}</span>
<br />
{foreach from=$sb_report.tables item=sb_table}
{if $sb_table.heading}<br /><br /><span style="font-size: 12pt; font-weight: bold; color: #12352c;">{$sb_table.heading|escape:'html':'UTF-8'} ({$sb_table.rows|@count})</span><br />{/if}
<br />
<table cellpadding="5" cellspacing="0" style="width: 100%; font-size: 8.5pt;">
	<thead>
		<tr>
			{foreach from=$sb_table.columns item=sb_col}
			<th style="width: {$sb_col[2]|intval}%; text-align: {$sb_col[1]|escape:'html':'UTF-8'}; background-color: #12352c; color: #ffffff; font-weight: bold;">{$sb_col[0]|escape:'html':'UTF-8'}</th>
			{/foreach}
		</tr>
	</thead>
	<tbody>
		{foreach from=$sb_table.rows item=sb_row name=sb_rows}
		<tr{if $smarty.foreach.sb_rows.index % 2} style="background-color: #f7f7f5;"{/if}>
			{foreach from=$sb_row key=sb_i item=sb_cell}
			<td style="width: {$sb_table.columns[$sb_i][2]|intval}%; text-align: {$sb_table.columns[$sb_i][1]|escape:'html':'UTF-8'}; border-bottom: 1px solid #e5e5e5;">{$sb_cell|escape:'html':'UTF-8'}</td>
			{/foreach}
		</tr>
		{foreachelse}
		<tr><td colspan="{$sb_table.columns|@count}" style="text-align: center; color: #777777; border-bottom: 1px solid #e5e5e5;">{l s='Nothing in this period.' mod='salisbergreports'}</td></tr>
		{/foreach}
	</tbody>
</table>
{/foreach}
