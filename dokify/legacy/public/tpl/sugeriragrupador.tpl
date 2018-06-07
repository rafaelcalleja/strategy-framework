<div class="box-title">
	{$elemento->getUserVisibleName()}
</div>

<form name="elemeto-form-suggest" action="{$action|default:$smarty.server.PHP_SELF}" class="form-to-box" method="post" id="elemeto-form-suggest">
	<div style="text-align: center">
		{include file=$errorpath}
		{include file=$succespath}
		{include file=$infopath}
	</div>
	<div class="cbox-content" style="width: 550px">
		{if $list && count($list)}
			{if $solicitud}
				{$lang.informacion_agrupadores_sugeridos}
			{else}
				{$lang.informacion_sugerir_agrupador}
			{/if}
			<hr />
			{$lang.lista_agrupadores_sugeridos}

			<ul style="padding:0 1em">
				<br>
				{foreach from=$list item=item}
					<li>- {$item->getUserVisibleName()} ({$item->getTypeString()}) <input type="hidden" name="selected[]" value="{$item->getUID()}" /></li>
				{/foreach}			
			</ul>
			{if $empresas}
				<hr />
	
				{$lang.empresas_remitir_solicitud}
				<div class="cbox-list-content item-list" style="width: 100%;">
					<table>
					{foreach from=$empresas item=empresa}
						<tr>
							<td>
								<span><input type="checkbox" name="empresas_seleccionadas[]" class="line-check toggle" value="{$empresa->getUID()}" {if count($empresas) == 1}checked {/if}/>{$empresa->getUserVisibleName()}</span>
							</td>
						</tr>
					{/foreach}
					</table>
				</div>
			{/if}
			{if $empresasDescartadas}
				<br>
				{include file=$alertpath}
				<br>
				{$lang.empresas_descartadas_solicitud}
				<div class="cbox-list-content item-list" style="width: 100%;">
					<table>
					{foreach from=$empresasDescartadas item=empresaDescartada}
						<tr class="toggled-row">
							<td>
								<span><input type="checkbox" name="empresas_seleccionadas[]" class="line-check toggle" disabled />{$empresaDescartada->getUserVisibleName()}</span>
							</td>
						</tr>
					{/foreach}
					</table>
				</div>
			{/if}

		{else}
			{$lang.es_necesario_seleccionar_sugerir}
		{/if}
		{if $solicitud}
			{assign var=destinatario value=$solicitud->getCompany()}
			{if isset($comment) && $comment}
				<hr />
				<button class="btn toggle" target="#comentar-documento"><span><span> <img src="{$resources}/img/famfam/user_comment.png" /> {$lang.comentario}</span></span></button>
				<div style="display:none" id="comentar-documento">
					<hr />
					<div class="cbox-content">
						{$lang.comentario}...
						<br />
						<textarea name='response_message' id="anexo-comentario"></textarea>
					</div>
				</div>
			{/if}
		{/if}
	</div>

	{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}
	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($solicitud)}<input type="hidden" name="request" id="request" value="{$solicitud->getUID()}" />{/if}
	<input type="hidden" name="send" value="1" />

	<div class="cboxButtons">
		{if $list && count($list)}
			{if $solicitud}
				{assign var=destinatario value=$solicitud->getCompany()}
				{assign var=solicitante value=$solicitud->getsolicitante()}
				{assign var=empresaUsuario value=$user->getCompany()}	
				{if $destinatario->compareTo($empresaUsuario) }
					<button class="btn detect-click" name="action" value="accept"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" /> {$lang.aceptar}</span></span></button>
					<button class="btn detect-click" name="action" value="reject"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cancel.png" /> {$lang.rechazar}</span></span></button>
				{/if}
				{if $solicitante->compareTo($empresaUsuario)}
					<button class="btn detect-click"  name="action" value="delete"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_delete.png" />{$lang.descartar}</span></span></button>
					{if $solicitud->canResend() }
						<button class="btn detect-click" name="action" value="resend"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_go.png" />{$lang.reenviar}</span></span></button>
					{else}
						<button class="btn" disabled><span><span>{$lang.espera_24h_antes_enviar}</span></span></button>
					{/if}
				{/if}
			{else}
				<button class="btn detect-click" name="action" value="create"><span><span><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_go.png" />{$lang.enviar}</span></span></button>
			{/if}
		{/if}
	</div>
</form>
