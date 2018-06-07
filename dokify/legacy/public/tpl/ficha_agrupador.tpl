
<div class="box-title">
	{$lang.caracteristicas}
</div>
<form name="ficha-elemento" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="ficha-elemento" enctype="multipart/form-data" method="POST">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}
	<div>
		<div class="message highlight">
			{assign var="datos" value=$elemento->getInfo(false,'edit')}
			<strong class="ucase">{$elemento->getUserVisibleName()}</strong>
			<hr />
			{$lang.autoasignacion} <input type="checkbox"  name="autoasignacion" {if ($datos.autoasignacion)}checked{/if}/>
			<br />
			{$lang.expirar_asignacion} <input type="text" name="expiracion" value="{$datos.expiracion}" size="3" maxlength="3" match="^[0-9]$"/>
		</div>
	</div>
	<div class="cboxButtons">
		<button class="btn"><span><span>{$lang.guardar}</span></span></button>
	</div>
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
</form>
