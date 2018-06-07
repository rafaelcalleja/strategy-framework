{*
	Util para crear listas con elementos siempre usando el estandar getUserVisibleName

	Variables
		· inputtype = checkbox || puedes pasar radio
		· elemento = objeto referencia de papalera
		· elementos = array() : Conjunto de elementos...
		· icon = [ true | false ] : Mostrar icono...
		· title = String titulo
		· hiddebottom = [ true | false ] : Ocultar el pie del formulario...
*}

{if !isset($inputtype)}
	{assign var=inputtype value="checkbox"}
{/if}

<form name="lista-simple-elementos" action="{$smarty.server.PHP_SELF}" method="POST" class="form-to-box" id="lista-simple-elementos">
	<div class="box-title" title="{$lang.informacion_documentos_relevantes}">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" style="vertical-align: middle;" /> {$lang.documentos_relevantes}
	</div>
	{include file=$succespath}
	{include file=$infopath}
	{include file=$errorpath}
	{if isset($elementos) && is_traversable($elementos) && count($elementos)}
		{assign var=empresa value=$user->getCompany()}
		<div class="cbox-content">
			<table class="item-list">
				{foreach from=$elementos item=atributo}
					{assign var=nombreElemento value=$atributo->getUserVisibleName()}
					{assign var=modulo value=$atributo->getDestinyModuleName()}
					<tr>
						{if isset($icon)}<td><img src="{$atributo->getIcon()}" alt="folder" /></td>{/if}
						<td class="overflow-text"><label for="uid_{$atributo->getUID()}" title="{$nombreElemento}">
							{$nombreElemento} 

							{if isset($solicitante) && $solicitante->referencia }
								· {$solicitante->referencia->getUserVisibleName()}
							{/if}
						</label></td>
						<td style="text-align: right"> 
							<a target="async-frame" name="{$replace.name}" type="{$replace.type}" href="{$smarty.server.PHP_SELF}?oid={$atributo->getUID()}&send=1" class={$replace.className}>{$lang.descargar}</a>

							{if $modulo == "empresa"}
								{assign var=documento value=$atributo->obtenerDocumentoViaEjemplo($user->getCompany())|reset}
								{if $documento instanceof documento}
									{assign var=option value=$user->getAvailableOptionsForModule('empresa_documento', 'anexar')}
									{if $option = $option.0}
										· <a class="box-it" title="Después de descargar, imprimir, firmar y escanear el documento, haz click para cargarlo" href="{$option.href}&o={$empresa->getUID()}&poid={$documento->getUID()}">{$lang.anexar}</a>
									{/if}
								{/if}
							{/if}
						</td>
					</tr>
				{/foreach}
			</table>
			<div style="text-align: center; margin-top: 1em;"> <a href="#documentos.php?m=empresa&poid={$empresa->getUID()}" >Ver todos los documentos que tengo que anexar</a> </div>
		</div>
		{if !isset($hiddebottom)}
			<div class="cboxButtons">
				<button class="btn checkall" target="form#lista-simple-elementos"><span><span> {$lang.seleccionar_todo} </span></span></button>
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


