<div>
	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float:right;" alt="logo-dokify" />
	<h1 style="margin:0;padding:17px 0;">{$lang.$title} - {$company->getUserVisibleName()}</h1>
	<div style="clear: both">
		<hr/>
	</div>
		{assign var="paymentInfo" value='invoice::PAYMENT_INFO'|constant}
		{assign var="reminderPayment" value='invoice::REMINDER_PAYMENT'|constant}
		{assign var="closeNotifiction" value='invoice::CLOSE_NOTIFICATION'|constant}
			
			{$lang.email_greeting} {$nombreContacto},<br><br>

		{if $action == $reminderPayment}
			{if $invoice->getDaysToClose() > 0}
				{$lang.reminder_payment|sprintf:$invoice->getDaysToClose()}<br><br><br>
			{else}
				{$lang.payment_pending}<br><br>
			{/if}
		{elseif $action == $closeNotifiction }
			{$lang.close_notification}<br><br><br>
		{else}
			{$lang.payment_pending}<br><br><br>
		{/if}

		{$lang.payment_pending_invoice_expl}<br><br>

		{include file=$tpldir|cat:'invoice/invoiceTable.tpl'}


	<div style="height:60px;margin-top:20px;float:right;min-width:125px">
		<div style="float:left"><a href="{$invoice->urlToPaypal(null, $company, false)}">{$lang.pagar_ahora}</a></div>
		{if $force}
			<div style="float:left; margin-left:50px"><a href="{$invoice->urlToPaypal(null, $company, true)}">Pagar con Sanbox (testing)</a></div>
		{/if}
	</div>
</div>

{if $firstDate && $lastDate}
	{$lang.time_between_validations|sprintf:$firstDate:$lastDate}<br><br>
{/if}

{if $company->isEnterprise()}
	Nombre empresa: {$company->getUserVisibleName()} <br>
	UID empresa: {$company->getUID()}
{/if}
<br /><br />{$lang.email_pie_equipo}<br /><br />
	

{include file=$smarty.const.DIR_ROOT|cat:'/tpl/email/pie.tpl'}