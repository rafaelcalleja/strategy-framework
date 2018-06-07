<div class="box-title">
	{$lang.transfer_pending_title}
</div>
<form name="elemento-form-papelera" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="elemento-form-papelera">
	<div>
		<div style="text-align: center">
			{include file=$errorpath}
		</div>
		<div class="cbox-content" style="width: 500px;">
			{include file=$alertpath}
			{$mensaje}
		</div>
	</div>

	<input type="hidden" name="poid" value="{$smarty.get.poid}" />
	<input type="hidden" name="m" value="{$smarty.get.m}" />

	{if $allow_accept}
		<div class="cboxButtons">
			<button class="btn detect-click" name="action" value="accept"><span><span><img src="{$resources}/img/famfam/cancel.png"> {$lang.transfer_pending_accept} </span></span></button>
		</div>
	{else}
		<br />
	{/if}
</form>