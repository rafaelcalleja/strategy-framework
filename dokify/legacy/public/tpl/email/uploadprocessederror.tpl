<div style="padding:10px 20px 0 0">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> <b>Documento recibido!</b> </h1>
	<div style="clear: both">
	</div>

		Ups! Hemos tenido problemas al procesar tu documento, este es el mensaje de error: <br />
		<strong>{$error}</strong>

		<br />
		<br />
		Intentalo de nuevo y si el problema persiste ponte en contacto con nosotros

		<br /><br />

		{$lang.email_pie_equipo}<br><br>
		{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
