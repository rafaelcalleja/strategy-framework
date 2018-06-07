{*
	Este será el "asistente" para la configuración de las contrataciones - subcontrataciones

	Variables
		· elemento = item : Elemento a asignar

*}
<form name="contratacion" action="{$smarty.server.PHP_SELF}" {if isset($className)}class="{$className}"{else}class="form-to-box"{/if} id="contratacion" method="POST" style="width:600px">
	<div class="box-title">
		{if isset($title)}{$title}{else}{$lang.configurar_contratacion}{/if} {$elemento->getUserVisibleName()}
	</div>

	{include file=$errorpath}
	{include file=$infopath}
	{include file=$succespath}



	<div class="cbox-content">
		{if count($empresas)}
			<p class="padded">{if $elemento instanceof usuario}{$lang.description_visibility_user}{else}{$lang.indicar_empresas_superiores}{/if}</p>
			<hr />
			<div class="cbox-list-content item-list" style="width: 100%; max-height: 360px">
				<table>
			
				{foreach from=$empresas item=empresa}
					{assign var="checked" value=""}
					{if $elemento instanceof usuario}
						{if isset($hiddenCompanies)}
							{assign var="isHidden" value=$hiddenCompanies->contains($empresa)}
						{/if}
						{if isset($isHidden) && !$isHidden}
							{assign var="checked" value="checked"}
						{/if}
					{elseif $elemento->esVisiblePara($empresa, $user->getCompany())}
						{assign var="checked" value="checked"}
					{/if}

					<tr class="{if $checked=='checked'}selected-row{/if}">
						<td style="width:25px">
							<input type="checkbox" name="list[]" class="line-check toggle" value="{$empresa->getUID()}" {$checked}/> 
						</td>
						<td style="width:25px">
							{if $empresa->countCorpDocuments()}
								<img class="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder_page.png" style="vertical-align: top;" title="{$lang.company_has_requests}"/>	
							{/if}
						</td>
						<td>
							<span>{$empresa->getListName()}</span>
						</td>
					</tr>
				{/foreach}
			
				</table>
			</div>
		{else}
			<p class="padded">{$lang.sin_clientes_asignables}</p>
		{/if}
		
	</div>
	<div class="cboxButtons">
		<div style="float:left">
			{assign var="literal" value="desc_ver_informacion_"|cat:$elemento->getType()}
			<a href="{$elemento->obtenerUrlFicha()}" class="box-it btn"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eye.png" /> {$lang.$literal}</span></span></a>
		</div>
		{if count($empresas)}<button class="btn {if isset($closeConfirm)} {$closeConfirm} {/if}" {if isset($closeConfirm)} data-confirm="{$lang.confirmar_abandonar_visibilidad}" {/if} type="submit"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" alt="save" /> {$lang.guardar}</span></span></button>{/if}
		<div class="clear"></div>
	</div>
	<input type="hidden" name="send" value="1">

	{ if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
	{ if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}">{/if}
	{ if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}">{/if}
	{ if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}">{/if}
	{ if isset($smarty.request.ref)}<input type="hidden" name="ref" value="{$smarty.request.ref}">{/if}
</form>
