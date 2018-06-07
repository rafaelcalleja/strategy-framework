	<table class="agd-form">
			{if isset($campos) && count($campos)}

				{foreach from=$campos item=campo key=nombre}
					{assign var="display" value=$nombre}
					{if strstr($display,"[]")}
						{assign var="display" value=$display|replace:"[]":""}
					{/if}


					{if isset($campo.innerHTML)}
						{assign var="innerHTML" value=$campo.innerHTML}
						{if isset($lang.$innerHTML)}
							{assign var="innerHTML" value=$lang.$innerHTML}
						{/if}
					{elseif isset($lang.$display)}
						{assign var="innerHTML" value=$lang.$display}
					{else}
						{assign var="innerHTML" value=$display}
					{/if}

					{assign var="open" value=$campos->openLine($campo)}
					{if !$campos instanceof FieldList || ( $campos instanceof FieldList && $open )}
						<tr id="form-line-{$nombre|replace:'[]':''}" {if $open && $campo instanceof FormField && $campos->endLine($campo) && $campo->isHidden($campos)}style="display:none;"{/if}>
					{/if}
						<td class="form-colum-description" style="vertical-align:top;padding-top:5px;{if $campo.search}padding-top:0px;vertical-align: bottom;padding-bottom:0.8em;{/if} {if isset($no_wrap_description)}vertical-align:inherit;padding-top:0px;white-space:nowrap;{/if}">
							{if $campo instanceof FormField} {$campo->getInnerHTML()} {else} {$innerHTML} {/if}
							{if $campo.info && $campo instanceof FormField}
								{assign var="langIndex" value='expl_'|cat:$campo->name|replace:"[]":""}
								<img src="{$resources}/img/famfam/information.png" title="{$lang.$langIndex}" />
							{/if}
						</td>
						<td class="form-colum-separator"></td>
						<td class="form-colum-value" style="vertical-align: middle;" {if $campo.affects}data-affects="{$campo.affects}"{/if} {if $campo.parts}data-parts="{$campo.parts}"{/if} {if $campos instanceof FieldList}colspan="{$campos->getMinColSpan($campo)}"{/if}>
							{include file=$tpldir|cat:'form/form_parts.inc.tpl'}
						</td>
					{if  !$campos instanceof FieldList || ( $campos instanceof FieldList && $campos->endLine($campo) )}
					</tr>
					{/if}

					{if $campo.hr}<tr><td colspan="{if $campos instanceof FieldList}{$campos->getMinColSpan($campo)+2}{else}3{/if}"><hr /></td></tr>{/if}
				{/foreach}
			{elseif isset($array)}
				{foreach from=$array item=value key=campo}
				<tr>
					<td class="form-colum-description">{if is_numeric($campo)}{$value}{else}{$campo}{/if}</td>
					<td class="form-colum-separator"></td>
					<td style="vertical-align: middle;">
						<input type="text" name="{if is_numeric($campo)}{$value}{else}{$campo}{/if}" {if !is_numeric($campo)}value="{$value}"{/if} /> 
					</td>
				</tr>
				{/foreach}
			{/if}
		</table>
