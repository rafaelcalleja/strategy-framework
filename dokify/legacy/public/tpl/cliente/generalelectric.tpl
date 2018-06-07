<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	
	<link rel="stylesheet" href="http://estatico.afianza.net/css/class.css.php" type="text/css"/>
	<link rel="stylesheet" href="http://estatico.afianza.net/css/botones.css.php" type="text/css"/>	
	<link rel="stylesheet" href="http://estatico.afianza.net/css/cliente/generalelectric/login.css.php?lang={$smarty.get.lang|default:'es'}" type="text/css"/>
	
	<meta http-equiv="X-UA-Compatible" content="chrome=1" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js"> </script>
	<title>AGD - Iniciar Sesión</title>
	</head>
<body>

<div id="container">
	<div id="containerCab">
		<div id="contenidoCab">			
			<span id="mensajes">
				{if isset($smarty.get.loc)}
					{assign var="string" value="loc_"|cat:$smarty.get.loc}
					{if isset($lang.$string)}
						<div class="message highlight">{$lang.$string}</div>
						{literal}<script>
								window.setInterval(function(){
									try {
									var div = document.getElementById("aviso"), newClassName = (div.className=='colored')?'':'colored';
									div.className = newClassName;
									} catch (e) {}
								}, 700);
						</script>{/literal}
					{/if}
				{/if}
				{if isset($error)}
					<div class="message error">{$lang.$error}</div>
				{/if}
			</span>


				<span id="formularioAcceso">
				{if $system->getSystemStatus() == 1 || isset($smarty.get.forceaccess)}
					<form method="post" action="" id="loginform" name="loginform">
						<label for="usuario">{$lang.nombre_de_usuario}</label>
						<input type="text" value="{if isset($smarty.get.usuario)}{$smarty.get.usuario}{/if}" id="usuario" name="usuario"/>
				
						<label for="password">{$lang.contrasena}</label>
						<input type="password" value="" id="password" name="password"/>
				
						{if isset($security)}
							<span>
							<label for="pSeguridad" class="pSeguridad">{$lang.pregunta_seguridad}: ¿Cuanto es {$rand1} + {$rand2}?</label>
							<input type="text" value="" id="pSeguridad" name="suma" class="pSeguridad"/>
							</span>
						{/if}
		
				
						{if !isset($hideremember)}<label class="check"><input class="checkbox" type="checkbox" value="true" id="rememberme" name="rememberme"/>Recordarme</label>{/if}
						<button class="btn" type="submit"><span><span>Iniciar Sesión</span></span></button>		
						{if !isset($hiderestore)}
						<span class="recordatorio"><a href="restorepassword.php">{$lang.olvide_password}</a></span>
						{/if}
					</form>
				{else}
						<div style="text-align: center">
							<div class="message error" >
								<strong>El acceso a la aplicación esta temporalmente deshabilitado</strong>
							</div>
						</div>
				{/if}
				</span>

		</div>
	</div>
	
	<span id="pie">
		<div id="idiomas">
			<ol>
				<li class="navPortugues"><a href="?lang=pt" class="{if isset($smarty.get.lang)&&$smarty.get.lang == 'pt'}on{else}off{/if}">Seleccionar Portugues</a></li>
				<li class="navCastellano"><a href="?lang=es" class="{if (isset($smarty.get.lang)&&$smarty.get.lang == 'es')||!isset($smarty.get.lang)}on{else}off{/if}">Seleccionar Castellano</a></li>
				<li class="navIngles"><a href="?lang=en" class="{if isset($smarty.get.lang)&&$smarty.get.lang == 'en'}on{else}off{/if}">Seleccionar Inglés</a></li>
			</ol>
		</div>
		<div id="txtPie"><a href="">{$lang.terminos_condiciones}</a> | <a href="">{$lang.soporte_tecnico}</a> | © Afianza 2010</div>
	</span>
</div>
{include file=$tpldir|cat:'cliente/common/jsparts.tpl'}
</body>
</html>
