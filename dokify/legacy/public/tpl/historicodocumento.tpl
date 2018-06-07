{*
Descripcion
	-	Pensada para usar con modalbox, incluye referencias info, error y succes

En uso actualmente
	-	

Variables
	· $documento - Objeto Documento
	· $usuario - Objecto usuario actual
*}

{assign var=totalHistorico value=$documento->obtenerHistorico($user)}
{if $selectedRequest}
	{assign var=coleccionHistorico value=$documento->obtenerHistorico($user, $selectedRequest)}
{else}
	{assign var=coleccionHistorico value=$totalHistorico}
{/if}
<div class="box-title">
	{$lang.historico_documento}
</div>
<div style="width: 850px;">
	{include file=$errorpath}
	{include file=$succespath}
	{include file=$infopath}

	<form name="lista-historico-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box agd-form" id="lista-historico-documento" method="POST">

		<div class="cbox-content">
			<h1>{$documento->getUserVisibleName()}</h1>

			<div style="padding: 0 0 1em">
				{if $selectedRequest && $totalHistorico|count > $coleccionHistorico|count}
					<span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="documentohistorico.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
				{/if}
			</div>
		</div>

		{if $coleccionHistorico && count($coleccionHistorico)}
			<table class="item-list" style="table-layout: auto;">
				<thead>
					<tr class="strong">
						<td style="text-indent: 8px;"> {$lang.alias} </td> 
						<td> {$lang.estado} </td> 
						<td> {$lang.solicitante} </td> 
						<td> {$lang.fecha_anexion} </td> 
						<td> {$lang.fecha} </td> 
						<td></td>
						<td></td>
					</tr>
				</thead>

				{foreach from=$coleccionHistorico item=elementoHistorico key=i}
					{assign var=solicitante value=$elementoHistorico->obtenerSolicitante()}
					{assign var=anexoh value=$elementoHistorico->getAnexo()}
					<tr class="selected-row">
						<td style="text-indent: 8px; padding: 6px 3px;">{$solicitante->getHTMLDocumentName()}</td>
						<td>
							<span class="docinfo stat_{$elementoHistorico->obtenerEstado(false)}" style="padding:2px">{$elementoHistorico->obtenerEstado()}</span>
						</td>
						<td>{$solicitante->getUserVisibleName()}</td>
						<td>{'d-m-Y'|date:$elementoHistorico->obtenerDato("fecha_anexion")}</td>
						<td>{'d-m-Y'|date:$anexoh->getRealTimestamp($timezone)}</td>
						<td class="padded">
							<a title="{$lang.ver_log}" href="#logui.php?m={$elementoHistorico->getHistoricModule()|strtolower}&poid={$anexoh->getUID()}" class="unbox-it"><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/time_go.png" alt="l" style="vertical-align: middle;" /></a>
						</td>
						<td class="padded"> <a href="{$smarty.server.PHP_SELF}?poid={$smarty.get.poid}&o={$smarty.get.o}&m={$smarty.get.m}&oid={$elementoHistorico->getUID()}&action=dl" target="async-frame">{$lang.descargar}</a> </td>
					</tr>
				{/foreach}
			</table>
		{else}
			<div class="cbox-content">{$lang.sin_historico}</div>
		{/if}

		<input type="hidden" name="poid" value="{$smarty.get.poid}" />
		<input type="hidden" name="o" value="{$smarty.get.o}" />
		<input type="hidden" name="m" value="{$smarty.get.m}" />
		<input type="hidden" name="send" value="1" />

		<div class="cboxButtons"></div>
	</form>
</div>