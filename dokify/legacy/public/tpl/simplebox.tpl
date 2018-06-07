{if isset($title)}
<div class="box-title">
	{if isset($lang.$title)}
		{$lang.$title}
	{else}
		{$title}
	{/if}
</div>
{/if}
<div class="cbox-content">
	<div style="margin-top:10px;">
		{if isset($html) && strlen($html) }
			{if isset($lang.$html)}
				{$lang.$html}
			{else}
				{$html}
			{/if}
		{/if}
	</div>
</div>
<div class="cboxButtons"></div>
{if isset($frameto)}{literal}
	<script>
		var href = "{/literal}{$frameto}{literal}";
		agd.elements.asyncFrame.src = href;
	</script>
{/literal}{/if}

