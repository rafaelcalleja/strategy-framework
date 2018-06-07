<div style="margin:10px 0 0 0; font-size:10px; color:#666;">
	<div style="border-top: 1px solid #CCC; padding: 10px 0 0;">
		{if isset($unsubscribe)}{$lang.email_aviso_eliminar_subscripcion|sprintf:$unsubscribe}<br>{/if}
		{$smarty.const.CURRENT_DOMAIN|string_format:$lang.email_pie}
	</div>
</div>
