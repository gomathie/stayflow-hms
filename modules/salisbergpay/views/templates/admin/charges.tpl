{*
 Rooms & Services > Charges & Tax. Data comes from Salisbergpay::getAutoCharges()
 and Salisbergpay::getTaxesInUse().
*}
<div class="panel">
	<div class="panel-heading"><i class="icon-money"></i> {l s='Charges added to every booking' mod='salisbergpay'}</div>
	<p class="help-block">{l s='These charges are added by themselves to every booking, without the guest choosing them. Untick one to stop charging it. Bookings already made are not changed.' mod='salisbergpay'}</p>
	<table class="table">
		<thead>
			<tr>
				<th>{l s='Charge' mod='salisbergpay'}</th>
				<th class="text-right">{l s='Price' mod='salisbergpay'}</th>
				<th>{l s='How it is charged' mod='salisbergpay'}</th>
				<th>{l s='Added to every booking' mod='salisbergpay'}</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$sb_charges item=sb_charge}
			<tr>
				<td><strong>{$sb_charge.name|escape:'html':'UTF-8'}</strong></td>
				<td class="text-right">{$sb_charge.price|escape:'html':'UTF-8'}</td>
				<td>{$sb_charge.how|escape:'html':'UTF-8'}</td>
				<td>
					{if $sb_can_edit}
					<form method="post" action="{$sb_action|escape:'html':'UTF-8'}" style="margin:0;">
						<input type="hidden" name="sb_charge" value="{$sb_charge.id_product|intval}" />
						<input type="hidden" name="sb_on" value="{if $sb_charge.on}0{else}1{/if}" />
						<label style="font-weight:normal;cursor:pointer;margin:0;">
							<input type="checkbox" {if $sb_charge.on}checked="checked"{/if} onchange="this.form.submit();" />
							{if $sb_charge.on}{l s='Yes' mod='salisbergpay'}{else}{l s='No' mod='salisbergpay'}{/if}
						</label>
					</form>
					{else}
						{if $sb_charge.on}{l s='Yes' mod='salisbergpay'}{else}{l s='No' mod='salisbergpay'}{/if}
					{/if}
				</td>
			</tr>
		{foreachelse}
			<tr><td colspan="4" class="text-center text-muted">{l s='No charge is set to be added by itself.' mod='salisbergpay'}</td></tr>
		{/foreach}
		</tbody>
	</table>
	<p class="help-block">{l s='Optional extras the guest can choose (breakfast, airport shuttle and so on) are not listed here. They are under Rooms & Services, Manage Service Products.' mod='salisbergpay'}</p>
</div>

<div class="panel">
	<div class="panel-heading"><i class="icon-legal"></i> {l s='Tax' mod='salisbergpay'}</div>
	{if $sb_can_edit}
	<form method="post" action="{$sb_action|escape:'html':'UTF-8'}">
		<input type="hidden" name="sb_tax" value="1" />
		<input type="hidden" name="sb_on" value="{if $sb_tax_on}0{else}1{/if}" />
		<label style="font-size:14px;cursor:pointer;">
			<input type="checkbox" {if $sb_tax_on}checked="checked"{/if} onchange="this.form.submit();" />
			{l s='Charge tax on bookings' mod='salisbergpay'}
		</label>
	</form>
	{else}
		<p><strong>{if $sb_tax_on}{l s='Tax is charged on bookings.' mod='salisbergpay'}{else}{l s='Tax is not charged on bookings.' mod='salisbergpay'}{/if}</strong></p>
	{/if}
	{if $sb_taxes}
		<p class="help-block">{l s='Tax rates set on room types and charges now:' mod='salisbergpay'}</p>
		<ul>
		{foreach from=$sb_taxes item=sb_tax}
			<li>{$sb_tax.name|escape:'html':'UTF-8'}: {$sb_tax.count|intval} {l s='room types or charges' mod='salisbergpay'}</li>
		{/foreach}
		</ul>
	{/if}
	<div class="alert alert-warning">{l s='The tax rates in the system came with the software and are United States sales taxes, not Ghana VAT and levies. Until the right Ghana taxes are set up with your accountant, it is safer to leave tax off. Setting them up is done by the developer team.' mod='salisbergpay'}</div>
</div>
