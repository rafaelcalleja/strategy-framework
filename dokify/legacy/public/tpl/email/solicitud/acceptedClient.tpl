<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.accepted_client} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br><br>
		{assign var=empresa value=$request->getCompany()}
		{$lang.accepted_client_message|sprintf:$empresa->getUserVisibleName()} 
		{if isset($message)} 
			<br><br>
			{$lang.comentariosEmpresa|sprintf:$empresa->getUserVisibleName()}
			<br><br>
			{$message} 
		{/if}
		<br><br>
		{$lang.email_pie_equipo}
	</p>
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>