<div id="validation-content">
	
	{foreach from=$statsValidation item=itemsValidation}
		<div class="statCont">
			<h1><u>{$itemsValidation.title}</u></h1>
			<div class="elementStat">
				{assign var="totalAmount" value="0"}
				{assign var="totalValidations" value="0"}
				{foreach from=$itemsValidation.items item=validation}
					<div class="itemValidation">
						<ul>
							{if $validation.item instanceof usuario}
								<li class="itemName"><strong>{$validation.item->getUsername()}</strong></li>
							{else}
								<li class="itemName"><strong>{$validation.item->getUserVisibleName()}</strong></li>
							{/if}
							 <li class="itemCount">Validaciones: {$validation.count|round}</li>
							 {assign var="totalValidations" value=$totalValidations+$validation.count}
							{if $validation.amount}
								{assign var="totalAmount" value=$totalAmount+$validation.amount}
								<li class="itemCount">Total: {$validation.amount|round:"3"}€</li>
							{/if}
						</ul>
					</div>
				{/foreach}
			</div>
			<div class="statAmount">Validaciones: {$totalValidations|round} {if $itemsValidation.type=="empresa"} Total:{$totalAmount|round:"2"}€ {/if}</div>
		</div>
	{/foreach}
</div>

