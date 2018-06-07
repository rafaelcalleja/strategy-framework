<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.certification_documents_validated}</h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br /><br />
		{assign var=url value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#documentos.php?m=empresa&poid="|cat:$empresa->getUID() }
		{$lang.certification_documents_validated_message|sprintf:$cliente->getUserVisibleName():$url}

		<br /><br>
		{$lang.email_pie_equipo}
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>

