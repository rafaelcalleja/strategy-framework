<div class="box-title">
	{$lang.alarma}
</div>
	<div style="text-align: center; width: 720px;">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		{assign var="alarmas" value=$objActual->obtenerAlarmas()}
		{if count($alarmas)}
			{foreach from=$alarmas item=alarma}
				{assign var="emails" value=$alarma->obtenerDato("email[]")}
				{assign var="accesoEliminar" value=$usuario->accesoEliminarElemento($alarma, $config)}
				<div class="message highlight cbox-content" style="text-align: left;">
					<table style="">
						<tr>
							<td>
								<form name="contacto-empresa" action="{$smarty.server.PHP_SELF}" class="ficha form-to-box" id="contacto-empresa" enctype="multipart/form-data" method="POST">
									<table style="margin: 0 0 0 0px; width: auto; width: 650px;">
										<tr> <td colspan="2"><span style="font-weight:bold;font-style:italic;font-size:14px">{$alarma->getUserVisibleName()} {if $alarma->isSend()} · OK{/if}</span></td> </tr>
										<tr> <td><strong>Comentarios:</strong></td> <td>{$alarma->obtenerDato("comentario")}</td> </tr>
										<tr> <td><strong>Fecha Aviso:</strong></td> <td>{$alarma->obtenerDato("fecha_alarma")}</td> </tr>
										<tr> <td><strong>Emails:</strong></td> <td>{if count($emails)}{if print implode(", ",$emails)}{/if}{else}{$lang.no_hay_emails}{/if}</td> </tr>
									</table>
									<input type="hidden" name="poid" value="{$smarty.get.poid}"/>

									{if $accesoEliminar}
										<div style="padding: 8px">
											<hr />
											<ul class="inline-options">
												<li><a class="{if $opt.class}{$opt.class}{else}box-it{/if} btn" href="eliminar.php?m=alarma&poid={$alarma->getUID()}&return=alarma%2Falarma.php%3Fpoid%3D{$objActual->getUID()}%26m%3D{$smarty.get.m}"><span><span><img src="{$resources}/img/famfam/cancel.png" /> Eliminar Alarma</span></span></a></li>
											</ul>
										</div>
									{/if}

								</form>
							</td>
						</tr>
					</table>
				</div>
			{/foreach}
		{else}
			{$lang.no_hay_alarmas}
		{/if}
		</div>
		<div class="cboxButtons">
			<button class="btn box-it" href="alarma/nuevo.php?poid={$smarty.get.poid}&m={$smarty.get.m}">
				<span><span> <img src="{$resources}/img/famfam/add.png"> {$lang.crear_alarma} </span></span>
			</button>
		</div>
