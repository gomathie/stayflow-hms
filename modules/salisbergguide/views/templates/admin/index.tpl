<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-guide">
	<div class="sb-intro">
		<h2>{l s='Guides' mod='salisbergguide'}</h2>
		<p>{l s='Step-by-step help for using this back office. Pick the guide for your role.' mod='salisbergguide'}</p>
	</div>
	<div class="sb-cards">
		<div class="sb-card">
			<h3>{l s='Staff Guide' mod='salisbergguide'}</h3>
			<p>{l s='For front desk and reservations staff: taking bookings, confirming cash and Mobile Money payments, check-in and check-out, guests and refunds.' mod='salisbergguide'}</p>
			<a class="btn btn-primary" href="{$sb_links.AdminSalisbergStaffGuide|escape:'html':'UTF-8'}">{l s='Open the Staff Guide' mod='salisbergguide'}</a>
		</div>
		{if $sb_can_read_admin_guide}
		<div class="sb-card">
			<h3>{l s='Admin Guide' mod='salisbergguide'}</h3>
			<p>{l s='For the hotel manager and the developer team: hotel details, room types and prices, staff accounts, website content, reports and system settings.' mod='salisbergguide'}</p>
			<a class="btn btn-primary" href="{$sb_links.AdminSalisbergAdminGuide|escape:'html':'UTF-8'}">{l s='Open the Admin Guide' mod='salisbergguide'}</a>
		</div>
		{/if}
	</div>
</div>
