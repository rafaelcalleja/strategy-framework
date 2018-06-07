<div class="box-title">
	{$lang.enviar_papelera}
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
					{if $selectedRequest && $totalSolicitudes|count > 1}
						<span class="red">{$lang.mostrando_solicitud_seleccionada}.</span> <a href="documentopapelera.php?m={$elemento->getModuleName()}&poid={$documento->getUID()}&o={$elemento->getUID()}&action=send" class="box-it">{$lang.ver_todas}</a>
					{/if}
				</div>
			</div>

			<table class="item-list">

				<tr class="strong">
					<td style="width: 80%;text-indent: 8px"> {$lang.solicitante} </td>
					<td> {$lang.estado} </td>
					<td> </td>
				</tr>
						
				{foreach from=$solicitudes item=solicitud}
					{assign var=empresa value=$solicitud->obtenerCliente()}
					<tr class="selected-row">
						<td style="text-indent: 8px">
							<div title="{$solicitud->getUserVisibleName()|strip_tags}">
								{$solicitud->getUserVisibleName()|truncate:100}
							</div>
						</td>
						
						<td>
							{$solicitud->getHTMLStatus()}
						</td>
						<td>
							{if $user->canValidateFor($empresa)}
								<input type="checkbox" class="line-check" id="uid_{$solicitud->getUID()}" name="elementos[]" value="{$solicitud->getUID()}" checked />
							{else}
								<img src="{$resources}/img/famfam/lock_delete.png"  alt="" />
							{/if}
						</td>
					</tr>
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
	<input type="hidden" name="action" value="send" />
	
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span> {$lang.enviar_papelera} </span></span></button>
	</div>
</form>
