<div class="box-title">
	{$lang.confirmar}
</div>
<form name="elemento-form-confirmar" action="{$smarty.server.PHP_SELF}" method="post" class="form-to-box" id="elemento-form-confirmar">
	<div>
		<div class="cbox-content" style="width: 450px;">
			{if isset($html) && isset($lang.$html)}
				{$lang.$html}			
			{elseif isset($html)}
				{$html}
			{else}
				{$lang.confirmar_accion}
			{/if}
		</div>
	</div>
	<input type="hidden" name="poid" value="{$smarty.request.poid}" />
	<input type="hidden" name="m" value="{$smarty.request.m}" />
	<input type="hidden" name="send" value="1" />
	<input type="hidden" name="confirmed" value="1">

	{if isset($hiddenInput)}
		{foreach from=$hiddenInput item=input}
			<input type="hidden" name="{$input.name}" value="{$input.value}">
		{/foreach}
	{/if}

	{if isset($smarty.request.elementos)}
		{foreach from=$smarty.request.elementos item=elemento}
			<input type="hidden" name="elementos[]" value="{$elemento}">
		{/foreach}
	{/if}
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span> <img src="{$resources}/img/famfam/tick.png"> {$lang.confirmar} </span></span></button> 
	</div>
</form>
