<table cellpadding="0" cellspacing="0" style="width: 100%;">
	<tr>
		<td style="width: 30%;">{if $sb_logo}<img src="{$sb_logo|escape:'html':'UTF-8'}" style="height: 46px;" />{else}<span style="font-size: 14pt; font-weight: bold; color: #12352c;">{$sb_shop_name|escape:'html':'UTF-8'}</span>{/if}</td>
		<td style="width: 70%; text-align: right;">
			<span style="font-size: 16pt; font-weight: bold; color: #12352c;">{$sb_report.title|escape:'html':'UTF-8'}</span><br />
			<span style="font-size: 10pt; color: #555555;">{$sb_report.period|escape:'html':'UTF-8'}</span>
		</td>
	</tr>
	<tr><td colspan="2" style="border-bottom: 1px solid #b98a2f; font-size: 2pt;">&nbsp;</td></tr>
</table>
