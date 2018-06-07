<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png"  style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> Empresa interesada en colaborar con dokify </h1>
	<p>
		Empresa: <strong>{$empresa}</strong> 
		<br/>
		<br/>
		Persona de contacto: <strong>{$nombre}</strong>
		<br/>
		<br/>
		Email de contacto: <strong>{$email}</strong>
		<br />
		<br/>
		Mensaje: <strong>{$mensaje}</strong>	
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>