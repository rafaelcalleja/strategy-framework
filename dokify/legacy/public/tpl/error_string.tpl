{if isset($error) && strlen($error) }
	<div class="message error">
	{if isset($lang.$error)}
		{$lang.$error}
	{else}
		{$error}
	{/if}
	</div>
{/if}
