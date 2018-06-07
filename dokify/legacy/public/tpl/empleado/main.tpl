<div id="head">
	<table width="100%" class="head"><tr>
		<td></td>
		<td>
			<div>
				<div id="head-buttons" class="line-block">
					{if isset($modules)&&count($modules)}
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
								<li class="line-block {if isset($module.selected)}seleccionado{/if}" name="{$module.name}">
									<a href="{$module.href}">
										<img src="{$imgpath}" height="32px"/>
										{if !$module.icononly}
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
		<div id="head-text"></div>
		<div id="informacion-navegacion"></div>
	</div>
</div>
<div>
	<div id="conformidad">
		{include file=$smarty.const.DIR_TEMPLATES|cat:"empleado/confirmation-line.tpl" empleado=$user}
	</div>
</div>
<div id="main"></div>
