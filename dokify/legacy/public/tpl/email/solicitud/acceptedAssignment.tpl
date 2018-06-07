<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.accepted_assignment} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br><br>
		{assign var=item value=$request->getItem()}
		{assign var=destino value=$request->getCompany()}
		{$lang.accepted_assignment_message|sprintf:$destino->getUserVisibleName():$item->getUserVisibleName()}
	</p>
	<p>
		<br />
		{if $list && count($list)}
			{$lang.lista_agrupadores_sugeridos}
			<ul style="padding:0 1em">
				{foreach from=$list item=agrupador}
					<li>{$agrupador->getUserVisibleName()} - {$agrupador->getTypeString()}</li>
				{/foreach}			
			</ul>
		{/if}
	</p>
	<p>
		{assign var=motivo value=$request->getMessage()}
		{if $motivo}
			<br />
			{$lang.comentariosEmpresa|sprintf:$destino->getUserVisibleName()}
			<br><br>
			{$motivo|sprintf:$lang.motivo_rechazo_solicitud}
		{/if}
	</p>
	<p>
		<br />
		{assign var=url value=$smarty.const.CURRENT_DOMAIN|cat:'/agd/#asignacion.php?m='|cat:$item->getType()|cat:'&poid='|cat:$item->getUID()}

		{$lang.comprobar_asignaciones_aqui|sprintf:$url}
	</p>
	<p>
		<br><br>
		{$lang.email_pie_equipo}
		<br /><br />
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>