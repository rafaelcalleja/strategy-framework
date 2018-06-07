<div class="box-title">
	{$lang.enviar_papelera}
</div>
<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="elemento-form-papelera">
	<div style="width: 580px;">
		{include file=$errorpath}
		{include file=$infopath}
		{assign var=confirm_button value=1 }
		<div class="cbox-content" >
			{$lang.confirmar_enviar_papelera_subcontrata}
			</br>
			</br>
			<div >
				<button class="btn toggle" target="#opcionesAvanzadas"><span><span> <img src="{$resources}/img/famfam/add.png" /> {$lang.opciones_avanzadas}</span></span></button>
			</div>
			<div id="opcionesAvanzadas" style="display:none">
				</br>
				{if count($cadenasContratacion)}
					{include file=$alertpath}
					<p class="padded">{$lang.indicar_cadenas_contratacion}</p>

					<hr />

					<div class="cbox-list-content item-list" style="width: 100%; max-height: 360px">
						<table>	
		
							{foreach from=$cadenasContratacion item=cadenaContratacion}
								<tr class="selected-row isolated-row">
									<td style = "text-align: left ; width:40px" >
										<span><input style="padding-left" type="checkbox" name="list[]" class="line-check toggle" target="tr.sup-of-{$cadenaContratacion->getUID()}" value="{$cadenaContratacion->getUID()}" checked />
										</span>
									</td>							
								{foreach from=$cadenaContratacion key=i item=empresa}
									<td>
										<span>{$empresa->getUserVisibleName()}</span>
									</td>
									{if $i==2 && count($cadenaContratacion)==3}
									<td></td>
									{/if}
								{/foreach}
								</tr>
								{assign var="residualChains" value=$cadenaContratacion->getResidualChains()}
								{if $residualChains}
									{foreach from=$residualChains item=residualChain}
										{assign var="supchecked" value=""}
										<tr class= "sup-of-{$cadenaContratacion->getUID()} toggled-row isolated-row">
											<td style="padding-left: 3em; background-position: 12px center; text-align: left" class="row-link">
												<span><input type="checkbox" name="{$cadenaContratacion->getUID()}" class="line-check" checked disabled /></span>
											</td>
											{foreach from=$residualChain key=i item=empresa}
												<td>
													<span>{$empresa->getUserVisibleName()}</span>
												</td>
											{/foreach}
										</tr>	
									{/foreach}
								{/if}
							{/foreach}				
						</table>
					</div>				
				{else}
					{assign var=confirm_button value=0 }
					{$lang.mensaje_subcontrata_no_asignada}
				{/if}
				{if isset($smarty.request.request)}<input type="hidden" name="request" value="{$smarty.get.request}" />{/if}
			</div>
		</div>
	</div>
	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		{if $confirm_button}<button class="btn" type="submit"><span><span> <img src="{$resources}/img/famfam/accept.png"> {$lang.confirmar} </span></span></button>{/if}
	</div>
</form>
