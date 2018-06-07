{assign var="bucle" value=1}
{if isset($array)}
	<table>
	{foreach from=$array item=item key=key }
		{if $bucle==1 && is_string($item) && trim($key)}
			<thead>
				<tr>
					{foreach from=$item item=col key=title }
						{if $title == "separator"}
							<td style='width: 10px;'>&nbsp;</td>
						{else}
							<td>{$title}</td>
						{/if}
					{/foreach}
				</tr>
			</thead>
		{/if}
		<tr>
			{foreach from=$item item=col key=title }
				{if $title == "separator"}
					<td style='width: 10px;'>&nbsp;</td>
				{else}
					<td>{$col}</td>
				{/if}
			{/foreach}
		</tr>
		{assign var="bucle" value=$bucle+1}
	{/foreach}
	</table>
{else}
	nada
{/if}

