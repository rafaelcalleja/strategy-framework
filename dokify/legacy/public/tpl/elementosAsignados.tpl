<div>
	{if count(elements) > 5}
		<div class="list-header">
			{$lang.$module}s
			<input type="text" class="find-html" search="{$lang.buscando_elemento_s}" target="#searchEmployee-{$uniqid}" rel="li" placeholder="{$lang.buscar}" />
		</div>
	{/if}

	<ul id="searchEmployee-{$uniqid}">
		{if count($elements)}
			{foreach from=$elements item=element key=i}
				<li>
					<span>
						{$element->getUserVisibleName()}
					</span>
					<span style="float:right">
						{$element->getId()}
					</span>
				</li>
			{/foreach}
		{else}
			<div class="padded">
				<strong>{$lang.no_resultados}</strong>
			</div>
		{/if}
	</ul>
</div>