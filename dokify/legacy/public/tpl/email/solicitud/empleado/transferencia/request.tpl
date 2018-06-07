<div style="padding:10px 20px 0 0">
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
	<h1 style="margin-top:0"> {$lang.request_add_employee} </h1>
	<p>
		<br />
		{$lang.email_greeting}{if isset($nombreContacto)} {$nombreContacto}{/if},
		<br /><br />
		{if isset($days)} 
		{$lang.request_transfer_employee_expired|sprintf:$days} 
		<br /><br />
		{/if}
		{assign var=solicitante value=$request->getSolicitante()}
		{assign var=empleado value=$request->getEmployee()}
		{assign var=companies value=$empleado->getCompanies()}
		{$lang.request_empleado_transfer_employee_message1|sprintf:$solicitante->getUserVisibleName()} 
		<br />
		{foreach from=$companies item=company}
			<br />
			- {$company->getUserVisibleName()}
		{/foreach}
		<br /><br>
		{$lang.request_empleado_transfer_employee_message2|sprintf:$solicitante->getUserVisibleName():$solicitante->getUserVisibleName()} 
		<br />
		<div style="margin-top: 20px">
			<!-- 		{$href|string_format:$lang.transfer_employee_notification} -->
			{assign var=acHref value=$smarty.const.CURRENT_DOMAIN|cat:'/requestTransfer.php?q=ac&token='|cat:$request->getToken()}
			{assign var=dnHref value=$smarty.const.CURRENT_DOMAIN|cat:'/requestTransfer.php?q=dn&token='|cat:$request->getToken()}
			<a style="background-color: #30B545; 
					color:white;
					padding: 8px 18px;
					font: normal 500 12px Ubuntu,sans-serif;
					text-decoration: none;
					text-shadow: 0px 1px 1px rgba(50, 50, 50, 0.75);
					border-radius:2px;
					border: 1px solid #258d36;
					"
				href="{$acHref}">{$lang.aceptar}</a>
			<a style="background-color: #e74d4d; 
					color:white;
					padding: 8px 18px;
					font: normal 500 12px Ubuntu,sans-serif;
					text-decoration: none;
					text-shadow: 0px 1px 1px rgba(50, 50, 50, 0.75);
					border-radius:2px;
					margin-left: 10px;
					border: 1px solid #e74d4d;
					"
				href="{$dnHref}">{$lang.rechazar}</a>
		</div>
		<br><br>
		{assign var=hrefFaq value='https://dokify.zendesk.com/entries/23759123'}
		{$lang.request_empleado_transfer_employee_moreinfo|sprintf:$hrefFaq}
		<br><br>
		{$lang.email_pie_equipo}
	</p>
	<p>
		<a href="https://dokify.net/">{$lang.volver_inicio}</a>
	</p>

	{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}
</div>