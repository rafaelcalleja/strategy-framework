{*
Descripcion
	Plantilla para su uso en modalbox, incluye referencias a error, succes e info
	Es siminar a <a href='?tpl=anexar'>anexar</a> pero para anular documentacion
	El objeto tipo documento se encarga de buscar los solicitantes y mostrarlos por pantalla

En uso actualmente
	-	/agd/borrar.php

Variables
	· $elemento - Objeto tipo x al cual se borrar el documento
	· $documento - Objeto tipo documento
	· $usuario - Objeto usuario en uso
*}

{if $selectedRequest}
	{assign var=anexos value=$documento->obtenerAnexos($elemento, $user, $selectedRequest)}

	{assign var=total value=$documento->obtenerAnexos($elemento, $user)}
	{assign var=total value=$total|count}
{else}
	{assign var=anexos value=$documento->obtenerAnexos($elemento, $user)}
	{assign var=total value=$anexos|count}
{/if}

<div class="box-title">
	{$lang.borrar_archivo}
</div>
<form name="borrar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box agd-form" id="borrar-documento">
	<div>
		{if isset($error)}
			<div class="message error" style="width: 400px; text-align: left;">
				{$lang.$error|default:$lang.error_borrar}
			</div>
			
		{/if}

		<div style="width: 750px">

			<div class="cbox-content">
				<h1>{$documento->getUserVisibleName()}</h1>

				<div style="padding: 0 0 1em">
					{$lang.borrar_texto}
					{if $selectedRequest && $total > 1}
						- <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="borrar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
					{/if}
				</div>
			</div>

			<table class="item-list">
				<tr class="strong">
					<td style="width: 50%;text-indent: 8px"> {$lang.solicitante} </td>
					<td> {$lang.duracion} </td>
					<td> {$lang.fecha} </td>
					<td> {$lang.estado} </td>
					<td> </td>
					<td> </td>
				</tr>

				{foreach from=$anexos item=anexo}
					{assign var=atributo value=$anexo->obtenerDocumentoAtributo()}
					{assign var=solicitante value=$atributo->getElement()}
					{assign var=infodoc value=$anexo->getInfo()}
					{assign var=solicitud value=$anexo->getSolicitud()}
					<tr class="selected-row">
						<td style="text-indent: 8px">{$solicitante->getHTMLDocumentName()}</td>
						<td>
							{if ($infodoc.duracion==0)}
								{$lang.no_caduca}
							{else}
								{$infodoc.duracion} ({$lang.dias})
							{/if}
						</td>
						<td>	
							{'d-m-Y'|date:$anexo->getRealTimestamp($timezone)}
						</td>
						<td>
							{$solicitud->getHTMLStatus()}
						</td>
						<td style="text-align: right">
							<input type="checkbox" class="line-check" checked="true" name="selected[]" value="{$anexo->getUID()}"/>
						</td>
						<td> </td>
					</tr>
				{/foreach}

			</table>

		</div>
	</div>

	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="o" value="{$smarty.get.o}" />
	<input type="hidden" name="elemento-tipo" value="{$elemento->getType()}" />
	<input type="hidden" name="elemento-nombre" value="{$elemento->getUserVisibleName()}" />
	<input type="hidden" name="documento-nombre" value="{$documento->getUserVisibleName()}" />
	<input type="hidden" name="send" value="1" />
	
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/report_delete.png" /> {$lang.opt_borrar} </span></span></button>
	</div>
</form>
