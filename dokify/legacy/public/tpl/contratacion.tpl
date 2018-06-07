{*
	Este será el "asistente" para la configuración de las contrataciones - subcontrataciones

	Variables
		· elemento = item : Elemento a asignar

*}
<form name="contratacion" action="{$smarty.server.PHP_SELF}" class="async-form" id="contratacion" method="POST">
	<div class="box-title">
		{$lang.configurar_contratacion} {$elemento->getUserVisibleName()}
	</div>

	{include file=$succespath}
	{include file=$errorpath}
	{include file=$infopath}

	<div class="cbox-content{if isset($reload)} reloader{/if}">
		{if is_traversable($empresas) && count($empresas)}
			<p class="padded">{$lang.indicar_clientes}</p>

			<div class="cbox-list-content item-list" style="width: 100%; max-height: 360px">
				<table>

				{foreach from=$empresas item=empresa}
					{assign var="checked" value=""}
					{if $elemento->esSubcontrataDe($empresaUsuario, $empresa)}
						{assign var="checked" value="checked"}
					{/if}

					<tr class="{if $checked=='checked'}selected-row{/if}">
						<td>
							<span><input type="checkbox" name="list[_][]" class="line-check toggle" target="tr.sup-of-{$empresa->getUID()}" value="{$empresa->getUID()}" {$checked} /> {$empresa->getListName()}</span>
						</td>
						<td style="width:25px">
							{if $empresa->countCorpDocuments()}
								<img class="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder_page.png" style="vertical-align: top;" title="{$lang.company_has_requests}"/>	
							{/if}
						</td>
					</tr>

					{assign var="superiores" value=$empresa->obtenerEmpresasSuperioresSubcontratando($empresaUsuario, null, true, $user)}
					{if $superiores}
						{foreach from=$superiores item=superior}
							{assign var="supchecked" value=""}
							{if $elemento->esSubcontrataDe($empresaUsuario, $empresa, $superior)}
								{assign var="supchecked" value="checked"}
							{/if}

							<tr {if $checked!='checked'}style="display:none"{/if} class="{if $supchecked=='checked'}selected-row{/if} sup-of-{$empresa->getUID()}">
								<td style="padding-left: 3em; background-position: 5px center" class="row-link">
									<span><input type="checkbox" name="list[{$empresa->getUID()}][]" class="line-check" value="{$superior->getUID()}" {$supchecked} /> {$superior->getListName()}</span>
								</td>
								<td style="width:25px">
									{if $superior->countCorpDocuments()}
										<img class="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder_page.png" style="vertical-align: top;" title="{$lang.company_has_requests}"/>	
									{/if}
								</td>
							</tr>	
						{/foreach}
					{/if}

				{/foreach}
				</table>
			</div>
		{else}
			<p class="padded">{$lang.sin_clientes_asignables}</p>
		{/if}
	</div>
	<div class="cboxButtons">
		<div style="float:left">
			{if (isset($continue) && $continue) || ($elemento instanceof signinrequest && count($empresas) == 0)}
				<button name="continue" class="btn send" type="submit" value="continue"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder_error.png"/> {$lang.continue_invitation_no_clients}</span></span></button>
			{/if}
			{if !$elemento instanceof signinrequest}
				<a href="{$elemento->obtenerUrlFicha()}" class="box-it btn"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eye.png" /> {$lang.desc_ficha_empresa}</span></span></a>
			{/if}
			{if $smarty.request.comefrom == "nuevo" && !$elemento instanceof signinrequest}
				<a href="usuario/nuevo.php?poid={$elemento->getUID()}" class="box-it btn red"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user.png" /> {$lang.crear_usuario_empresa}</span></span></a>
			{/if}
		</div>
		{if is_traversable($empresas) && count($empresas)}<button class="btn" type="submit"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/disk.png" alt="save" /> {if $buttonText}{$buttonText}{else}{$lang.guardar}{/if}</span></span></button>{/if}
		<div class="clear"></div>
	</div>

	<input type="hidden" name="send" value="1">

	{ if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
	{ if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}">{/if}
	{ if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}">{/if}
	{ if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}">{/if}
</form>
