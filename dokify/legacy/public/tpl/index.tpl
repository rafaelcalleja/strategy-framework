{*

Descripcion
	Plantilla utilizada a nivel html, se usa para cargar el resto de elementos

En uso actualmente
	-	/agd/index.php

Variables
	· $visibleonstart - if isset = no muestra la capa de carga inicial
	· $modules - array(
			img => ruta absoluta a la imagen || ../img/32x32/iface/ + name
			lang => texto visible del boton || menu_ + name
		) || null
	· $hidesearch = if isset = oculta el buscador
	· $avisos = array(text,text,text) || null
	· $usuario = objeto de tipo usuario || null (como si no tuviera permisos)
	· $version = text - Enlaza al changelog
	· $exitlink = string - link de salida alternativo
	· $manifiest = bool(true) - manifiest for offline app
	· $readonly = if true muestra el mensaje de que se esta accediendo en modo solo lectura
	· $open = String - abrir ventana modal al iniciar
	· $route = Plantilla que se cargará a modo de cuerpo de la página
	· $currentAPP = Locale string que identifica a la app actual
*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		{if is_ie()}<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" >{/if}

		<link rel="shortcut icon" href="{$resources}/img/favicon.ico" />
		<link rel="icon" href="{$resources}/img/favicon.ico" />

		<style>{literal}html,body,ul{margin:0px;}#load{font-size:12px;padding-top:20px;text-align:center;}#cboxOverlay{ position:fixed;width:100%;height:100%;background:url("{/literal}{$resources}{literal}/img/modal/overlay.png") 0 0 repeat;}{/literal}</style>

		<script type="text/javascript">window.__rversion='{$smarty.const.VKEY}';</script>
		{if !isset($script)}
		<script data-main="{$resources}/js/{$smarty.const.BOOT_FILE}" src="{$resources}/js/require.js"></script>
		{/if}

		<link type="text/css" rel="stylesheet" href="{$resources}/css/main.css?{$smarty.const.VKEY}" id="main-style" />
		<link type="text/css" rel="stylesheet" href="{$resources}/css/componente/_button.css?{$smarty.const.VKEY}" />
		<link type="text/css" rel="stylesheet" href="{$assets}/css/vendor/tipsy.css"/>
		{if $empresaUsuario}<style id="inline-style">{$empresaUsuario->getStyleString(false)}</style>{/if}
		<!--[if IE]><link type="text/css" rel="stylesheet" href="{$resources}/css/ie/iehack.css" /><![endif]-->
		{include file=$smarty.const.DIR_ROOT|cat:"/tpl/webapp.tpl"}

		<title>dokify - {$lang.bienvenido}{if isset($user)} - @{$user->getUserName()}{/if}</title>

		{if $embedded}
			{include file=$tpldir|cat:'embedded-style.tpl'}
		{/if}
	</head>
	<body>
		{if isset($readonly)&&$readonly==true}
			<div id="readonly-advertisment">
				{$lang.readonly_mode_activado}
			</div>
		{/if}

		{if !isset($visibleonstart)} <div id="load"> {if $embedded}<img src="{$smarty.const.WEBCDN}/img/icons/loader-transparent.gif" />{else}<h1>{$lang.espera_mientras_carga}</h1>{/if} </div> {/if}
		<div id="loading" style="display:none"><div>{$lang.cargando}...</div></div>

		<div id="cuerpo" {if !isset($visibleonstart)}style="display: none"{/if}>
			<div id="top-bar" {if $embedded}style="display:none"{/if}>
				<div id="top-bar-left">
					<span class="wrap"><a href="#home.php" class="current" id="docs"> {$lang.$currentAPP|default:$currentAPP} </a></span>
					{if isset($usuario) && $usuario instanceof usuario}
						{assign var=empresaUsuario value=$usuario->getCompany()}

						{if ($empresaUsuario->isEnterprise() || $user->isBetatester()) && $user->accesoModulo("datamodel")}
							<span class="wrap"><a href="#analytics/list.php?m=dataexport" id="analytics">{$lang.analytics}</a></span>
						{/if}


						{if $smarty.now<strtotime('2012-01-10')}
							<span> <img src="{$resources}/img/common/new.gif"> <a class="bonus" href="moosnow">  {$lang.asomate_nieve} </a> </span>
						{/if}

						{if $empresaUsuario->isPartner() && $usuario->esValidador()}
							<span>
								<a href="#validation.php" title="Pendientes en cola"  id="validation">
								{$lang.validacion}
								</a>
							</span>
						{/if}


						<span>&#64;{$usuario->getUserName()}</span>


						{if $usuario instanceof usuario}
							| &nbsp;
							<span id="newversion">
						 		<img src="{$resources}/img/common/new.gif"> &nbsp; <a href="/app/settings/appversion?version=2">{$lang.probar_nueva_version}</a>
						 	</span>
						{/if}

					{/if}
				&nbsp;</div>
				<div id="top-bar-right">

					{if isset($usuario)}
						<span> <a href="ayuda.php" class="box-it btn"> <span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" title="{$lang.ayuda}" height="14px" width="14px" /> {$lang.ayuda} </span></span> </a> </span>

						{* AQUI USAMOS USER, PARA FACILITAR EL TRABAJO A SATI POR QUE $USUARIO SIEMPRE SEREMOS NOSOTROS INCLUSO SIMULANDO *}
						{if $user instanceof usuario}
							{assign var=empresaUsuario value=$user->getCompany()}
							{assign var=renewTime value=$empresaUsuario->timeFreameToRenewLicense()}
							{assign var=optionalPayment value=$empresaUsuario->hasOptionalPayment()}
							{assign var=needsPay value=$empresaUsuario->needsPay()}
							{assign var=expiredLicense value=$empresaUsuario->hasExpiredLicense()}
							{assign var=temporaryLicense value=$empresaUsuario->isTemporary()}


							{if $empresaUsuario instanceof empresa && ($needsPay || $renewTime || $temporaryLicense || (($optionalPayment && $expiredLicense) || ($optionalPayment && $renewTime)))}
								{if ($empresaUsuario->timeFreameToRenewLicense() && !$empresaUsuario->isTemporary()) || ($empresaUsuario->hasExpiredLicense() && !$empresaUsuario->hasTemporaryPayment()) && !$empresaUsuario->isFree() }
									{assign var=textBotton value=$lang.renovar_plan_premium_boton}
								{else}
									{assign var=textBotton value=$lang.contratar_plan_premium}
								{/if}
								<span> <a href="/app/payment/license" class="btn">  <span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" title="{$textBotton}" height="14px" width="14px" />{$textBotton}</span></span> </a> </span>
							{/if}

							{if $empresaUsuario instanceof empresa && $empresaUsuario->hasInvoicesNotPayedFrameTime(true)}
								<span> <a href="/app/payment/invoice" class="btn">  <span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/page_white_text.png" title="Pago validacion" height="14px" width="14px" />{$lang.validationPayment}</span></span> </a> </span>
							{/if}

						{/if}

						{if $usuario instanceof usuario}
							{assign var=perfilesUsuario value=$usuario->obtenerPerfiles()}
							{if isset($perfilesUsuario) && is_traversable($perfilesUsuario) && count($perfilesUsuario)}
								{assign var=perfilActivo value=$usuario->perfilActivo()}
								{if count($perfilesUsuario)>1}
									<a class="a-extend right" id="link-perfiles" target="#lista-perfiles" href="">@{$perfilActivo->getUserVisibleName()}</a>
								{else}
									<span>@{$perfilActivo->getUserVisibleName()}</span>
								{/if}
							{/if}

							{if $empresaUsuario instanceof empresa && !$empresaUsuario->needsPay()}
								<img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" title="{$lang.dispones_certificado_dokify}" height="18px" width="18px" />
							{/if}
						{/if}
						<a class="a-extend noline right" target="#config-actions" href="">&nbsp;<img src="{$resources}/img/common/gears-icon.png" />&nbsp;</a>
					{/if}

				</div>
				<div class="clear"></div>
			</div>


			{if isset($usuario)}
			<div>
				{if isset($perfilesUsuario) && count($perfilesUsuario)>1}
					<div id="lista-perfiles" style="display: none;">
						<div class="top-box-content">
							<ul>
								{foreach from=$perfilesUsuario item=perfil}
									{if ($perfil->getUID() != $perfilActivo->getUID())}
										<li><a class="changeprofile line-block" to="{$perfil->getUID()}" href="javascript:void(0)">{$perfil->getUserVisibleName()}</a></li>
									{/if}
								{/foreach}
							</ul>
							{if count($perfilesUsuario)>5}
								<div class="search"><input type="text" class="search find-html" target="#lista-perfiles" rel="li" /></div>
							{/if}
						</div>
					</div>
				{/if}

				<div id="config-actions" style="display:none">
					<div class="top-box-title" style="text-align: right">
						<span class="top-box-image" style="float: left"><img height="60" width="60" style="background-image:url({$smarty.const.RESOURCES_DOMAIN}/img/common/ajax-loader.gif); background-repeat: no-repeat; background-position: center center;" src="{$smarty.const.RESOURCES_DOMAIN}/img/blank.gif" data-src="{$usuario->getImage(false)}" /></span>
						{$usuario->getHumanName()} <br />

						<a href="ficha.php?m={$usuario->getType()}&oid={$usuario->getUID()}" class="box-it">{if $usuario instanceof usuario}@{/if}{$usuario->getUserName()}</a> <br />
						<div id="idiomas" style="float:none; width: auto;">
							<ol style="width:100%; margin-left: 5px;">
								<li class="navPortugues" style="float:right;"><a {if isset($locale)&&$locale == 'pt'} href="javascript:return false;" class="on" {else} href="/set-locale?language=pt_PT" class="off" {/if}>&nbsp;</a></li>

								<li class="navCastellano" style="float:right"><a {if isset($locale)&&$locale== 'es'} href="javascript:return false;" class="on" {else} href="/set-locale?language=es_ES" class="off" {/if}>&nbsp;</a></li>

								<li class="navChileno" style="float:right"><a {if isset($locale)&&$locale== 'cl'} href="javascript:return false;" class="on" {else} href="/set-locale?language=es_CL" class="off" {/if}>&nbsp;</a></li>

								<li class="navIngles" style="float:right"><a {if isset($locale)&&$locale == 'en'} href="javascript:return false;" class="on" {else} href="/set-locale?language=en_GB" class="off" {/if}>&nbsp;</a></li>

								<li class="navFrances" style="float:right; "><a {if isset($locale)&&$locale == 'fr'} href="javascript:return false;" class="on" {else} href="/set-locale?language=fr_FR" class="off" {/if}>&nbsp;</a></li>
							</ol>
						</div>
						<div class="clear"></div>
					</div>
					<div class="top-box-content">
						{if $usuario instanceof usuario }
							<div>
								{if $usuario->isBetatester()}
									Programa beta <strong>activado</strong>
									-
									<a href="/agd/beta/remove.php">desactivar</a>
								{else}
									Programa beta <strong>desactivado</strong>
									-
									<a href="/agd/beta/add.php">activar</a>
								{/if}
							</div>
							<hr />
						{/if}

						{if !$usuario->configValue("limitecliente")}
							<div>
								<input type="checkbox" {if $usuario->configValue('view')}checked{/if} class="post" href="userdata.php?option=view" style="vertical-algin: middle;" />
								{$lang.activar_vista_global}
								&nbsp; | &nbsp;
								<a href="empresa/clients.php" class="box-it">{$lang.clientes}</a>
							</div>
							<hr />
						{/if}

						{if $usuario->esStaff()}
							<div>
								<input type="checkbox" {if $usuario->configValue('viewall')}checked{/if} class="post" href="usuario/prefs.php?viewall=0" style="vertical-algin: middle;" />
								Ver todos los documentos
							</div>
							<hr />
						{/if}

						{if $usuario instanceof usuario && $usuario->accesoConfiguracion()}
							<span id="config-link"><a href="#configurar.php" draggable="true">{$lang.configuracion_sistema}</a></span> <br />
							<hr />
						{/if}
						<a href="{if isset($exitlink)}{$exitlink}{else}./salir.php?manual=1{/if}">{$lang.cerrar_sesion}</a> <span style="float:right" class="light right">{$lang.ultimo_acceso|default:"Último acceso"}: {'usuario::getLastLogin'|call_user_func}</span>
					</div>
				</div>

				<div id="chat-user-list" style="display:none; width: 260px;">
					<ul id="friend-list" class="top-box-content">
						<li id="chat-user-loading"> <img src="{$resources}/img/common/ajax-loader.gif" /> Cargando... </li>
					</ul>
				</div>
			</div>
			{/if}



			<div id="menu-avisos" {if $embedded}style="display:none"{/if}>
				<div class="avisos-principal">
					<div>
						<ul>
							{if isset($avisos) && count($avisos)}
								{foreach from=$avisos item=aviso}
								{if $aviso.tipo}
									{assign var=tipo value=$aviso.tipo}
								{else}
									{assign var=tipo value="aviso"}
								{/if}
								<li {if $aviso.id}id="aviso-{$aviso.id}"{/if} {if $aviso.className}class="{$aviso.className}"{/if}>
									<div>
										<span>{$aviso.titulo}</span>
										{$aviso.texto}
									</div>
								</li>
								{/foreach}
							{/if}
						</ul>
					</div>
				</div>
				<div id="boton-avisos" class="click-bar toggle" target=".avisos-principal" rel="slideToggle" {if isset($avisos) && count($avisos)}{else} style="display:none;"{/if}>
					<a href="avisos">Atención, tienes <span id="numeroavisos">{if isset($avisos) && print(count($avisos))}{/if}</span> avisos pendientes</a>
				</div>
			</div>


			{assign var=route value=$smarty.const.DIR_TEMPLATES|cat:$route}
			{include file="$route"}

			{if $user->esAdministrador()}
				<div id="bottomdata" {if $embedded}style="display:none"{/if}>
					<div>
						{assign var=headCommit value='git_get_head'|call_user_func}
						{assign var=headDate value='git_get_head_date'|call_user_func}


						<a href="https://github.com/Dokify/dokify/commit/{$headCommit}" title="{$headDate}" target="_blank">{$headCommit}</a>

						{if $smarty.const.CURRENT_ENV == 'dev'}
						&nbsp; | &nbsp;
							{assign var=currentBranch value='git_get_current_branch'|call_user_func}
							{assign var=branches value='git_get_remote_branches'|call_user_func}
							<select name="branch"  class="go">
								{foreach from=$branches item=branch}
									<option value="/git.php?c=checkout&amp;b={$branch}" data-target="top" {if $currentBranch == $branch}selected{/if}>{$branch}</option>
								{/foreach}
							</select>
						{/if}
					</div>
				</div>
			{/if}
		</div>

		<iframe style="display: none;" id="async-frame" name="async-frame"></iframe>



		{if isset($open)} <script>var __openOnStart = '{$open}';</script> {/if}
		{if isset($usuario)}
			<script>var __currentUser = '{$usuario->getUserName()}';
				{if $usuario instanceof usuario && $usuario->getMostrarAsistente() && !$usuario->isEnterprise() && !$usuario->perteneceCorporacion()}
					{if $smarty.get.ref == 'paypal-complete'}
						var __asistente = "asistente.php?step=3";
					{else}
						var __asistente = "asistente.php";
					{/if}
				{elseif $smarty.const.CURRENT_ENV != 'dev'}
					{literal}
					(function(){var uv=document.createElement('script');uv.type='text/javascript';uv.async=true;uv.src='//widget.uservoice.com/yhpPTncHEXpYpyRReAkZlw.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(uv,s)})()

					UserVoice = window.UserVoice || [];
					UserVoice.push(['setSSO', '{/literal}{$user->getUserVoiceToken()}{literal}']);
					{/literal}
				{/if}
			</script>


			{literal} <script> if (navigator.userAgent.indexOf("MSIE 10") > -1) { document.body.classList.add("ie10"); } </script> {/literal}
		{/if}

		{include file=$tpldir|cat:'analyticsGoogle.tpl'}
	</body>
</html>
