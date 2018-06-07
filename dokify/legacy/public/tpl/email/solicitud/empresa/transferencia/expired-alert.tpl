<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.expired_alert_transfer_employee} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br /><br />
		{assign var=empleado value=$request->getItem()}
		{assign var=diasCaduca value=$request->daysToExpired()}
		{$lang.expired_alert_message|sprintf:$diasCaduca:$empleado->getUserVisibleName()} 
		<br />
		{assign var=href value=$smarty.const.CURRENT_DOMAIN|cat:'/agd/#buscar.php?p=0&q=tipo:empleado uid:'|cat:$empleado->getUID()|cat:'&req='|cat:$request->getUID()}
		{$href|string_format:$lang.transfer_employee_notification}
		<br>
		{$lang.email_pie_equipo}
	</p>
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>