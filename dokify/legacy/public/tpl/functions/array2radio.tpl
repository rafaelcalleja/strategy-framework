{*
	Convertiru un array a lista de elementos radio

	· $title - Mostrar el titulo con el texto
	· $array - Elemento array que formara el html
	· checked - La clave marcada como chequeada por defecto
*}
{if isset($title)}
	<div class="box-title">{$title}</div>
{/if}
<form class="form-to-box" name="radio" action="{$smarty.server.PHP_SELF}">
	<div class="cbox-content">
	{if isset($array)}
		<table class="item-list">
		{foreach from=$array item=item key=key }
			<tr>
				<td>{$item.innerHTML}</td>
				<td style="text-align: right"> <input type="radio" name="radio" value={$key} {if isset($checked) && $key == $checked}checked{/if}/> </td>	
			</tr>
		{/foreach}
		</table>
	{/if}
	</div>
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span>{$lang.continuar}</span></span></button>
	</div>
	{if isset($smarty.request.selected)}
		{foreach from=$smarty.request.selected item=uid }
			<input type="hidden" name="selected[]" value="{$uid}" />
		{/foreach}
	{/if}

	{if isset($smarty.get.poid)}
		<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	{/if}
	{if isset($smarty.get.m)}
		<input type="hidden" name="m" value="{$smarty.get.m}" />
	{/if}
	<input type="hidden" name="send" value="1" />
</form>

