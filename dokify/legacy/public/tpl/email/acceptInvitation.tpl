<div style="padding:10px 20px 0 0">
	<img src="{$elemento_logo}" style="float: right" alt="logo-dokify" height="59" />
	<h1 style="margin-top:0"> <b>{$lang.invitacion_aceptada}</b> </h1>
	<div style="clear: both">
	</div>

		{$lang.email_greeting},<br><br>

		{$lang.email_signin_response_text|sprintf:$company->getUserVisibleName()}<br /><br />
		{assign var="urlCompanyProfile" value=$smarty.const.CURRENT_DOMAIN|cat:"/agd/#buscar.php?q=tipo:empresa uid:"|cat:$company->getUID()|cat:"&src=qr"}

		{$lang.email_signin_response_link|sprintf:$urlCompanyProfile}<br /><br />

		{$lang.email_pie_equipo}<br><br>
		{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
