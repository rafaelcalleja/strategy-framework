<div class="box-title">
	{$lang.descargar_evaluacion_de_riesgos}
</div>
<div class="cbox-content">
	Desde aqui puedes descargar el pdf con la evaluación de riesgos necesaria para este empleado
</div>
<form target="_blank" action="{$smarty.server.PHP_SELF}">
	<div class="cboxButtons">
		<button class="btn"><span><span>{$lang.descargar}</span></span></button>
	</div>
	{if isset($smarty.get.poid)}<input type="hidden" name="poid" value="{$smarty.get.poid}" />{/if}
	{if isset($smarty.get.m)}<input type="hidden" name="m" value="{$smarty.get.m}" />{/if}
	<input type="hidden" name="send" value="1" />
</form>
