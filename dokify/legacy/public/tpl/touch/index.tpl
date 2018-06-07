<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="shortcut icon" href="{$resources}/img/favicon.ico" />
		<link rel="icon" href="{$resources}/img/favicon.ico" />

		<link rel="stylesheet" href="{$resources}/touch/css/main.css?{$smarty.const.VKEY}" type="text/css" />
		{if $empresaUsuario}<style id="inline-style">{$empresaUsuario->getStyleString(false)}</style>{/if}

		<title>dokify</title>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
		<script src="{$resources}/touch/js/forms.min.js?{$smarty.const.VKEY}"></script>
		{include file=$smarty.const.DIR_ROOT|cat:"/tpl/webapp.tpl"}

    	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
	</head>
	<body>
		<div id="loading"><div>{$lang.cargando}...</div></div>

		<div id="viewport">
			<div id="sidebar">
				<ul style="margin-bottom:80px">
					<li>
						<div style="padding-top: 2px">
							<img src="{$user->getImage()}" class="icon" style="width:32px;height:32px;float:left;margin-top:6px" />
							<div style="float:left; line-height: 18px; width: 146px; white-space: nowrap; overflow: hidden; margin-top: 2px">
								<span style="font-size: 14px;">{$usuario->getUserName()}</span>
								<br />
								<span style="font-size: 12px;">{$perfilActivo->getUserVisibleName()}</span>
							</div>
						</div>
					</li>

					{assign var=profiles value=$user->obtenerPerfiles()}
					{if count($profiles) > 1}
						{assign var=activeProfile value=$user->perfilActivo()}

						<h3>Perfiles</h3>

						{foreach from=$profiles item=profile key=i}
							{if $activeProfile->compareTo($profile) == false}
								<li style="white-space:nowrap; overflow:hidden">
									<a href="../chgperfil.php?pid={$profile->getUID()}">
										<img src="{$resources}/img/32x32/iface/refresh.png" style="width:30px;height:30px" class="icon" />
										<span>{$profile->getUserVisibleName()}</span>
									</a>
								</li>
								{if $i > 3}{break}{/if}
							{/if}
						{/foreach}
					{/if}

					<h3>Menu</h3>
					{foreach from=$modules item=module}
						{if !isset($module.img)&&!isset($module.imgpath)}
							{assign var=imgpath value="$resources/img/32x32/iface/"|cat:$module.name|cat:".png"}
						{else}
							{if isset($module.imgpath)}
								{assign var=imgpath value=$module.imgpath}
							{else}
								{assign var=imgpath value=$module.img}
							{/if}
						{/if}
						{if !isset($module.lang)}
							{assign var=langstring value="menu_"|cat:$module.name}
						{else}
							{assign var=langstring value=$module.lang}
						{/if}

						<li name="{$module.name}">
							<a href="{$module.href}">
								<img src="{$imgpath}" class="icon" />
								<span>
									{if isset($lang[$langstring])}{$lang[$langstring]}{else}{$module.name}{/if}
								</span>
							</a>
						</li>
					{/foreach}

					<h3>&nbsp;</h3>
					<li>
						<a href="/agd/salir.php?manual=1">
							<img src="{$smarty.const.RESOURCES_DOMAIN}/touch/img/common/logout.png" class="icon" />
							<span>{$lang.salir}</span>
						</a>
					</li>

					{if $user instanceof usuario && $user->isBetatester()}
						<h3>&nbsp;</h3>
						<li>
							<a href="/app/settings/appversion?version=2">
								<span>&nbsp; {$lang.probar_nueva_version}</span>
							</a>
						</li>
					{/if}
				</ul>
			</div>

			<div id="page">
				{if isset($smarty.request.src) && $smarty.request.src == 'qr'}
					{*Version simple*}
				{else}
					<div id="top-bar">
						<div class="left">
							<a id="menu-link" style="border:0"><img data-src="{$smarty.const.RESOURCES_DOMAIN}/touch/img/common/menu.png" id="home-icon" width="32" height="32" style="vertical-align: middle" /></a>

						</div>
						<div class="right" style="text-align: right">
							<form method="get" onsubmit="location.href='#buscar.php?p=0&q='+this.q.value;return false;"><input type="text" name="q" id="search-box" placeholder="{$lang.buscar}" /></form>
						</div>
					</div>
				{/if}
				<div id="page-body">
					{*
					<div id="main-menu">
						<ul>
							{foreach from=$modules item=module}
								{if !isset($module.img)&&!isset($module.imgpath)}
									{assign var=imgpath value="$resources/img/32x32/iface/"|cat:$module.name|cat:".png"}
								{else}
									{if isset($module.imgpath)}
										{assign var=imgpath value=$module.imgpath}
									{else}
										{assign var=imgpath value=$module.img}
									{/if}
								{/if}
								{if !isset($module.lang)}
									{assign var=langstring value="menu_"|cat:$module.name}
								{else}
									{assign var=langstring value=$module.lang}
								{/if}
								<li class="{if isset($module.selected)}seleccionado{/if}" name="{$module.name}">
									<a href="{$module.href}">
										<img src="{$imgpath}" height="32px"/>
										<div class="line-block">
											{if isset($lang[$langstring])}{$lang[$langstring]}{else}{$module.name}{/if}
										</div>
									</a>
								</li>
							{/foreach}
							{if $usuario instanceof usuario && $usuario->accesoConfiguracion()}
								<li class="{if isset($module.selected)}seleccionado{/if}" name="configurar">
									<a href="#configurar.php">
										<img src="{$resources}/img/common/gears-icon.png" height="32px"/>
										<div class="line-block">
											{$lang.configurar}
										</div>
									</a>
								</li>
							{/if}
						</ul>
					</div>
					*}
					<div id="page-content">
						<div id="view-data"><div id="data-content"></div></div>
						<div id="view-simple"><div id="main"></div></div>
						<div id="view-options"><div class="option-list"></div></div>
					</div>
					<div id="view-offline" style="display:none">
						<div class="bad-profile">
							Sorry, you are offline!
						</div>
					</div>
				</div>

				{if $nextQR}
					<a  href="dokireader://" id="next-qr" class="button xl yellow">
						Escanear otro código
					</a>
				{/if}
			</div>
		</div>

		<script>window.__resources = '{$resources}'; window.__rversion = '{$smarty.const.VKEY}';</script>
		<script src="{$resources}/js/require.js"></script>
		<script src="{$resources}/touch/js/main.min.js?{$smarty.const.VKEY}" type="text/javascript"></script>

		{include file=$tpldir|cat:'analyticsGoogle.tpl'}
	</body>
</html>
