
{assign var=canDownload value=$user->getAvailableOptionsForModule($elemento->getType()."_documento", "descargar")}
{assign var=empresaUsuario value=$user->getCompany()}							
{assign var=anexos value=$documento->obtenerAnexos($elemento, $user, $selectedRequest)}
{if count($anexos)}	
	{assign var=hashs value=$anexos->getData("hash")}
	<table class="item-list" style="width: 100%; table-layout: auto;">

		{if count($anexos) > 1}
		<tr style="border: 0px;">
			<td colspan="{if count($hashs)==1}9{else}10{/if}" style="text-align: center">
				{if count($hashs)==1 && trim($hashs[0])}
					<div class="succes">{$lang.todos_anexos_iguales|default:"Todos los anexos son iguales"} </div>
				{else}
					<div class="error">{$lang.algunos_anexos_diferentes|default:"Los anexos pueden variar en cada solicitud"} </div>
				{/if}
			</td>
		</tr>
		{/if}

		<tr class="strong">
			{if count($hashs)>1}<td style="width: 4px; padding:0px;"> </td>{/if}
			<td style="text-indent: 8px"> {$lang.solicitante} </td>
			<td> {$lang.duracion} </td>
			<td> {$lang.opcional} </td>
			<td> {$lang.fecha} </td>
			<td> {$lang.caducidad} </td>
			<td> {$lang.estado} </td>
			<td> </td>
			<td> </td>
			<td> </td>

		</tr>
			
		{foreach from=$anexos item=anexo}
			{assign var=atributo value=$anexo->obtenerDocumentoAtributo()}
			{assign var=empresa value=$atributo->getCompany()}
			{assign var=solicitante value=$atributo->getElement()}
			{assign var=solicitud value=$anexo->getSolicitud()}
			{assign var=infoAttr value=$atributo->getInfo(false,"ficha")}
			{assign var=infodoc value=$anexo->getInfo(false, null, $user, true)}
			{assign var=duracion value=$atributo->obtenerDato("duracion")}
			{assign var=obligatorio value=$atributo->obtenerDato("obligatorio")}
			{assign var=name value=$atributo->obtenerDato("alias")}
			{assign var=previewFormat value=$anexo->canPreview()}

			<tr class="selected-row">
				{if count($hashs)>1}<td style="background-color:#{$anexo->getHexColor()}; padding:0px;" title="Bloques del mismo color indican que el archivo anexado es el mismo"> </td>{/if}
				<td style="text-indent: 8px; padding: 4px 2px;">
					{if $previewFormat}
						{assign var=url value="/agd/docview.php?poid="|cat:$anexo->getUID()|cat:"&m=anexo_"|cat:$elemento->getType()|cat:"&o="|cat:$elemento->getUID()}

						{assign var=url value=$url|urlencode}
						{assign var=url value="/app/viewer?format="|cat:$previewFormat|cat:"&title="|cat:$name|cat:"&file="|cat:$url}

						{if $smarty.request.url == $url}
							<img src="{$resources}/img/famfam/arrow_right.png" />
						{else}
							<a href="validar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getType()}&o={$elemento->getUID()}&url={$url|urlencode}" class="box-it" title="{$lang.previsualizar}" ><img src="{$resources}/img/famfam/zoom_in.png" /></a>
						{/if}
					{/if}
					
				
					{if $user->esValidador()}
						{if $solicitante instanceof empresa}
							{$solicitante->getUserVisibleName()}
						{else}
							{assign var=cliente value=$solicitante->getCompany()}
							{$cliente->getUserVisibleName()}
						{/if}
						 @ 

					{/if}

					{if $canDownload}
						<a href="descargar.php?poid={$documento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getType()}&o={$elemento->getUID()}&action=dl&t={$time}" title="{$lang.descargar} {$anexo->getRequestString()}" target="async-frame"> {$anexo->getRequestString()|truncate:70:'...'} </a> 
					{else}
						<span title="{$anexo->getRequestString()}" style="cursor: help">{$anexo->getRequestString()|truncate:80:'...'}</span>
					{/if}
				</td>
				<td>
					{assign var=expiration value=$anexo->getExpirationTimestamp($timezone)}
					{if $atributo->caducidadManual()}
						{$lang.manual}
					{else}
						{if (is_numeric($infoAttr.duracion) && $infoAttr.duracion===0) || (is_numeric($expiration) && $expiration == 0)}
							{$lang.no_caduca}
						{else}

							{if $infodoc.duration && !is_numeric($infodoc.duration)}
								{$lang.hasta} {$infodoc.duration}
							{else}
								{assign var=documentTime value=$anexo->getRealTimestamp($timezone)}
								{assign var=diffDuration value=$expiration-$documentTime}
								{$diffDuration/86400|ceil}
							{/if}
						{/if}
					{/if}

				</td>
				<td>	
					{if $obligatorio}{$lang.no}{else}{$lang.si}{/if}
				</td>
				<td>	
					{'d-m-Y'|date:$anexo->getRealTimestamp($timezone)}
				</td>
				<td>
					{if $infodoc.duracion === 0 && !$atributo->caducidadManual()}
						{$lang.no_caduca} 
					{elseif $expiration}
						{'d-m-Y'|date:$expiration}
					{else}
						{$lang.no_caduca} 
					{/if}
				</td>
				<td>
					{$solicitud->getHTMLStatus()}
				</td>
				<td>
					{if $anexo->yaRevisado($user)}
						<img src="{$resources}/img/famfam/thumb_up.png" title="Revisado" /> 
					{/if}
				</td>

				<td style="white-space: nowrap; text-align: right">
					{if $user->canValidateFor($empresa)}
						<input type="checkbox" class="line-check" checked="true" {if $infodoc.estado==2 && !$anexo->getReverseStatus()}disabled{else}name="selected[]" value="{$anexo->getUID()}"{/if} />
					{else}
						<img src="{$resources}/img/famfam/lock_delete.png" title="{$lang.validar_prohibido}" alt="" />
					{/if}
				</td>
				<td></td>
			</tr>
		{/foreach}
		</table>
	{else}
		<div class="cbox-content">
			{$lang.sin_documentos_anexados}
		</div>
	{/if}

