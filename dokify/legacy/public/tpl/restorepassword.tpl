<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/main.css" type="text/css" />
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/www/estilopassword.css" type="text/css" />
		{*<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/inc/tags.css" type="text/css" />
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/inc/class.css" type="text/css" />
		<link rel="stylesheet" href="{$smarty.const.RESOURCES_DOMAIN}/css/inc/botones.css" type="text/css" />*}
		<meta http-equiv="X-UA-Compatible" content="chrome=1" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>dokify - {$lang.restaurar_password}</title>
	</head>
	<body>
		<div id="container" style="width: 320px; margin: 100px auto 0;">
			{if isset($lock)}
				<div style="text-align: center">
					<div class="message error" >
						<strong>{$lang.error_demasiados_intentos}</strong>
					</div>
					<br />
					<a href="/login.php">{$lang.volver_inicio}</a>
				</div>
				<br />
			{else}
				<form method="post" action="" id="loginform" name="loginform">
					{if isset($succes) }
						<div style="text-align: center">
							<div class="message succes" >
								<strong>{$lang.clave_enviada_correctamente}</strong>
							</div>
							<br /><br />
							<a href="/login.php">{$lang.volver_inicio}</a>
						</div>
						<br />
					{else}
						<p style="font-size: 18px;">
							{$lang.texto_restaurar_password}
							<br /><br />
						</p>
						<p>
							<label>{$lang.usuario}<br/>
							<input type="text" tabindex="20" size="20" value="" class="input" id="usuario" name="usuario"/></label>
						</p>
						<p>
							<label><img src="../class/securimage/securimage_show.php" id="captcha" /><br/>				
							<a href="#" onclick="document.getElementById('captcha').src = '../class/securimage/securimage_show.php?' + Math.random(); return false">{$lang.refrescartexto}</a><br/><br/>
							<label>{$lang.introducir_captcha}<br/>
							<input type="text" tabindex="20" size="20" value="" class="input-like" id="captchacode" name="captchacode"/></label>
						</p>

						{if isset($error) }
							<div style="text-align: center">
								<div class="message error" >
									<strong>{$lang.$error|default:$error}</strong>
								</div>
							</div>
							<br />
						{/if}
						<p class="enviar">
							<button class="btn" tabindex="100" type="" onclick="{literal}var t=this;setTimeout(function(){t.disabled=true;},100);{/literal}">
								<span><span>{$lang.restaurar_password}</span></span>
							</button>
							<button class="btn" tabindex="100" type="" onclick="location.href='/';return false;">
								<span><span>{$lang.volver}</span></span>
							</button>
						</p>
						<input type="hidden" name="send" value="1" />
					{/if}
					<div style="clear: both;"></div>
				</form>
			 {/if}
		</div>
	</body>
</html>