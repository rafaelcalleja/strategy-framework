<div>
	<img src="{$elemento_logo}" style="float:right;" alt="logo-dokify" height="59" />
	<h2 style="margin:0;padding:17px 0;">
		{if !isset($expired)}
			{$lang.title_email_license_expire_today}
		{elseif $expired}
			{$lang.title_email_license_expired|sprintf:$daysExpire}
		{elseif !$expired}
			{$lang.title_email_license_about_to_expire|sprintf:$daysExpire}
		{/if}
	</h2>
	<div style="clear: both">
		<hr/>
	</div>

		{$lang.email_greeting} {$contactName},<br><br>

		{if !isset($expired)}
			{assign var="dateExp" value=$dateExpire|date_format:"%d.%m.%Y"}
			{$lang.text_email_license_expire_today|sprintf:$dateExp:$smarty.const.CURRENT_DOMAIN}
		{elseif $expired}
			{$lang.text_email_license_expired|sprintf:$daysExpire:$smarty.const.CURRENT_DOMAIN}
		{elseif !$expired}
			{assign var="dateExp" value=$dateExpire|date_format:"%d.%m.%Y"}
			{$lang.text_email_license_about_to_expire|sprintf:$daysExpire:$smarty.const.CURRENT_DOMAIN:$dateExp}
		{/if}



	<br /><br />{$lang.email_pie_equipo}<br /><br />
	
	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>