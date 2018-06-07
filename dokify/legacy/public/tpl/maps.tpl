<div class="padded">

	<div style="padding-bottom: 2em" id="toolbar">
		{assign var="company" value=$user->getCompany()}

		{if $company->isFree()}
			<strong class="red">{$lang.pago_maps} <a href="/app/payment/license">{$lang.pasate_premium}</a></strong>

		{else}
			<button class="button s {if $smarty.request.live != 'false'}green actived{else}grey{/if} searchtoggle" target="live" value="false">{$lang.actualizar_tiempo_real}</button>

			<span class="light" style="margin:1em"> | </span>

			{if $item instanceof empleado}
				<span>{$lang.viendo_maps|sprintf:$item->obtenerUrlFicha($item->getUserVisibleName())}</a>. {$lang.para_ver_todos_empleados} <a href="#maps.php{if $smarty.request.live == 'false'}?live=false{/if}">{$lang.haz_click_aqui}</a>
			{else}
				{if $item instanceof empresa}
					<a class="button s grey" style="text-decoration:none" href="#maps.php{if $smarty.request.live == 'false'}?live=false{/if}"> {$lang.ver_todos_empleados} </a>
				{else}
					
					<a class="button s grey" style="text-decoration:none" href="#maps.php?m=empresa&amp;poid={$company->getUID()}{if $smarty.request.live == 'false'}&amp;live=false{/if}"> {$lang.ver_solo_mis_empleados} </a>
				{/if}

				<span class="light" style="margin:1em"> | </span>
				<span id="maps-activity"> {$lang.cargando}... </span>
			{/if}
		{/if}
	</div>

	{if $mapsrc}
		<div class="map" style="width:100%;height:500px;" data-src="{$mapsrc}" {if $smarty.request.live != 'false'}data-polling="true"{/if} data-activity="#maps-activity"></div>
	{/if}
</div>