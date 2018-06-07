<div class="padded asistente" style="font-family: sans-serif;">
	{assign var="totalreferencia" value=0}
	{assign var="empresas" value=$referencia->obtenerEmpresasCertificadas()}
	<h1 style="font-size: 16px">Reporte certificaciones de {$referencia->getUserVisibleName()}
		de {$smarty.get.datestart} a {$smarty.get.dateend}
	</h1>
	<div>
		
		{foreach from=$empresas item=empresa }
			
			{assign var="totalempresa" value=0}

			{assign var="certificaciones" value=$empresa->obtenerCertificaciones($referencia,$smarty.get.datestart,$smarty.get.dateend)}
			<div class="padded">
				<h1 style="font-size: 14px">{$empresa->getUserVisibleName()}</h1>
					{foreach from=$certificaciones item=certificacion key=i }
						{assign var="ambito" value=""}
						{assign var="sumaambitos" value=0}
						{assign var="conceptos" value=$certificacion->obtenerConceptosAsignados()}
							{if count($conceptos)}
							<div class="padded">
								<table class="extra-table">
									<thead>
										<tr><th colspan="5" style="text-align: left;">{$lang.certificacion} {$certificacion->getDate()}</th></tr>
									</thead>
									{assign var="total" value=0}
									
									{foreach from=$conceptos item=agrupador key=i}
										{if $ambito != $agrupador->obtenerDato("ambito")}
											{if $ambito}
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
											<td>{$unidades} * {$coste}€ {$ambito}</td>
											<td>{$precio}€</td>
											<td></td>
										</tr>
				
										{if $i == (count($conceptos)-1)}
											<tr><td colspan="3"></td><td colspan="2"><strong>Total {$ambito}: {$sumaambitos}€</strong></td></tr>
										{/if}

										{assign var="costeactual" value=$unidades*$coste}
										{assign var="total" value=$total+$costeactual}
										{assign var="totalempresa" value=$totalempresa+$costeactual}
										{assign var="totalreferencia" value=$totalreferencia+$costeactual}
									{/foreach}
									<tr><td colspan="5"><hr /></td></tr>
									<tr>
										<td colspan="3">
											{if $i+1 == count($certificaciones)}
												{*<span style="font-size:14px">Total empresa: {$totalempresa}€</span>*}
											{/if}
										</td>
										<td><strong style="font-size: 1.2em;">Subtotal {$certificacion->getDate()} : {$total}€</strong></td>
										<td></td>
									</tr>
								</table>
							</div>
						{else}
							<tr><td colspan="5" style="padding-left: 10px"> {$lang.sin_conceptos_seleccionados}: {$certificacion->getDate()} <br /> </td></tr>
						{/if}
					{/foreach}

				<span style="font-size:14px">Total empresa: {$totalempresa}€</span>
			</div>
		{/foreach}
		<span style="font-size:16px">Total Parque: {$totalreferencia}€</span>
	</div>
</div>
<hr />
