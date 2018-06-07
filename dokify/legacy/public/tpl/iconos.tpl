
<div class="wall">
	{if $folder=="puestos"}
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/pencil.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/help.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/email_go.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/mouse.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/package.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/monitor.png" />
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/photo.png" />
	{/if}

	{foreach from=$iconos item=nombre key=num}
	<img class="extend-replace" src="{$smarty.const.RESOURCES_DOMAIN}/img/{$folder}/{$nombre}" />
	{/foreach}
</div>
