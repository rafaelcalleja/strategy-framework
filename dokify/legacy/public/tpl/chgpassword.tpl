{*
Descripcion
	-	HTML completo se utiliza cuando el usuario tiene que cambiar de password

En uso actualmente
	-	/chgpassword.php

Variables
	· $usuario - Objeto Usuario que debe actualiar el pass
*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/main.css" type="text/css" />
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/www/estilopassword.css" type="text/css" />
		<script src="{$smarty.const.CURRENT_PROTOCOL}//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.js" type="text/javascript"></script>
		<script src="{$smarty.const.RESOURCES_DOMAIN}/js/pschecker/pschecker.js" type="text/javascript"></script>
		<meta http-equiv="X-UA-Compatible" content="chrome=1" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>dokify - {$lang.iniciar_sesion}</title>
		<script>
		var cdebil = "{$lang.contrasena_debil}";
		var caceptable = "{$lang.contrasena_aceptable}";
   		var cfuerte = "{$lang.contrasena_fuerte}";
		{literal}
		$(document).ready(function(){

   				var validatePassword ;

   				var matchPassword ;			

            $('.form-container').pschecker({ onPasswordValidate: validatePassword, onPasswordMatch: matchPassword, debil : cdebil, aceptable : caceptable, fuerte : cfuerte });
		});
		{/literal}
		</script>
	</head>
	<body>
		<div class="password-container form-container" id="container" style="width: 320px; margin: 100px auto 0;">
			<form method="post" action="" id="loginform" name="loginform">
				<p style="font-size: 18px;">
					{$lang.texto_pass_caducada}
					<br /><br />
				</p>
				<p>
					{$lang.nombre_de_usuario}<br/>
					<div class="input-like">{$usuario->getUsername()}</div>
				</p>
				{if (!$token_email) }
				<p>
					<label>{$lang.contrasena_actual}<br/>
					<input type="password" tabindex="20" size="20" value="" class="input password" id="old_password" name="old_password"/></label>
				</p>
				{/if}
				<p>
					<label>{$lang.contrasena_nueva}<br/>
					<input type="password" tabindex="20" size="20" value="" class="strong-password" id="new_password" name="new_password"/></label>
				</p>
				<p>
					{$lang.barra_fortaleza}<br/>
				<div class="strength-container strength-indicator">

               		<div class="meter-container meter">
               		</div>
           		</div>
           		</p>
				<p>
					<label>{$lang.repite_contrasena_nueva}<br/>
					<input type="password" tabindex="20" size="20" value="" class="strong-password" id="new_password2" name="new2_password"/></label>
				</p>
				{if isset($error) }
					<div style="text-align: center">
						<div class="message error" >
							<strong>{$lang.$error}</strong>
						</div>
					</div>
					<br />
				{/if}
				<p class="enviar">
					<button class="btn" tabindex="100" type="" onclick="this.form.submit();" >
						<span><span>{$lang.cambiar_contrasena}</span></span>
					</button>
				</p>
				<input type="hidden" name="send" value="1" />
				<div style="clear: both;"></div>
			</form>
		</div>
		{if (!$token_email) }
		{literal}
		<script> window.onload = function(){ document.getElementById('old_password').value='';}</script>
		{/literal}
		{/if}
	</body>
</html>
