{*
 Website footer: Location and Contact. Shown next to Explore, Follow us on
 and Payment accepted (same "footer" hook, same column shape). Uses the
 address, phone and email already shown on the Contact page, so there is one
 place to update them: Preferences > General / Shop Parameters.
*}
{if $sbFooterAddress || $sbFooterPhone || $sbFooterEmail}
<div class="col-sm-3">
	<div class="row">
		<section class="col-xs-12 col-sm-12">
			<div class="row margin-lr-0 footer-section-heading">
				<p>{l s='Location and Contact' mod='salisbergguide'}</p>
				<hr/>
			</div>
			<div class="row margin-lr-0">
				<ul class="sb-footer-contact">
					{if $sbFooterAddress}
						<li><i class="icon-building"></i> <span>{$sbFooterAddress|escape:'html':'UTF-8'}</span></li>
					{/if}
					{if $sbFooterPhone}
						<li><i class="icon-phone"></i> <a href="tel:{$sbFooterPhone|escape:'html':'UTF-8'}">{$sbFooterPhone|escape:'html':'UTF-8'}</a></li>
					{/if}
					{if $sbFooterEmail}
						<li><i class="icon-envelope"></i> <a href="mailto:{$sbFooterEmail|escape:'html':'UTF-8'}">{$sbFooterEmail|escape:'html':'UTF-8'}</a></li>
					{/if}
				</ul>
			</div>
		</section>
	</div>
</div>
{/if}
