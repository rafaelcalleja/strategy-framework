<div class="box-title">
	{$lang.remove_revision_title}
</div>
<form name="elemento-form-delete" action="{$smarty.server.PHP_SELF}" class="form-to-box asistente" id="elemento-form-delete">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div class="cbox-content" style="width: 450px;">
		{$lang.remove_revision_text}
	</div>
	<div class="cboxButtons">
		<button class="btn"  type="submit"><span><span>{$lang.$boton|default:"Continuar"}</span></span></button>
	</div>

	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
	<input type="hidden" name="send" value="1" />
</form>
