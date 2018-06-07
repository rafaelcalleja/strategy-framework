<div style="padding:10px 20px 0 0">
		<img src="{$smarty.const.RESOURCES_DOMAIN}/img/dokify-google-logo.png" style="float: right" alt="logo-dokify" />
		<h1 style="margin-top:0"> <b>Desglose validaciones</b> </h1>
	<div style="clear: both">
		<br />
	</div>

		{foreach from=$totalValidationPerPartner item=validation}

			{assign var="totalAmount" value="0"}
			{assign var="totalValidations" value="0"}

			{assign var="partner" value=$validation.partner}
			La empresa validadora <strong>{$partner->getUserVisibleName()}</strong> ({$partner->getUID()} ) ha validado:<br><br><br>

			{foreach from=$validation.items item=dataItems}

				{assign var="totalValidations" value=$totalValidations+$dataItems.count}
				{assign var="totalAmount" value=$totalAmount+$dataItems.amount}
				{assign var="company" value=$dataItems.item}
			
				Para <strong>{$company->getUserVisibleName()}</strong> se han validado <strong>{$dataItems.count|round}</strong> hacen un total de <strong>{$dataItems.amount|round:"2"}€</strong> a pagar.<br>
		
			{/foreach}

			<br><br><br>El partner va a recibir un total de <strong>{$totalAmount|round:"2"}</strong> en un total de <strong>{$totalValidations|round}</strong> validaciones<br><br>

		{/foreach}

		

</div>