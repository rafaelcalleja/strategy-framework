{if $selectedRequest}
	{assign var=anexos value=$documento->obtenerAnexos($elemento, $user, $selectedRequest)}

	{assign var=total value=$documento->obtenerAnexos($elemento, $user)}
	{assign var=total value=$total|count}
{else}
	{assign var=anexos value=$documento->obtenerAnexos($elemento, $user)}
	{assign var=total value=$anexos|count}
{/if}
<div class="box-title">
	{$lang.revisar_archivo}
</div>
<form name="revisar-documento" action="{$smarty.server.PHP_SELF}" class="form-to-box agd-form" id="revisar-documento">
	<div>
		{if isset($error)}
			<div class="message error" style="width: 400px; text-align: left;">
				{$lang.$error}
			</div>
		{/if}
		<div style="width: 750px">

			<div class="cbox-content">
				<h1>{$documento->getUserVisibleName()}</h1>

				<div style="padding: 0 0 1em">
					{$lang.revisar_texto}
					{if $selectedRequest && $total > 1}
						- <span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="revisar.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}" class="box-it">{$lang.ver_todas}</a>
					{/if}
				</div>
			</div>

			<table class="item-list">

				<tr class="strong">
					<td style="width: 50%;text-indent: 8px"> {$lang.solicitante} </td>
					<td> {$lang.duracion} </td>
					<td> {$lang.expedicion} </td>
					<td> {$lang.estado} </td>
					<td> </td>
					<td> </td>
				</tr>

				{assign var=empresaUsuario value=$user->getCompany()}							
				{foreach from=$anexos item=anexo}
					{assign var=revisado value=$anexo->yaRevisado($user)}
					{if (!$revisado)}
						{assign var=atributo value=$anexo->obtenerDocumentoAtributo()}
						{assign var=empresa value=$atributo->getCompany()}
						{assign var=solicitante value=$atributo->getElement()}
						{assign var=infodoc value=$anexo->getInfo()}
						{assign var=solicitud value=$anexo->getSolicitud()}
						
						<tr class="selected-row">
							<td style="text-indent: 8px">
								<div title="{$anexo->getRequestString()|strip_tags}">
									{$anexo->getRequestString(true)|truncate:50}
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
								{'d-m-Y'|date:$infodoc.fecha_emision}
							</td>
							<td>
								{$solicitud->getHTMLStatus()}
							</td>
							<td style="text-align: right">
								{if $user->canValidateFor($empresa)}
								<input type="checkbox" class="line-check" checked name="selected[]" value="{$anexo->getUID()}"/>
								{else}
								<img src="{$resources}/img/famfam/lock_delete.png" title="{$lang.revisar_prohibido}" alt="" />
								{/if}
							</td>
							<td> </td>
						</tr>
					{/if}
				{/foreach}
			</table>
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
	
	<div class="cboxButtons">
		{if $action=reset($user->getAvailableOptionsForModule($documento, "anular"))}
			<button class="btn box-it" href="validar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}&&validate=anular"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.anular} </span></span></button>
		{/if}
		{if $action=reset($user->getAvailableOptionsForModule($documento, "validar"))}
			<button class="btn box-it" href="validar.php?poid={$smarty.get.poid}&m={$smarty.get.m}&o={$smarty.get.o}"><span><span> <img src="{$action.icono}" /> {$lang.ir_a} {$lang.validar} </span></span></button>
		{/if}
		{if $action=reset($user->getAvailableOptionsForModule($documento, "revisar"))}
			<button class="btn" type="submit"><span><span> <img src="{$action.icono}" /> {$lang.revisar} </span></span></button>
		{/if}
	</div>
</form>
