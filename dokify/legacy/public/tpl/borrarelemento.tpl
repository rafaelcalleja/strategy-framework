{*
Descripcion
	Plantilla para mostrar en modal box, incluye las referencias a error, succes e info

En uso actualmente
	-	/agd/gettpl.php

Variables
	· $boton - Opcional String = Boton que aparecerá en el formulario
	· *confirm - Si se indica esta variable via GET/POST, pide una segunda conformidad
*}
<div class="box-title">
	{$lang.borrar_elemento}
</div>
<form name="elemento-form-delete" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" id="elemento-form-delete">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content" style="width: 450px;">
		{$lang.borrar_definitivo}
	</div>
	<div class="cboxButtons">
		<button class="btn"  type="submit"><span><span>{$lang.$boton|default:"Continuar"}</span></span></button>
	</div>
	{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
	{if isset($smarty.get.oid)}<input type="hidden" name="oid" value="{$smarty.get.oid}" />{/if}
	{if isset($smarty.get.return)}<input type="hidden" name="return" value="{$smarty.get.return}" />{/if}
	{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
	{if isset($smarty.get.config)}<input type="hidden" name="config" value="{$smarty.get.config}" />{/if}
	{if isset($action)}<input type="hidden" name="action" value="{$action}" />{/if}

	{if isset($smarty.get.confirm)}
		<input type="hidden" name="confirm" value="1" />
	{else}
		<input type="hidden" name="confirmed" value="1" />
	{/if}
	<input type="hidden" name="send" value="1" />
</form>
