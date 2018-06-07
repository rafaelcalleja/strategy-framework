{*
Descripcion
	HTML simple para cargar eb modal box, incluye error, info y succes
	Se llama cuando se ha invocado la plantilla <a href='?tpl=borrarelemento'>borrarelemento</a> con el parametro "confirm" = 1

En uso actualmente
	-	/agd/eliminar.php

Variables
*}	
<div class="box-title">
	{$lang.borrar_elemento}
</div>
<form name="elemento-form-delete" action="{$smarty.server.PHP_SELF}" method="{$smarty.server.REQUEST_METHOD}" class="form-to-box asistente" id="elemento-form-delete">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content" style="width: 450px;">
		{$lang.borrar_definitivo_confirmacion}
	</div>
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span>{$lang.eliminar}</span></span></button>
	</div>
	
	{if isset($smarty.request.selected)}
		{foreach from=$smarty.request.selected item=seleccionado}
			<input type="hidden" name="selected[]" value="{$seleccionado}" />
		{/foreach}
	{/if}

	{if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}" />{/if}
	{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}

	
	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($smarty.request.config)}<input type="hidden" name="config" value="{$smarty.request.config}" />{/if}
	
	<input type="hidden" name="confirmed" value="1" />
	<input type="hidden" name="send" value="1" />
	
	
</form>
