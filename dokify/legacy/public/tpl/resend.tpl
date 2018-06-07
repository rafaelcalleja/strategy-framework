
{assign var="request" value=$smarty.request}
{foreach from=$request key=varname item=value}

	{if is_array($request.$varname)}
		{foreach from=$value item=seleccionado}
			<input type="hidden" name="{$varname}[]" value="{$seleccionado}" />
		{/foreach}
	{else}
		<input type="hidden" name="{$varname}" value="{$value}" />
	{/if}
{/foreach}
