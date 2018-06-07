<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.denied_transfer_employee} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br /><br />
		{$employee->getUserVisibleName()|string_format:$lang.denied_notification_message}
		<br />
		{foreach from=$messages item=message}
			<br />
			- {$message}
		{/foreach}	
		<br>
		{$lang.email_pie_equipo}
	</p>
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>