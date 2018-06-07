{if isset($botones)}
	{foreach from=$botones item=btn}
		{assign var="string" value=$btn.innerHTML}
		<button class="btn {if isset($btn.className)}{$btn.className}{/if}" {if isset($btn.onClick)}onclick="{$btn.onClick}"{/if} {if isset($btn.style)}style="{$btn.style}"{/if} {if isset($btn.href)}href="{$btn.href}"{/if}  {if isset($btn.type)}type="{$btn.type}"{/if}>
			<span><span>
			{if isset($btn.img)} <img src="{$btn.img}" /> {/if} {$lang.$string|default:$string}
			</span></span>
		</button>
	{/foreach}
{/if}
