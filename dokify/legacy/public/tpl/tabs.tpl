<div class="box-title">
	{if isset($lang.$titulo)}{$lang.$titulo}{else}{$titulo}{/if}

	<div class="tabs">
	{if count($tabs)}
		{assign var="i" value=0}
		{foreach from=$tabs item=tab key=name}
			{assign var="idname" value=$name|md5}
			<div class="box-tab {if ( (!$i && !isset($currenttab) ) || ( isset($currenttab) && $currenttab==$idname) )}selected{/if}" rel="#tab-{$idname}">{if isset($lang.$name)}{$lang.$name}{else}{$name}{/if}</div>
			{assign var="i" value=$i+1}
		{/foreach}
	{/if}
	</div>
</div>
<form name="tabs-form" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="tabs-form" method="post">
	<div style="text-align: center; width: 675px;">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}

		<div id="tabs-content">
		{if count($tabs)}
			{assign var="i" value=0}
			{foreach from=$tabs item=tab key=name}

				{assign var="idname" value=$name|md5}

				<div id="tab-{$idname}" {if ( ($i && !isset($currenttab) ) || ( isset($currenttab) && $currenttab!=$idname) )}style="display:none;"{/if}>
					{if isset($asignaciones) && isset($asignaciones.$name)}
						{assign var="asignacion" value=$asignaciones.$name}
						{include file="$tpldir/$tab" asignados=$asignacion.asignados disponibles=$asignacion.disponibles al_vuelo=$asignacion.al_vuelo agrupamiento=$asignacion.agrupamiento prefix=$asignacion.agrupamiento|cat:"-" }
					{else}
						{include file="$tpldir/$tab" }
					{/if}
				</div>
				{assign var="i" value=$i+1}
			{/foreach}
		{/if}
		</div>
	</div>
	<div class="cboxButtons">
		{if isset($buttons)}
			{foreach from=$buttons item=button key=i}
				{assign var="html" value=$button.innerHTML}
				<button class="btn {if $button.className}{$button.className}{/if}"><span><span> 
					{if isset($button.img)} <img src="{$button.img}" /> {/if}{if isset($lang.$html)}{$lang.$html}{else}{$html}{/if}
				</span></span></button>
			{/foreach}
		{/if}
	</div>
	<input type="hidden" name="send" value="1" />
	{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
	{if isset($smarty.request.o)}<input type="hidden" name="o" value="{$smarty.request.o}" />{/if}
	{if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}" />{/if}
	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($smarty.request.tab)}<input type="hidden" name="tab" value="{$smarty.request.tab}" />{/if}
	{if isset($smarty.request.return)}<input type="hidden" name="return" value="{$smarty.request.return}" />{/if}
	{if isset($smarty.request.frameopen)}<input type="hidden" name="frameopen" value="{$smarty.request.frameopen}" />{/if}
</form>


