<div class="padded">
	{assign var="empresa" value=$certificacion->getCompany()}
	{assign var="referencia" value=$certificacion->obtenerReferencia()}
	{assign var="conceptos" value=$certificacion->obtenerConceptosAsignados()}
	{assign var="time" value=$certificacion->getTime()}
	
	<div style="float: right">
		<button class="btn" href="certificacion/export.php?selected[]={$referencia->getUID()}&poid={$empresa->getUID()}&oid={$smarty.get.poid}" target="#async-frame"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/page_excel.png" /> {$lang.exportar}</span></span></button>
	</div>
	<h1 style="font-size: 16px">Certificacion de {$empresa->getUserVisibleName()} para {$referencia->getUserVisibleName()} {$certificacion->getDate()}</h1>
	<hr />
	<div>
		<table class="extra-table">
			{assign var="total" value=0}
			{assign var="ambito" value=""}
			

			{if count($conceptos)}
				{foreach from=$conceptos item=agrupador key=i}
					{if $ambito != $agrupador->obtenerDato("ambito")}
						{if $sumaambitos}
							<tr><td colspan="3"></td><td colspan="2"><strong>Total {$ambito}: {$sumaambitos}€</strong></td></tr>
						{/if}
						{assign var="ambito" value=$agrupador->obtenerDato("ambito")}
						{assign var="sumaambitos" value=0}
						<tr>
							<td colspan="5"><strong>{$ambito}</strong></td>
						</tr>
					{/if}

					{assign var="unidades" value=$certificacion->getUnits($agrupador)}

					{if $parametro=reset($empresa->obtenerParametrosDeRelacion($agrupador))}
						{assign var="coste" value=$parametro->obtenerDato("precio_unitario")}
					{else}
						{assign var="coste" value=$agrupador->obtenerDato("precio_unitario")}
					{/if}

					{assign var="precio" value=$unidades*$coste}
					{assign var="sumaambitos" value=$sumaambitos+$precio}

					<tr>
						<td style="padding-left: 10px">{$agrupador->getUserVisibleName()}</td>
						<td class="column-separator"></td>
						<td>{$unidades} * {$coste}€</td>
						<td>{$unidades*$coste}€</td>
						<td></td>
					</tr>

				
					{if $i == (count($conceptos)-1)}
						<tr><td colspan="3"></td><td colspan="2"><strong>Total {$ambito}: {$sumaambitos}€</strong></td></tr>
					{/if}

					{assign var="total" value=$total+$unidades*$coste}
				{/foreach}	
			{else}
				<tr><td colspan="5" style="padding-left: 10px"> {$lang.sin_conceptos_seleccionados} </td></tr>
			{/if}
			<tr><td colspan="5"><hr /></td></tr>
			<tr>
				<td colspan="3"></td>
				<td><strong>Total: {$total}€</strong></td>
				<td></td>
			</tr>
		</table>
	</div>
</div>
