<div style="width: 760px">
<div class="box-title">
	{$elemento->getUserVisibleName()}

	{assign var="modulo" value=$elemento->getType()}

	{assign var="tabs" value="$modulo::fieldTabs"|call_user_func:$user}
	<div class="tabs">
		{foreach from=$tabs item=tab key=i}
			{assign var="tabname" value=$tab->name}
			{assign var="icon" value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/"|cat:$tab->icon}
			<div class="box-tab {if !$i}selected{/if}" rel="#tab-{$tabname}"><img src="{$icon}" height="16px" widht="16px" /> {$lang.$tabname|default:$tabname}</div>
		{/foreach}
	</div>
</div>
<form action="../agd/configurar/modificar.php?send=1&inline=true&m={$elemento->getType()}&poid={$elemento->getUID()}" method="{$smarty.server.REQUEST_METHOD}" onsubmit="return false;">
	<div id="tabs-content" class="cbox-content">
		{foreach from=$tabs item=tab key=i}
			{assign var="tabname" value=$tab->name}

			<div id="tab-{$tabname}" style="{if $i}display:none;{/if}">
				{assign var="campos" value=$elemento->getPublicFields(true, "edit", $user, $tab)}

				<table class="agd-form">
				{foreach from=$campos item=campo key=nombre}
					{if !$campos instanceof FieldList || ( $campos instanceof FieldList && ($open = $campos->openLine($campo)) )}
						<tr id="form-line-{$nombre}" {if $open && $campo instanceof FormField && $campos->endLine($campo) && $campo->isHidden($campos)}style="display:none;"{/if}>
					{/if}
						<td class="form-colum-description" style="{if $campo.search}vertical-align: bottom;padding-bottom:0.8em;{/if} padding: 5px 0;"> {if !$campo instanceof FormField} {if dump($campo)}{/if} {/if} {$campo->getInnerHTML()} </td>
						<td class="form-colum-separator"></td>
						<td class="form-colum-value" style="vertical-align: middle;" {if $campo.affects}data-affects="{$campo.affects}"{/if} {if $campo.parts}data-parts="{$campo.parts}"{/if} {if $campos instanceof FieldList}colspan="{$campos->getMinColSpan($campo)}"{/if}>
							{include file=$tpldir|cat:'form/form_parts_live.inc.tpl'}
						</td>
					{if  !$campos instanceof FieldList || ( $campos instanceof FieldList && $campos->endLine($campo) )}
					</tr>
					{/if}

					{if $campo.hr}<tr><td colspan="3"><hr /></td></tr>{/if}
				{/foreach}
				</table>

			</div>
		{/foreach}
	</div>
	<div class="cboxButtons">
		<a class="btn box-it" href="../agd/ficha.php?m=accidente&poid={$elemento->getUID()}"><span><span>{$lang.volver}</span></span></a>
	</div>
</form>
</div>
