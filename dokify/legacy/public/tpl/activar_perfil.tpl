<div class="box-title">
	{$lang.activar_perfil}
</div>
<form method="POST" action="{$smarty.server.PHP_SELF}" class="form-to-box">
	<div class="cbox-content">
		{assign var="usuario" value=$perfil->getUser()}
		{assign var="perfiles" value=$usuario->obtenerPerfiles(false)}
		Deseas activar el perfil "{$perfil->getUserVisibleName()}"
	</div>
	<div class="cboxButtons">
		<button class="btn"><span><span> {$lang.continuar} </span></span></button>
	</div>
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
</form>
