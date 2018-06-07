{*
	Util para crear listas de acciones sencillas a partir de un array

	Variables
		· elementos = array() : Conjunto de elementos...
		· title = String titulo
*}
<div>
	{include file=$succespath}
	{include file=$errorpath}
	{include file=$infopath}
	<div class="cbox-content">
		{if isset($titulo)}
			<h1>{$lang.$titulo|default:$titulo}</h1>
		{/if}
		<table class="item-list">
		{foreach from=$elementos item=elemento}
			{assign var=innerHTML value=$elemento.innerHTML}
			<tr ><td style="padding: 6px 4px;">
				<div>	
					{if isset($elemento.img)}<img src="{$elemento.img}" />{/if} <a class="{if isset($elemento.className)}{$elemento.className}{/if}" target="{if isset($elemento.target)}{$elemento.target}{/if}" name="{if isset($elemento.name)}{$elemento.name}{/if}" href="{if isset($elemento.href)}{$elemento.href}{/if}">{$lang.$innerHTML|default:$elemento.innerHTML}</a>
				</div>
			</td></tr>
		{/foreach}
		</table>
	</div>
</div>
<div class="cboxButtons">
	{include file=$tpldir|cat:'button-list.inc.tpl'}
</div>

