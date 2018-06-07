{*
	Util para crear listas con elementos siempre usando el estandar getUserVisibleName

	Variables
		· inputtype = checkbox || puedes pasar radio
		· elemento = objeto referencia de papalera
		· elementos = array() : Conjunto de elementos...
		· icon = [ true | false ] : Mostrar icono...
		· title = String titulo
		· hiddebottom = [ true | false ] : Ocultar el pie del formulario...
		· replace = [ string tagName ] : Reemplazar el input por defecto por otro elemento...
*}

{if !isset($inputtype)}
	{assign var=inputtype value="checkbox"}
{/if}

<form name="lista-simple-elementos" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="lista-simple-elementos">
	{include file=$succespath}
	{include file=$infopath}
	{include file=$errorpath}
	{if isset($elementos) && is_traversable($elementos) && count($elementos)}
		<div class="cbox-content">
			<table class="item-list">
				{foreach from=$elementos item=atributo}
					{assign var=nombreElemento value=$atributo->getUserVisibleName()}
					<tr>
						{if isset($icon)}<td><img src="{$atributo->getIcon()}" /></td>{/if}
						<td><label for="uid_{$atributo->getUID()}">
							{if ($atributo instanceof documento_atributo)}
								{assign var=solicitante value=$atributo->getElement($elemento)}
								{if $solicitante instanceof agrupador}
									{$solicitante->getHTMLDocumentName()}  &raquo; 
								{/if}
							{/if}

							{$nombreElemento} 

							{if isset($solicitante) && $solicitante->referencia }
								· {$solicitante->referencia->getUserVisibleName()}
							{/if}
						</label></td>
						<td style="text-align: right"> 
							{if !isset($replace)}
								{assign var=currentEmpresaClient value=$user->getCompany()}
								{if $atributo instanceof solicituddocumento} 
									{assign var=atributoSolicitud value=$atributo->obtenerDocumentoAtributo()} 
									{assign var=empresaClienteSolicitante value=$atributoSolicitud->getCompany()} 	

									{assign var="key" value=""}

									{if isset($solicitante)&&$solicitante->referencia}{assign var="key" value="referencia-"|cat:$solicitante->referencia->getUID()}{/if}

									{if !$user->canValidateFor($empresaClienteSolicitante)}
										<img src="{$resources}/img/famfam/lock_delete.png" title="{$lang.filtrar_arhivo_prohibido}" alt="" />
									{else}
										<input type="{$inputtype}" class="line-check" id="uid_{$atributo->getUID()}" name="elementos[{$key}]" value="{$atributo->getUID()}" />
									{/if} 
								{else}
									<input type="{$inputtype}" class="line-check" id="uid_{$atributo->getUID()}" name="elementos[{$key}]" value="{$atributo->getUID()}" />
								{/if}								
							{else}
								<{$replace.tagName} target="{$replace.target}" name="{$replace.name}" type="{$replace.type}" href="{$smarty.server.PHP_SELF}?oid={$atributo->getUID()}&send=1" class={$replace.className}>{$replace.innerHTML}</{$replace.tagName}>
							{/if}
						</td>
					</tr>
				{/foreach}
			</table>
		</div>
		{if !isset($hiddebottom)}
			<div class="cboxButtons">
				{if !isset($noSelectAll)}
					<button class="btn checkall" target="form#lista-simple-elementos"><span><span> {$lang.seleccionar_todo} </span></span></butto>
				{/if}
				<button class="btn"><span><span> {$lang.continuar} </span></span></button>
			</div>
		{/if}
		{foreach from=$smarty.request item=val key=var}
			{if $var == "type" || $var == "send" || $var == "elementos"}{php}continue;{/php}{/if}
			<input type="hidden" name="{$var}" value="{$val}" />
		{/foreach}
		<input type="hidden" name="send" value="1" />
	{else}
		<div style="text-align: center">
			<div class="message highlight" style="text-align: center;">
				{$lang.select_sin_elementos}
			</div>
		</div>
	{/if}
</form>


