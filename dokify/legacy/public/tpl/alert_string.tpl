{if isset($alert) && strlen($alert) }
	<div class="message alert" style="{if $ie}width: 500px;{/if}">
	{if isset($lang.$alert)}
		{$lang.$alert}
	{else}
		{$alert}
	{/if}
	</div>
{/if}


{if isset($smarty.request.message) && strlen($smarty.request.message)}
	{assign var=msg value=$smarty.request.message}
	<br />
	<div class="message alert" style="{if $ie}width: 500px;{/if}">
	{if isset($lang.$msg)}
		{$lang.$msg}
	{else}
		{$msg}
	{/if}
	</div>
{/if}