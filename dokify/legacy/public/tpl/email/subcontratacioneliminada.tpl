<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.cadena_subcontrata_eliminada} </h1>
	<h3 style="margin-top:0"> {$empresaContacto->getUserVisibleName()} </h3>
	<p>
		{$lang.mensaje_cadena_subcontrata_eliminada|sprintf:$empresaUsuario->getUserVisibleName():$empresaFinal->getUserVisibleName()} 
		<br/>
		{$lang.mensaje_subcontrata_continua_dokify|sprintf:$empresaFinal->getUserVisibleName()} 
	</p>			
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>