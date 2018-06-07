{if isset($title) && isset($lang.$title)}
<div class="box-title" style="{if $ie}width: 500px;{/if}">
	{$lang.$title}
</div>
{/if}
<div class="box-margin">
{if isset($succes) && strlen($succes) }
	<div class="message succes {if isset($successClass)} {$successClass} {/if}" id="reloader" style="{if $ie}width: 500px;{/if}">
	{if isset($lang.$succes)}
		{$lang.$succes}
	{else}
		{$succes}
	{/if}
	</div>
{/if}
</div>
