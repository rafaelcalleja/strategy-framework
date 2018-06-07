{*
Descripcion
	Lista de items adjunto

En uso actualmente
	-	/agd/adjuntos.php

Variables
	$elemento = Objeto Ielemento
*}
<div style="width: 750px;">
	<div class="box-title">
		{$lang.adjuntos}
	</div>
	{assign var="elementos" value=$elemento->obtenerAdjuntos()}
	{assign var="return" value=$smarty.server.PHP_SELF|cat:"?m="|cat:$elemento->getModuleName()|cat:"&poid="|cat:$elemento->getUID()|urlencode}	
	{if $elementos && count($elementos)}
		<form name="attach-form" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="attach-form">
			<div class="cbox-content">
				<table class="item-list" {if $ie}style="table-layout: auto; width: 100%"{/if}>
					<tbody id="body-documentos-descargables">
					{foreach from=$elementos item=item}
						<tr class="nombre-documento-descarga">
							<td width="90%">
								{$item->getUserVisibleName()} <span class="light"> <span style="white-space: nowrap"></span></span> 
								<a href="eliminar.php?m=adjunto&poid={$item->getUID()}&return={$return}" class="box-it"><img title="{$lang.eliminar}" style="vertical-align: middle" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/delete.png" /></a>
							</td>
							<td style="text-align: right">
								<div style="white-space: nowrap;">
								<a href="{$smarty.server.PHP_SELF}?m={$item->getModuleName()}&poid={$item->getUID()}&action=download" target="async-frame">
									{$lang.descargar}
								</a>
								</div>
							</td>
						</tr>
					{/foreach}
				</tbody>
				</table>
			</div>

			{*
			<input type="hidden" name="send" value="1" />
			<input type="hidden" name="m" value="{$smarty.get.m}" />
			*}
		</form>
	{else}
		<div style="text-align: center">
			<div class="message highlight" style="text-align: center;">
				{$lang.no_hay_archivos_adjuntos}
			</div>
		</div>
	{/if}

	<div class="cboxButtons">
		<a class="btn box-it" href="{$smarty.server.PHP_SELF}?m={$elemento->getModuleName()}&poid={$elemento->getUID()}&action=attach&return={$return}"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/attach.png" /> {$lang.anexar}</span></span></a>
	</div>
</div>

