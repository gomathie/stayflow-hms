{* Modules and Services > Manage Modules. One summary line, category chips,
   then the list (list.tpl), which is also reloaded on its own by AJAX. *}
<div class="sb-mod-summary">
	<strong>{l s='%d modules' sprintf=[$nb_modules]}</strong>
	<span>{l s='%d on' sprintf=[$nb_modules_activated]}</span>
	<span>{l s='%d off' sprintf=[$nb_modules_unactivated]}</span>
	<span>{l s='%d not installed' sprintf=[$nb_modules_uninstalled]}</span>
	{if $upgrade_available|@count}
		<span class="sb-mod-warn">{l s='%d to update:' sprintf=[$upgrade_available|@count]}
		{foreach from=$upgrade_available item='module' name=upd}<a href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;anchor={$module.anchor|escape:'html':'UTF-8'}">{$module.displayName|escape:'html':'UTF-8'}</a>{if !$smarty.foreach.upd.last}, {/if}{/foreach}</span>
	{else}
		<span>{l s='all up to date'}</span>
	{/if}
</div>
{if $module_alerts|@count}
<div class="alert alert-warning">
	<ul class="list-unstyled">{foreach from=$module_alerts item='alert'}<li>{$alert}</li>{/foreach}</ul>
</div>
{/if}
{if $add_permission eq '1'}
<div id="module_install" class="row" style="{if !isset($smarty.post.downloadflag)}display: none;{/if}">
	<div class="panel col-lg-12">
		<form id="module_install_form" action="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
			<h3>{l s='Add a new module'}</h3>
			<p class="alert alert-info">{l s='The module must either be a Zip file (.zip) or a tarball file (.tar, .tar.gz, .tgz).'}</p>
			<div class="form-group">
				<label for="file" class="control-label col-lg-3">
					<span class="label-tooltip" data-toggle="tooltip" title="{l s='Upload a module from your computer.'}">
						{l s='Module file'}
					</span>
				</label>
				<div class="col-sm-9">
					<div class="row">
						<div class="col-lg-7">
							<input id="file" type="file" name="file" class="hide" />
							<div class="dummyfile input-group">
								<span class="input-group-addon"><i class="icon-file"></i></span>
								<input id="file-name" type="text" class="disabled" name="filename" readonly />
								<span class="input-group-btn">
									<button id="file-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
										<i class="icon-folder-open"></i> {l s='Choose a file'}
									</button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-9 col-lg-push-3">
					<button class="btn btn-default" type="submit" name="uploadAndInstall">
						<i class="icon-upload-alt" ></i>
						{l s='Upload  and install this module'}
					</button>
				</div>
			</div>
			<div id="module_install_status" class="form-group" style="display:none">
				<div class="col-lg-6 col-lg-push-3">
					<ul class="list-unstyled">
						<li class="mod_status_upload" style="display:none"><i class="icon-refresh icon-spin"></i>&nbsp;{l s='Uploading module.'}</li>
						<li class="mod_status_check" style="display:none"><i class="icon-refresh icon-spin"></i>&nbsp;{l s='Checking module if module is trusted.'}</li>
						<li class="mod_status_install" style="display:none"><i class="icon-refresh icon-spin"></i>&nbsp;{l s='Installing module.'}</li>
						<li class="mod_status_update" style="display:none"><i class="icon-refresh icon-spin"></i>&nbsp;{l s='Module already installed, checking and installing updates.'}</li>
						<li class="mod_status_rollback" style="display:none"><i class="icon-refresh icon-spin"></i>&nbsp;{l s='Rolling back changes.'}</li>
					</ul>
					<div class="install_msg"></div>
					<div class="install_errors" style="display:none">
						{l s='Errors.'}
						<div class="list"></div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
{/if}
<div class="panel sb-mod-panel">
	<div class="sb-mod-bar">
		<form id="filternameForm" method="post" class="sb-mod-search">
			<i class="icon-search"></i>
			<input class="form-control" placeholder="{l s='Search modules'}" type="text" value="" name="moduleQuicksearch" id="moduleQuicksearch" autocomplete="off" />
		</form>
		<div class="sb-mod-chips">
			<a class="categoryModuleFilterLink {if count($categoryFiltered) lte 0}active{/if}" href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;unfilterCategory=yes" id="filter_all">{l s='All'} <span>{$nb_modules|intval}</span></a>
			{if $nb_modules_favorites}<a class="categoryModuleFilterLink {if isset($categoryFiltered.favorites)}active{/if}" href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;filterCategory=favorites" id="filter_favorite">{l s='Favorites'} <span id="favorite-count">{$nb_modules_favorites|intval}</span></a>{/if}
			{foreach from=$list_modules_categories item=module_category key=module_category_key}
			<a class="categoryModuleFilterLink {if isset($categoryFiltered[$module_category_key])}active{/if}" href="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$token|escape:'html':'UTF-8'}&amp;filterCategory={$module_category_key|escape:'html':'UTF-8'}" id="filter_{$module_category_key|escape:'html':'UTF-8'}">{$module_category.name|escape:'html':'UTF-8'} <span>{$module_category.nb|intval}</span></a>
			{/foreach}
		</div>
	</div>
	<div id="moduleContainer">
		{include file='controllers/modules/list.tpl'}
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$('#file-selectbutton').click(function(e){
			$('#file').trigger('click');
		});
		$('#file-name').click(function(e){
			$('#file').trigger('click');
		});
		$('#file').change(function(e){
			var val = $(this).val();
			var file = val.split(/[\\/]/);
			$('#file-name').val(file[file.length-1]);
		});
	});
</script>
