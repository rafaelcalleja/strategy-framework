{*
Variables plugin

*}

{if isset($elemento)}
	{assign var=totalSolicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user)}

	{if $selectedRequest}
		{assign var=solicitudes value=$documento->obtenerSolicitudDocumentos($elemento, $user, null, null, $selectedRequest)}
	{else}
		{assign var=solicitudes value=$totalSolicitudes}
	{/if}
{/if}
<div class="box-title">
	{$lang.descargar_archivo}
</div>
<form name="descargar-documento" action="{$smarty.server.PHP_SELF}" target="async-frame" id="descargar-documento" enctype="multipart/form-data" method="POST" class="agd-form">
	
	<div style="width:800px">
		{if isset($error)}
			<div class="message error" style="width: 400px; text-align: left;">
				{$lang.$error}
			</div>
			<br />
		{/if}

		<div class="cbox-content">
			<h1>{$documento->getUserVisibleName()}</h1>
			{if !isset($descargable)}
				<div style="padding: 0 0 1em">
					{$lang.descargar_texto}
					{if $selectedRequest && $totalSolicitudes|count > 1}
						- <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="descargar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
					{/if}
				</div>
			{/if}
		</div>

		<table class="item-list">
			{if isset($documento)}
				{if isset($descarga) && $descarga}
					{if isset($atributo)}
						{assign var=solicitantes value=$documento->obtenerSolicitantes($user, true, false, $atributo, true)}
					{else}
						{assign var=solicitantes value=$documento->obtenerSolicitantes($user, true)}
					{/if}

					{foreach from=$solicitantes item=solicitante}
						{assign var=tipo value=$solicitante->getType()}
						<tr class="selected-row">
							<td style="text-indent: 8px">{$solicitante->getHTMLDocumentName()}</td>
							<td>
								<span class="stat stat_{$atributo->getStatus($solicitante, true)}">{$atributo->getStatus($solicitante, true, true)}</span>
							</td>
							<td style="white-space: nowrap; text-align: right">
							{if $atributo->getStatus($solicitante, true)}
								{if isset($atributo)}
									{if $user->getAvailableOptionsForModule("documento_atributo", "descargar", 1)}
										{assign var=empresa value=$user->getCompany()}
										<a href="{$smarty.server.PHP_SELF}?poid={$smarty.get.poid}&o={$empresa->getUID()}&oid={$solicitante->getUID()}&solicitante={$tipo}&m={$documento->moduloFiltro}&action=dl{if $solicitante->referencia}&ref={$solicitante->referencia->getUID()}&t={$time}{/if}" target="async-frame">{$lang.descargar}</a> <input class="line-check" type="checkbox" checked="true" name="{$solicitante->getType()}[]" value="{$solicitante->getUID()}"/>	
									{/if}
								{else}
									{if $user->getAvailableOptionsForModule($documento->elementoFiltro->getType()."_documento", "descargar")}
										<a href="{$smarty.server.PHP_SELF}?poid={$smarty.get.poid}&o={$smarty.get.o}&oid={$solicitante->getUID()}&solicitante={$tipo}&m={$documento->moduloFiltro}&action=dl{if $solicitante->referencia}&ref={$solicitante->referencia->getUID()}{/if}&t={$time}" target="async-frame">{$lang.descargar}</a> <input class="line-check" type="checkbox" checked="true" name="{$solicitante->getType()}[]" value="{$solicitante->getUID()}"/>	
									{/if}
								{/if}
							{/if}
							</td>
							<td> </td>
						</tr>
					{/foreach}
				{elseif isset($descargable)}
					<tr class="selected-row">
						<td style="text-indent: 8px">{$alias}</td>
						<td style="text-align: center; padding:10px;">
							<img src="http://dokify.local/res/img/famfam/arrow_down.png" style="vertical-align:middle">
							<span>{$lang.download_file|sprintf:$descargable}</span>
						</td>
					</tr>

				{else}	
					{foreach from=$solicitudes item=solicitud}
						{assign var=estado value=$solicitud->getStatus()}
						<tr class="selected-row">
							<td style="text-indent: 8px;">{$solicitud->getUserVisibleName()}</td>
							<td>
								{$solicitud->getHTMLStatus()}
							</td>
							<td style="white-space: nowrap; text-align: right">
							{if $estado}
								{assign var=anexo value=$solicitud->getAnexo()}
								{if isset($descarga)}
									{if $user->getAvailableOptionsForModule("documento_atributo", "descargar", 1)}
										{assign var=empresa value=$user->getCompany()}
										<a href="{$smarty.server.PHP_SELF}?poid={$smarty.get.poid}&o={$empresa->getUID()}&oid={$solicitante->getUID()}&solicitante={$tipo}&m={$documento->moduloFiltro}&action=dl{if $solicitante->referencia}&ref={$solicitante->referencia->getUID()}&t={$time}{/if}" target="async-frame">{$lang.descargar}</a> <input class="line-check" type="checkbox" checked="true" name="{$solicitante->getType()}[]" value="{$solicitante->getUID()}"/>	
									{/if}
								{else}
									{if $user->getAvailableOptionsForModule($elemento->getType()."_documento", "descargar")}
										<a href="{$smarty.server.PHP_SELF}?poid={$documento->getUID()}&o={$elemento->getUID()}&oid={$anexo->getUID()}&m={$elemento->getModuleName()}&action=dl&t={$time}" target="async-frame">{$lang.descargar}</a> <input class="line-check" type="checkbox" checked="true" name="selected[]" value="{$anexo->getUID()}"/>	
									{/if}
								{/if}
							{else}
								<input type="checkbox" disabled />
							{/if}
							</td>
							<td> </td>
						</tr>
					{/foreach}
				{/if}
			{/if}
		</table>
	</div>


	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="o" value="{$smarty.get.o}" />
	{if isset($smarty.get.oid)}<input type="hidden" name="oid" value="{$smarty.get.oid}" />{/if}
	{if isset($smarty.get.ref)}<input type="hidden" name="ref" value="{$smarty.get.ref}" />{/if}
	{if isset($elemento)}<input type="hidden" name="m" value="{$elemento->getType()}" />{/if}
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">

		{if (!isset($descarga) || !$descarga) && !isset($descargable)}	
			<button class="btn" type="submit"><span><span> {$lang.descargar_zip} </span></span></button> 
		{/if}

	</div>
</form>
