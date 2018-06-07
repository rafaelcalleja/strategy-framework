<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.created_assignment} </h1>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br><br>
		{assign var=item value=$request->getItem()}
		{assign var=url value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#asignacion.php?m="|cat:$item->getType()|cat:"&poid="|cat:$item->getUID() }
		{assign var=requrl value="&request="|cat:$request->getUID()}
		{$lang.created_assignment_message|sprintf:$solicitante->getUserVisibleName():$item->getUserVisibleName()}
		<ul style="padding:0 1em">
			{foreach from=$list item=item}
				<li>{$item->getUserVisibleName()} - {$item->getTypeString()}</li>
			{/foreach}			
		</ul>
		{$lang.solucionar_asignando_reemplazando|sprintf:$solicitante->getUserVisibleName()}
		<br /><br />
		<a href="{$url|cat:$requrl}">{$lang.solucionar_ahora}</a>
		<br /><br />
		{$lang.email_pie_equipo}
	</p>
	<p>
		<br /><br />
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>