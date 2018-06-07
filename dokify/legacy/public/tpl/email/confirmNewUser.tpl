<div style="padding:10px 20px 0 0">
	<img src="{$elemento_logo}" style="float: right" alt="logo-dokify" height="59" />
	<h1 style="margin-top:0"> <b>{$lang.email_subject_signup_confirmation}</b> </h1>
	<div style="clear: both">
		<br />
	</div>

		{$lang.email_greeting} <b>{$elementoNombre}</b>,<br><br>

		{$lang.email_new_user_main_info|sprintf:$empresaNombre}<br><br>

		{$lang.email_bienvenida_ssl} <br><br>

		{$lang.email_bienvenida_datos_usuario} <br><br>


		{$lang.email_bienvenida_usuario} <b>{$usuario}</b><br>
		{$lang.email_bienvenida_pass} <b>{$password}</b><br><br>


		{$lang.email_bienvenida_faq}<br><br>

		{$lang.email_pie_equipo}<br><br>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
