<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.CURRENT_DOMAIN}/www/images/logo.gif" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.contrasena_nueva} </h1>
	<p>
		{$lang.mensaje_nombre_usuario} <strong>{$usuario->getUserName()}.</strong> 
		<br/><br/>
		{$lang.mensaje_constrasena} 
		<br />
		<br/>
		 <a href="{$smarty.const.CURRENT_DOMAIN}/agd/chgpassword.php?token={$token}&email={$usuario->obtenerDato("email")}&tipo={$tipo}">
		 	{$lang.enviar_constrasena}</a>
		<br />
		<br/>
		{$lang.cambio_password_no_solicitado}
	</p>
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>

