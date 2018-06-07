<div style="width: 600px;">
	<div class="box-title" style="font-size: 16px;">
		{$lang.firmar} - <strong title="{$documento->getUserVisibleName()}">{$documento->getUserVisibleName()|truncate:"60"}</strong>
	</div>



	<div class="cbox-content">
		<br />
		<p class="margenize">
			{$lang.informacion_premium_firma}
			<br /><br />
			{$lang.funcionaliad_disponible_premium} <a href="/soluciones.php" target="_blank">{$lang.mas_informacion_plan}</a>
		</p>

		<br />
		<p style="text-align: center">
			<a class="btn box-it" href="anexar.php?m={$smarty.request.m}&amp;poid={$smarty.request.poid}&amp;o={$smarty.request.o}&amp;comefrom=firmar"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/attach.png" /> {$lang.anexar_sin_firma}</span></span></a>
			<a class="btn" href="/app/payment/license"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" /> {$lang.contratar_plan_premium}</span></span></a>
		</p>
	</div>

	<div class="cboxButtons">
		<div style="clear:both"></div>
	</div>


		{if isset($smarty.request.solicitante) }<input type="hidden" name="solicitante" value="{$smarty.request.solicitante}">{/if}
		{if isset($smarty.request.oid)}<input type="hidden" name="oid" value="{$smarty.request.oid}">{/if}
		{if isset($smarty.request.action)}<input type="hidden" name="action" value="{$smarty.request.action}">{/if}
		{if isset($smarty.request.referencia)}<input type="hidden" name="referencia" value="{$smarty.request.referencia}">{/if}
		{if isset($smarty.request.o)}<input type="hidden" name="o" value="{$smarty.request.o}">{/if}
		<input type="hidden" name="poid" value="{$smarty.request.poid}" />
		<input type="hidden" name="m" value="{$smarty.request.m}" />
		<input type="hidden" name="send" value="1" />
	
</div>