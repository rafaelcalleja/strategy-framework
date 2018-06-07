<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.denied_assignment} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br><br>
		{assign var=item value=$request->getItem()}
		{assign var=destino value=$request->getCompany()}
		{$lang.denied_assignment_message|sprintf:$destino->getUserVisibleName():$item->getUserVisibleName()}
	</p>
	<p>
		<br />
		{if $list && count($list)}
			{$lang.lista_agrupadores_rechazados}
			<ul style="padding:0 1em">
				{foreach from=$list item=agrupador}
					<li>{$agrupador->getUserVisibleName()} - {$agrupador->getTypeString()}</li>
				{/foreach}			
			</ul>
		{/if}
	</p>
	<p>
		{if $motivo}
			<br />
			{$lang.comentariosEmpresa|sprintf:$destino->getUserVisibleName()}
			<br><br>
			{$motivo|sprintf:$lang.motivo_rechazo_solicitud}
		{/if}
	</p>
	<p>
		<br>
		{$lang.alternative_reject_option}
		<br><br>
		{assign var="subject" value=$lang.set_assignment|sprintf:$item->getUserVisibleName()|urlencode}
		{assign var=url value="mailto:"|cat:$email|cat:"?subject="|cat:$subject}
		<strong>1.</strong> {$lang.answer_via_email|sprintf:$url}
		<br><br>
		{assign var=url value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#asignacion.php?m="|cat:$item->getType()|cat:"&poid="|cat:$item->getUID() }
		<strong>2.</strong> {$lang.current_assignment|sprintf:$url}
	</p>
	<p>
		<br><br>
		{$lang.email_pie_equipo}
		<br /><br />
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>