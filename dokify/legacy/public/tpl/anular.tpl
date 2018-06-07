	{*
Descripcion
	Plantilla para su uso en modalbox, incluye referencias a error, succes e info
	Es siminar a <a href='?tpl=anexar'>anexar</a> pero para anular documentacion
	El objeto tipo documento se encarga de buscar los solicitantes y mostrarlos por pantalla

En uso actualmente
	-	/agd/anular.php

Variables
	· $elemento - Objeto tipo x al cual se anulara el documento
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
	{$lang.anular_archivo}
</div>
<form name="anular-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box agd-form" id="anular-documento">
	<div>
		{if isset($error)}
			<div class="message error" style="width: 400px; text-align: left;">
				{$lang.$error|default:$lang.error_anular}
			</div>
		{/if}
		<div style="width: 750px">
			<div  class="cbox-content">
				<h1>{$documento->getUserVisibleName()}</h1>
			</div>

			{if count($anexos)}
				<div  class="cbox-content">
					<div style="padding: 0 0 1em">
						{$lang.anular_texto}
						{if $selectedRequest && $total > 1}
							- <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="validar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}&validate=anular" class="box-it">{$lang.ver_todas}</a>
						{/if}
					</div>
				</div>
				
				<table class="item-list" style="table-layout: auto;">
					<tr class="strong">
						<td style="width: 50%; text-indent: 8px"> {$lang.solicitante} </td>
						<td> {$lang.duracion} </td>
						<td> {$lang.fecha} </td>
						<td> {$lang.estado} </td>
						<td> </td>
						<td> </td>
					</tr>

					{assign var=empresaUsuario value=$user->getCompany()}
					{foreach from=$anexos item=anexo}
						{assign var=atributo value=$anexo->obtenerDocumentoAtributo()}
						{assign var=empresa value=$atributo->getCompany()}
						{assign var=solicitante value=$atributo->getElement()}
						{assign var=infodoc value=$anexo->getInfo()}
						{assign var=solicitud value=$anexo->getSolicitud()}
						{assign var=canDownload value=$user->getAvailableOptionsForModule($elemento->getType()."_documento", "descargar")}
						<tr class="selected-row">
							<td style="text-indent: 8px">
								<div>
									{if $canDownload}
										<a href="descargar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getType()}&o={$elemento->getUID()}&action=dl&t={$time}" title="{$lang.descargar} {$anexo->getRequestString()}" target="async-frame"> {$anexo->getRequestString()|truncate:50:'...'} </a> 
									{else}
										<span title="{$anexo->getRequestString()}" style="cursor: help">{$anexo->getRequestString()|truncate:50:'...'}</span>
									{/if}
								</div>
							</td>
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
								{if $user->canValidateFor($empresa)}
								<input type="checkbox" class="line-check" name="selected[]" value="{$anexo->getUID()}" {if $infodoc.estado==4 && !$anexo->getReverseStatus()}disabled{else}checked="true"{/if}/>
								{else}
								<img src="{$resources}/img/famfam/lock_delete.png" title="{$lang.anular_prohibido}" alt="" />
								{/if}
							</td>
							<td> </td>
						</tr>
					{/foreach}
				</table>

				<div  class="cbox-content">
					<br />
					<div style="text-align: right">
						<textarea id="textarea-comment" name="comentario" style="height: 80px" onfocus="this.value='';this.onfocus=null;" {if $user->esStaff()}class="autocomplete-input" href="t=comentario_anulacion&f=comentario" rel="comentario"{/if} placeholder="Indica el motivo de la anulación"></textarea>
					</div> 
				</div>
			{else}
				<div class="error cbox-content">
					No hay anexos que se puedan eliminar
				</div>
			{/if}
		</div>
	</div>

	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="oid" value="{$smarty.get.oid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="o" value="{$smarty.get.o}" />
	<input type="hidden" name="elemento-tipo" value="{$elemento->getType()}" />
	<input type="hidden" name="elemento-nombre" value="{$elemento->getUserVisibleName()}" />
	<input type="hidden" name="documento-nombre" value="{$documento->getUserVisibleName()}" />
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="validate" value="anular" />
	
	<div class="cboxButtons">
		{if $action=reset($user->getAvailableOptionsForModule($documento, "anular"))}
			<button class="btn send" type="submit" data-alert="{$lang.cannot_be_empty_comment}" data-must="#textarea-comment"><span><span> <img src="{$action.icono}" /> {$lang.anular} </span></span></button>
		{/if}
		{if $action=reset($user->getAvailableOptionsForModule($documento, "validar"))}
			<button class="btn box-it" href="validar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.validar} </span></span></button>
		{/if}
		{if $action=reset($user->getAvailableOptionsForModule($documento, "revisar"))}
			<button class="btn box-it" href="revisar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.revisar} </span></span></button>
		{/if}
	</div>
</form>
