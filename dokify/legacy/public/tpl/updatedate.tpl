<form action="{$smarty.server.PHP_SELF}" class="form-to-box" method="post" id="update-date-form" style="width: 600px;">
	{include file=$succespath}
	{include file=$errorpath}
	{include file=$infopath}

	<div class="cbox-content" style="text-align:center">
		{if $dateUpdated}
			<div class="message error">
				{$lang.update_date_retry_error}
			</div>
		{else}
			<div class="tip-message">
				<img class="help" src="{$resources}/img/famfam/information.png" />
				<span>{$lang.update_date_download|sprintf:$download}</span>
			</div>

			<hr />

			<div style="margin-bottom:1em">
				{$lang.informacion_fecha_documento}
			</div>



			{$lang.seleccionar_fecha_documento}
			<input type="text" class="datepicker" name="date" size="10" />
		{/if}
	</div>

	<div class="cboxButtons">
		{if !$dateUpdated}
			<button class="btn" type="submit"><span><span>
				<img src="{$resources}/img/famfam/arrow_refresh.png"> {$lang.actualizar}
			</span></span></button>
		{/if}
		
		<div style="clear:both"></div>
	</div>

	{if isset($smarty.request.m)}<input type="hidden" name="m" value="{$smarty.request.m}" />{/if}
	{if isset($smarty.request.comefrom)}<input type="hidden" name="comefrom" value="{$smarty.request.comefrom}" />{/if}
	{if isset($smarty.request.o)}<input type="hidden" name="o" value="{$smarty.request.o}" />{/if}
	{if isset($smarty.request.poid)}<input type="hidden" name="poid" value="{$smarty.request.poid}" />{/if}

	{if isset($smarty.request.selected)}
		{foreach from=$smarty.request.selected item=seleccionado}
			<input type="hidden" name="selected[]" value="{$seleccionado}" />
		{/foreach}
	{/if}

	<input type="hidden" name="send" value="1" />
</form>
