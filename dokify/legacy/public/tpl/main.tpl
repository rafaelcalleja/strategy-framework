<div id="head" {if $embedded}style="display:none"{/if}>
	{if isset($empresaUsuario) && $empresaUsuario->getSkinName()=="dokify" && isset($logo)} 
		<div id="logo-cliente"><img src="{$logo}" style="max-height: 70px" /></div>
	{/if}
	{if isset($empresaUsuario) && isset($logo) && !$empresaUsuario->getSkinName()}
		<div id="logo-cliente"><img src="{$logo}" style="max-height: 70px" /></div>
	{/if}
	<table width="100%" class="head"><tr>
		<td></td>
		<td>
			<div>
				<div id="head-buttons" class="line-block">
					{if isset($modules)&&count($modules)}
					<div id="main-menu">
						<ul>
							{foreach from=$modules item=module key=i}
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

								{assign var=title value=$module.title}
								<li class="line-block {if isset($module.selected)}seleccionado{/if}{if $i==0} first-child{/if}{if isset($modules) && ($i+1)==count($modules)} last-child{/if}" name="{$module.name}">
									<a href="{$module.href}" title="{$lang.$title}">
										<img src="{$imgpath}" height="32px"/>
										{if !isset($module.icononly) || !$module.icononly}
											<div class="line-block">
												{if isset($lang[$langstring])}{$lang[$langstring]}{else}{$module.name}{/if}
											</div>
										{/if}
									</a>
								</li>
							{/foreach}
						</ul>
					</div>
					{/if}
				</div>
			</div>
		</td>
	</tr></table>
	<div id="sub-head">
		{if isset($modules) && count($modules) && !isset($hidesearch)}
			<div id="buscador">
				<div>
					<div id="buscador-clear"></div>
					<form method="GET" action="buscar.php" class="sendhash" >
						<input type="text" name="q" id="buscar" value="{$lang.buscar}..." x-webkit-speech />
						<button id="boton-buscar" type="submit" class="btn" style="width: 55px">{$lang.buscar}</button>
						{if $usuario->comprobarAccesoOpcion('196')}
							<button id="busquedas" href="#busqueda/listado.php" style="width: 30px" title="{$lang.desc_ver_busquedas_guardadas}" data-gravity="e"><img src="{$resources}/img/famfam/disk.png"></button>
						{/if}

						{assign var=op value=$user->getAvailableOptionsForModule("buscador","avanzadas")}
						{assign var=op value=$op.0}
						{if $op}
							<button href="{$op.href}" style="width: 30px" title="{$lang.desc_busquedas_avanzadas}" data-gravity="e"><img src="{$op.icono}"></button>
						{/if}
					</form>									
				</div>
			</div>
		{/if}
		<div id="head-text"></div>
		<div id="informacion-navegacion"></div>
	</div>
</div>

<div id="main">

</div>
