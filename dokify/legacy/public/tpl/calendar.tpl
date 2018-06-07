<div>
	<div style="padding: 10px 50px 0;">
		<button class="btn searchtoggle pulsar" target="attached" value="1"><span><span>
			<div class="line-block stat stat_1" style="position:relative; top:-1px; padding: 1px 3px">&nbsp;&nbsp;</div>
			{$lang.anexar}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="validated" value="2"><span><span>
			<div class="line-block stat stat_2" style="position:relative; top:-1px; padding: 1px 3px">&nbsp;&nbsp;</div>
			{$lang.validar}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="rejected" value="4"><span><span>
			<div class="line-block stat stat_4" style="position:relative; top:-1px; padding: 1px 3px">&nbsp;&nbsp;</div>
			{$lang.anular}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="expired" value="3"><span><span>
			<div class="line-block stat stat_3" style="position:relative; top:-1px; padding: 1px 3px">&nbsp;&nbsp;</div>
			{$lang.caducar}
		</span></span></button>


		&nbsp; <span class="light"> | </span> &nbsp;

		<button class="btn searchtoggle pulsar" target="manual" value="1"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bell.png" alt="" />
			{$lang.alarma}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="empresa" value="1"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/sitemap_color.png" alt="" />
			{$lang.empresa}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="empleado" value="1"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/group.png" alt="" />
			{$lang.empleados}
		</span></span></button>

		<button class="btn searchtoggle pulsar" target="maquina" value="1"><span><span>
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/car.png" alt="" />
			{$lang.maquinas}
		</span></span></button>


		&nbsp; <span class="light"> | </span> &nbsp;

		{if $groups}
			<form style="display:inline">
				<select class="go" name="comefrom" data-target="top">
					<option value="#empresa/calendario.php">{$lang.config_filter}</option>
					{foreach from=$groups item=group}
						{assign var="kind" value=$group->obtenerAgrupamientoPrimario()}
						{assign var="client" value=$kind->getCompany()}

						<option value="#empresa/calendario.php?comefrom={$group}" {if $group->compareTo($comefrom)}selected{/if}>{$group->getSelectName()}  ({$client->getUserVisibleName()})</option>
					{/foreach}
				</select>
			</form>

			&nbsp;
		{/if}

		{if $comefrom instanceof elemento}
			<strong>{$lang.filtrar_por} <span class="red">{$comefrom->getSelectName()}</span></strong>
		{/if}

	</div>
	<div class='calendario' src='{$src}'>

	</div>
</div>