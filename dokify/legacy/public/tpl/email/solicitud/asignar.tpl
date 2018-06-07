{assign var=asunto value='asunto_email_solicitud_'|cat:$type|cat:'_'|cat:$estado}
{assign var=email value='email_solicitud_'|cat:$type|cat:'_'|cat:$estado}
{assign var=url value=$smarty.const.CURRENT_DOMAIN}|cat:"/agd/#asignacion.php?m=|cat:$item->getType()|cat:"&poid="|cat:$item->getUID() }
{assign var=requrl value="&request="|cat:$solicitud->getUID()}
<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
		<h1 style="margin-top:0"> {$lang.$asunto|sprintf:$item->getUserVisibleName()} </h1>
		<div style="clear: both">
		{if $estado==0}
			{$lang.$email|sprintf:$solicitante->getUserVisibleName():$item->getUserVisibleName()}
			<ul style="padding:0 1em">
				{foreach from=$list item=item}
					<li>{$item->getUserVisibleName()} - {$item->getTypeString()}</li>
				{/foreach}			
			</ul>
			{$lang.solucionar_asignando_reemplazando}
			<br /><br />
			<a href="{$url|cat:$requrl}">{$lang.solucionar_ahora}</a>	
		{else}
			{$lang.$email|sprintf:$destino->getUserVisibleName():$item->getUserVisibleName()}
			<br /><br />
			{if $list && count($list)}
				{$lang.lista_agrupadores_sugeridos}:
				<ul style="padding:0 1em">
					{foreach from=$list item=agrupador}
						<li>{$agrupador->getUserVisibleName()} - {$agrupador->getTypeString()}</li>
					{/foreach}			
				</ul>
			{/if}
			{if $estado==2 && $motivo}<p>{$motivo|sprintf:$lang.motivo_rechazo_solicitud}</b></p>{/if}
			<br /><br />
			{if $estado==1}
				<p>{$url|sprintf:$lang.puedes_comprobarlo_aqui}</p>
			{else}
				{assign var="subject" value=$lang.ajustar_asignaciones|sprintf:$item->getUserVisibleName()|urlencode}
				<a href="mailto:{$email}?subject={$subject}">{$lang.contestar_via_mail}</a> | <a href="{$url}">{$lang.ver_asignaciones_actuales}</a>	
			{/if}
		{/if}
		<br /><br />
	</div>
	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>



	