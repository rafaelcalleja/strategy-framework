<div style="padding:10px 20px 0 0">
		<img src="{$elemento_logo}" style="float: right" alt="logo-dokify" height="59"  />
	<h1 style="margin-top:0"> <b>{if $title}{$lang.$title}{else}{$lang.email_bienvenida_titulo}{/if}</b> </h1>
	<div style="clear: both">
	</div>

		{$lang.email_greeting},<br><br>

		{if (isset($expired))}
			{if $expired === true}
				{$lang.expiring_one_invitation|sprintf:$invitationDate:$name:$lang.cif:$cif}<br>
				{$lang.expl_how_to_reinvite_a_company} {$lang.continunation_expl_reinvite_company}
				<br><br>
			{elseif is_numeric($expired)}
				{$lang.expiring_several_invitation|sprintf:$expired}<br>
				{$lang.expl_how_to_reinvite_several_company} {$lang.continunation_expl_reinvite_company}
				<br><br>
			{/if}

			<a href="{$smarty.const.CURRENT_DOMAIN}/agd/#empresa/listado.php?comefrom=invitacion">{$lang.go_to_my_invitations}</a><br /><br />
		{else}
			{if isset($days)}
				{$lang.pending_invitation_days|sprintf:$days:$company->getUserVisibleName()}<br>
				{$lang.email_signin_welcome_text|sprintf:""}
			{else}
				{$lang.email_signin_welcome_text|sprintf:$company->getUserVisibleName()}
			{/if}
			<a href="{$smarty.const.CURRENT_DOMAIN}/agd/empresa/new.php?token={$token}">{$lang.signup}</a><br /><br />

			{$lang.email_link_how_to_sign_up_company}<br><br>

			{if isset($deadline)}
				{$lang.client_deadline|sprintf:$company->getUserVisibleName():$deadline}<br><br>
			{/if}
		{/if}

		{$lang.email_pie_equipo}<br><br>
		{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>
