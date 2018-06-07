{*
Descripcion
	Accion completada correctamente y mostrada en modal box

En uso actualmente
	-	un montonazo

Variables
	· $textoextra = Texto que ira en debajo del texto de exito, indicando algun dato extra
	· $acciones = Array(
		string => texto de idioma
		class => clase del link
		href => el atributo href
	) => para una vez completada una accion ir rapidamente a otro lugar
	· $buttons = Array(

	) => añadir botones
	
	· $clear = elementos seleccionados a borrar.
*}
<div class="box-title" style="{if $ie}width: 550px;{/if}">
	{$lang.exito_titulo}
</div>
	
	{if $ie}<div style="width: 500px;">{/if}

		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		<div>
			<div class="message highlight" id="reloader">
				{$lang.exito_texto}
				{if isset($textoextra) && strlen(trim($textoextra))}
					<br /><br />
					{if isset($lang.$textoextra)}
						{$lang.$textoextra}
					{else}
						{$textoextra}
					{/if}
				{/if}
			</div>
		</div>

	{if isset($acciones) && is_traversable($acciones)}
		{assign var="i" value=0}
		<div class="message highlight" id="reloader" style="text-align: center; {if $ie}width: 400px;{/if}">
		{foreach from=$acciones item=accion}
			{assign var="string" value=$accion.string}
			<a class="{if isset($accion.class)}{$accion.class}{else}box-it{/if}" {if isset($accion.href)}href="{$accion.href}"{/if}>{if isset($lang.$string)}{$lang.$string}{else}{$string}{/if}</a>


			{if $i!=(count($acciones)-1)}<br /><br />{/if}
			{assign var="i" value=$i+1}
		{/foreach}
		</div>
	{/if}

	{if $ie}</div>{/if}

	<div class="cboxButtons">
		{include file=$tpldir|cat:'button-list.inc.tpl'}
	</div>
	{if isset($clear)}
		{foreach from=$clear key=modulo item=list}
			<input type="hidden" name="{$modulo}" class="clear" value="{','|implode:$list}" />
		{/foreach}
	{/if}
	
	{if isset($smarty.request.frameopen)}<input type="hidden" id="frameopen" value="{$smarty.request.frameopen}" />{/if}
	
	
	
	
