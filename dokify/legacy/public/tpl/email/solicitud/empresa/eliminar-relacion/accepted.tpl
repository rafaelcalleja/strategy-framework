<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.solicitud_aceptada} </h1>
	{assign var=empresa value=$request->getSolicitante()}
	<h2 style="margin-top:0"> {$empresa->getUserVisibleName()} </h2>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br /><br />
		{assign var=destino value=$request->getCompany()}
		{$lang.accepted_delete_relationship_message|sprintf:$destino->getUserVisibleName()}
		{if isset($message)} 
			<br><br>
			{$lang.comentariosEmpresa|sprintf:$destino->getUserVisibleName()}
			<br><br>
			{$message} 
		{/if}
		<br><br>
		{$lang.email_pie_equipo}
	</p>
	<p>
		<br />
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>