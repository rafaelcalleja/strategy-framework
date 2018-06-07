<div class="see-more load-more-{if $offset}{$offset}{/if}">
	<a class="load" href="/agd/documentocomentario.php?m={$moduleName}&p=0&poid={$document->getUID()}&o={$element->getUID()}{if $offset}&offset={$offset}{/if}{if $req}&req={$req->getUID()}{/if}" data-target=".load-more-{if $offset}{$offset}{/if}">
		{$lang.show_more_comments}
	</a>
</div>
