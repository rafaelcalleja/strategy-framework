{*
	Util para crear listas para seleccion de los elementos

	Variables
		· elementos = array() : Conjunto de elementos...
		· title = String titulo
		· inputtype = radio | checkbox
		· elemento = objeto padre
		· seleccionados = array objetos seleccionados
		· $title = título a mostrar
		· $unsetsend = no envies $_REQUEST["send"]
		· $varname = list[] | variable de seleccion
*}

{if !isset($inputtype) || ( $inputtype != "radio" && $inputtype != "checkbox" ) }
	{assign var="inputtype" value="radio"}
{/if}
{if !isset($varname)}
	{assign var="varname" value="list[]"}
{/if}
<form name="seleccion-lista" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="seleccion-lista" method="POST">
	{if isset($title)}
	<div class="box-title">
		{$title}
	</div>
	{/if}

	{include file=$succespath}
	{include file=$errorpath}
	{include file=$infopath}

	<div class="cbox-content">
		<h1>{$elemento->getUserVisibleName()}</h1>
		<hr />

		<div class="cbox-list-content item-list" style="width: 100%;">
			<table>

			{foreach from=$elementos item=item}
				{assign var="class" value=""}
				{assign var="defaultSelected" value=$selected|default:false}

				{foreach from=$seleccionados item=seleccionado}
					{if is_object($seleccionado)}
						{if call_user_func('util::comparar', $item, $seleccionado)}
							{assign var="class" value="selected-row"}
							{assign var="defaultSelected" value="checked"}
							{break}
						{/if}
					{/if}
				{/foreach}
				<tr class="{$class}">
					<td>
						<span><input type="{$inputtype}" name="{$varname}" class="line-check" value="{$item->getUID()}" {$defaultSelected}/> {$item->getListName()}</span>
					</td>
				</tr>
			{/foreach}
			</table>
		</div>
	</div>
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span>{if isset($button)}{$lang.$button|default:$button}{else}{$lang.guardar}{/if}</span></span></button>
	</div>
	{ if !isset($unsetsend)} <input type="hidden" name="send" value="1"> {/if}
	{ if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
	{ if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}">{/if}
	{ if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}">{/if}
	{ if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}">{/if}
</form>
