



	<div style="text-align: center;">
		
			{assign var="datos" value=$elemento->getInfo(true)}
			{assign var="modulos" value=$elemento->obtenerModulos()}
			{assign var="disponibles" value=$elemento->obtenerModulosDisponibles()}
			<strong class="ucase">{$elemento->getUserVisibleName()}</strong>

			{if isset($datos.descripcion)}{$datos.descripcion}{/if}
			<hr />
				

			<div class="cbox-content">
				<div style="text-align: left"><h3 >{$lang.seleccionar_modulos_aplicables}</h3><br /></div>

				<table class="item-list">
					{foreach from=$disponibles item=modulo}
						<tr>
							<td class="field-list">{$lang.$modulo}</td>
							<td class="field-list"><input type="checkbox" class="line-check box-it" href="{$smarty.server.PHP_SELF}?action=asignar&mid={$elemento->obtenerIdModulo($modulo)}&poid={$smarty.get.poid}" /></td>
						</tr>
					{/foreach}
					{foreach from=$modulos item=modulo}
						<tr class="selected-row">
							<td class="field-list">{$lang.$modulo}</td>
							<td class="field-list"><input type="checkbox" class="line-check box-it" checked href="{$smarty.server.PHP_SELF}?action=desasignar&mid={$elemento->obtenerIdModulo($modulo)}&poid={$smarty.get.poid}" class="box-it"/></td>
						</tr>
					{/foreach}
				</table>
			</div>

		
	</div>

