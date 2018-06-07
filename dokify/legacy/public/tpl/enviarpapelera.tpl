<div class="box-title">
	{$lang.enviar_papelera}
</div>
<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="elemento-form-papelera">
	<div>
		<div class="cbox-content" style="width: 450px;">
			{if isset($textoextra) }
				{$textoextra}
			{ else }
				{$lang.confirmar_enviar_papelera}
			{/if}
		</div>
	</div>
	<input type="hidden" name="oid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />
	<input type="hidden" name="send" value="1" />
	<div class="cboxButtons">
		<button class="btn" type="submit"><span><span><img src="{$resources}/img/famfam/accept.png"> {$lang.confirmar} </span></span></button> 
	</div>
</form>