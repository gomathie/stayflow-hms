{*
 Administration > Integrations. The API key is never assigned to the template.
 Overview first: one card per integration (sb_cards, built in the controller);
 a new integration adds a card there and its own panel below.
*}
<div class="sb-int-cards">
	{foreach from=$sb_cards item=card}
	<a class="sb-int-card" href="{if !empty($card.link)}{$card.link|escape:'html':'UTF-8'}{elseif $card.anchor}#{$card.anchor|escape:'html':'UTF-8'}{else}#{/if}">
		<span class="sb-int-icon"><i class="{$card.icon|escape:'html':'UTF-8'}"></i></span>
		<span class="sb-int-text">
			<strong>{$card.title|escape:'html':'UTF-8'}</strong>
			<small>{$card.provider|escape:'html':'UTF-8'}</small>
			<span class="sb-int-status {if $card.ok}is-on{else}is-off{/if}"><i></i>{$card.status|escape:'html':'UTF-8'}</span>
		</span>
	</a>
	{/foreach}
</div>

<div class="panel" id="sb-int-sms">
	<div class="panel-heading"><i class="icon-plug"></i> {l s='Text messages' mod='salisbergnotify'}</div>
	{if $sb_has_key}
	<div class="alert alert-success">{l s='A key is saved. A guest who has a phone number is texted at check-in and when the booking is paid in full.' mod='salisbergnotify'}</div>
	{else}
	<div class="alert alert-warning">{l s='No key is saved, so no text is sent. Check-in and payment still work.' mod='salisbergnotify'}</div>
	{/if}
	<p class="help-block">{l s='Paste the API key from the mNotify account. The sender name is what the guest sees on their phone, and it must already be approved as a sender ID on that account.' mod='salisbergnotify'}</p>
	<form method="post" action="{$sb_action|escape:'html':'UTF-8'}" autocomplete="off" class="form-horizontal">
		<div class="form-group">
			<label class="control-label col-lg-3" for="sb_api_key">{l s='mNotify API key' mod='salisbergnotify'}</label>
			<div class="col-lg-4">
				<input type="password" name="sb_api_key" id="sb_api_key" value="" autocomplete="new-password" class="form-control" />
				<p class="help-block">{l s='Leave this empty to keep the saved key.' mod='salisbergnotify'}</p>
			</div>
		</div>
		{if $sb_has_key}
		<div class="form-group">
			<div class="col-lg-offset-3 col-lg-4">
				<label style="font-weight:normal;">
					<input type="checkbox" name="sb_remove_key" value="1" />
					{l s='Remove the saved key' mod='salisbergnotify'}
				</label>
			</div>
		</div>
		{/if}
		<div class="form-group">
			<label class="control-label col-lg-3" for="sb_sender">{l s='Sender name' mod='salisbergnotify'}</label>
			<div class="col-lg-4">
				<input type="text" name="sb_sender" id="sb_sender" maxlength="11" value="{$sb_sender|escape:'html':'UTF-8'}" class="form-control" />
				<p class="help-block">{l s='Letters and numbers only, at most 11 characters. It starts as Salisberg.' mod='salisbergnotify'}</p>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-offset-3 col-lg-4">
				<button type="submit" name="sb_save" class="btn btn-primary">{l s='Save' mod='salisbergnotify'}</button>
			</div>
		</div>
	</form>
</div>
{if $sb_has_key}
<div class="panel">
	<div class="panel-heading"><i class="icon-mobile"></i> {l s='Send a test text' mod='salisbergnotify'}</div>
	<p class="help-block">{l s='Sends one short text to the number you enter, so you can confirm the key and the sender name before guests are texted.' mod='salisbergnotify'}</p>
	<form method="post" action="{$sb_action|escape:'html':'UTF-8'}" class="form-horizontal" autocomplete="off">
		<div class="form-group">
			<label class="control-label col-lg-3" for="sb_test_phone">{l s='Phone number' mod='salisbergnotify'}</label>
			<div class="col-lg-4">
				<input type="text" name="sb_test_phone" id="sb_test_phone" value="" class="form-control" inputmode="tel" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-offset-3 col-lg-4">
				<button type="submit" name="sb_test" class="btn btn-default">{l s='Send test text' mod='salisbergnotify'}</button>
			</div>
		</div>
	</form>
</div>
{/if}

<div class="panel" id="sb-int-alerts">
	<div class="panel-heading"><i class="icon-bell"></i> {l s='Booking alerts for staff' mod='salisbergnotify'}</div>
	<p class="help-block">{l s='Choose who is told about new bookings and payments. A chosen person is alerted by text (if a key is saved above and they have a phone number) and by email, at the address on their staff account.' mod='salisbergnotify'}</p>
	{if $sb_mail_enabled}
	<div class="alert alert-info">{l s='Email is set up' mod='salisbergnotify'} ({$sb_mail_method|escape:'html':'UTF-8'}{if $sb_mail_server}, {$sb_mail_server|escape:'html':'UTF-8'}{/if}). {l s='Alert emails will be sent.' mod='salisbergnotify'} <a href="{$sb_mail_link|escape:'html':'UTF-8'}">{l s='Email settings' mod='salisbergnotify'}</a></div>
	{else}
	<div class="alert alert-warning">{l s='Email is switched off, so no alert emails are sent.' mod='salisbergnotify'} <a href="{$sb_mail_link|escape:'html':'UTF-8'}">{l s='Set it up under Advanced Parameters, E-mail' mod='salisbergnotify'}</a>.</div>
	{/if}
	<form method="post" action="{$sb_action|escape:'html':'UTF-8'}" class="form-horizontal" autocomplete="off">
		<table class="table">
			<thead><tr>
				<th style="width:60px;">{l s='Alert' mod='salisbergnotify'}</th>
				<th>{l s='Staff member' mod='salisbergnotify'}</th>
				<th>{l s='Email' mod='salisbergnotify'}</th>
				<th style="width:200px;">{l s='Phone for texts' mod='salisbergnotify'}</th>
				<th style="width:90px;"></th>
			</tr></thead>
			<tbody>
			{foreach from=$sb_employees item=emp}
				<tr>
					<td class="text-center"><input type="checkbox" name="sb_recipients[]" value="{$emp.id|intval}"{if $emp.is_recipient} checked="checked"{/if} /></td>
					<td>{$emp.name|escape:'html':'UTF-8'}</td>
					<td>{$emp.email|escape:'html':'UTF-8'}</td>
					<td><input type="text" name="sb_emp_phone_{$emp.id|intval}" value="{$emp.phone|escape:'html':'UTF-8'}" class="form-control" inputmode="tel" placeholder="{l s='optional' mod='salisbergnotify'}" /></td>
					<td>{if $sb_has_key && $emp.is_recipient && $emp.phone}<button type="submit" name="sb_test_one" value="{$emp.id|intval}" class="btn btn-default btn-sm" title="{l s='Send a test text to this saved number' mod='salisbergnotify'}"><i class="icon-mobile"></i> {l s='Test' mod='salisbergnotify'}</button>{/if}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		<p class="help-block">{l s='Leaving a phone empty is fine: that person still gets the email alert. Anyone with no phone is asked for one when they next sign in. Test sends one text to the saved number of a chosen person; save a new number first.' mod='salisbergnotify'}</p>
		<button type="submit" name="sb_save_recipients" class="btn btn-primary">{l s='Save recipients' mod='salisbergnotify'}</button>
	</form>
</div>
