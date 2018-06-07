<div class="box-title">
	{$lang.solicitud_eliminar_relacion}
</div>
<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="elemento-form-papelera">
	<div>
		<div class="cbox-content" style="width: 550px;">
			{include file=$alertpath}
			{if isset($elementos) }{$elementos->getUserVisibleName()}:  {/if}
			{$empresaEliminar->getUserVisibleName()|string_format:$lang.confirmar_solicitar_eliminar}

		</div>
	</div>
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="sendrequest" value="1" />
	<input type="hidden" name="send" value="1" />
	{if isset($elementos) }
		<input type="hidden" name="elementos[]" value="{$elementos->getUID()}" />
	{/if}
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span><img src="{$resources}/img/famfam/accept.png"> {$lang.si} </span></span></button> 
	</div>
</form>