{foreach from=$sbpay_methods item=method}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			<a class="salisbergpay salisbergpay-{$method.code|escape:'html':'UTF-8'}" href="{$link->getModuleLink('salisbergpay', 'payment', ['method' => $method.code], true)|escape:'html':'UTF-8'}" title="{$method.title|escape:'html':'UTF-8'}">
				{$method.title|escape:'html':'UTF-8'} <span>({$method.hint|escape:'html':'UTF-8'})</span>
			</a>
		</p>
	</div>
</div>
{/foreach}
