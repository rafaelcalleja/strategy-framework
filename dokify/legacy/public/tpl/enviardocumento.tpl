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
<div class="box-title">
	{$lang.enviar_documentos}
</div>
<form name="enviar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box" method="POST" id="enviar-documento">
	<div>
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
		<div class="cbox-content" style="width: 650px">
			<h1>{$documento->getUserVisibleName()}</h1>
			<table class="item-list">
				<tr>
					<td colspan="3">
						{$lang.enviar_documentos_texto}
						<br /><br />
					</td>
				</tr>
				<tr class="strong">
					<td> {$lang.solicitante} </td>
					<td> {$lang.estado} </td>
					<td> </td>
				</tr>

				{assign var=disponibles value=0}
				{foreach from=$documento->obtenerAnexos($elemento, $user) item=anexo}
					{assign var=atributo value=$anexo->obtenerDocumentoAtributo()}
					{assign var=solicitante value=$atributo->getElement()}
					{assign var=infodoc value=$anexo->getInfo()}
					{assign var=solicitud value=$anexo->getSolicitud()}

					<tr>
						<td>{$solicitante->getHTMLDocumentName()}</td>
						<td>
							{$solicitud->getHTMLStatus()}
						</td>
						<td style="text-align: right">
							<input type="checkbox" class="line-check" name="selected[]" value="{$anexo->getUID()}"/>
						</td>
					</tr>
					{assign var=disponibles value=$disponibles+1}
				{/foreach}

				{*
				{foreach from=$documento->obtenerDocumentoatributos($user) item=atributo}
					{assign var=fileinfo value=$atributo->getFileInfo($documento->elementoFiltro)}
					{assign var=info value=$atributo->getInfo()}
					{assign var=solicitante value=$atributo->getElement()}
					{assign var=estado value=$atributo->getStatus($documento->elementoFiltro)}

					{if $estado>0}
						<tr>
							<td>{$solicitante->getHTMLDocumentName()}</td>
							<td> <span class="stat stat_{$atributo->getStatus($documento->elementoFiltro)}">{$atributo->getStatus($documento->elementoFiltro,false,true)}</span> </td>
							<td style="text-align: right">
								{assign var="key" value=""}{if $solicitante->referencia}{assign var="key" value="referencia-"|cat:$solicitante->referencia->getUID()}{/if}
								<input type="checkbox" class="line-check" name="{$solicitante->getType()}[{$key}]" value="{$solicitante->getUID()}"/>
							</td>
						</tr>
						{assign var=disponibles value=$disponibles+1}
					{/if}
				{/foreach}
				*}
			</table>
			<br />

			{if $disponibles}
				<div>
					<table>
						<tr>
							<td>Enviarme una copia:</td><td><input type="checkbox" name="replyto" checked /></td>
						</tr>
						<tr>
							<td>Adjuntar el fichero:</td><td> <input type="checkbox" name="attach" checked /></td>
						</tr>
						<tr>
							<td>
								Usuario / Email destino:
							</td>
							<td>
								<input type="text" name="usuario" class="autocomplete-input" autocomplete="off" href="t=usuario&f=usuario" rel="usuario" style="width: 80%"/>
							</td>
						</tr>
					</table>
					<hr />

					<textarea style="height: 80px" onchange="this.name='comentario';this.onchange=null;" onfocus="this.value='';this.onfocus=null;">Comentario...
					</textarea>
				</div> 
			{else}
				<div class="message error">
					{$lang.sin_documentos_para_envio}
				</div>
			{/if}
		</div>
	</div>

	{if $disponibles}
	<input type="hidden" name="poid" value="{$smarty.request.poid}" />
	<input type="hidden" name="oid" value="{$smarty.request.oid}" />
	<input type="hidden" name="m" value="{$smarty.request.m}" />
	<input type="hidden" name="o" value="{$smarty.request.o}" />
	<input type="hidden" name="elemento-tipo" value="{$elemento->getType()}" />
	<input type="hidden" name="elemento-nombre" value="{$elemento->getUserVisibleName()}" />
	<input type="hidden" name="documento-nombre" value="{$documento->getUserVisibleName()}" />
	<input type="hidden" name="send" value="1" />
	{/if}

	<div class="cboxButtons">
		{if $disponibles && $action = @$user->getAvailableOptionsForModule("documento", "enviar")[0]}
			<button class="btn" type="submit"><span><span> <img src="{$action.icono}" /> {$lang.enviar} </span></span></button>
		{/if}
		{if $action=@$user->getAvailableOptionsForModule($documento->elementoFiltro->getType()."_documento", "validar")[0]}
			<button class="btn box-it" href="validar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.validar} </span></span></button>
		{/if}
	</div>
</form>
