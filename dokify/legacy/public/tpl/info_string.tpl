{if isset($info) && strlen($info) }
	<div class="message highlight" style="{if $ie}width: 500px;{/if}">
	{if isset($lang.$info)}
		{$lang.$info}
	{else}
		{$info}
	{/if}
	</div>
{/if}


{if isset($smarty.request.message) && strlen($smarty.request.message)}
	{assign var=msg value=$smarty.request.message}
	<br />
	<div class="message highlight" style="{if $ie}width: 500px;{/if}">
	{if isset($lang.$msg)}
		{$lang.$msg}
	{else}
		{$msg}
	{/if}
	</div>
{/if}
