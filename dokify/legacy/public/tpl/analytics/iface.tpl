<div id="analytics-iface">
	<table style="width:100%;"><tr>
		<td id="analytics-menu">

			<ul>
				{assign var=actions value=$user->getOptionsFastFor("dataexport")}
				{if count($actions)}
					{foreach from=$actions item=action}
						{assign var=string value="informe_action_"|cat:$action.uid_accion}
						{assign var=params value=$action.href|parseparams}
						<li {if $action.href[0]=='#' && $params.m}name="{$params.m}"{/if}> 
							<a href="{$action.href}" class="{if $action.href[0]!='#'}box-it{/if}">{$lang.$string|default:$string}</a> 
						</li>	
					{/foreach}
					<li class="separator"></li>
				{/if}

				{if $user->esStaff()}
					{assign var=actions value=$user->getOptionsFastFor("dataimport")}
					{if count($actions)}
						{foreach from=$actions item=action}
							{assign var=string value="importacion_action_"|cat:$action.uid_accion}
							{assign var=params value=$action.href|parseparams}
							<li {if $action.href[0]=='#' && $params.m}name="{$params.m}"{/if}> 
								<a href="{$action.href}" class="{if $action.href[0]!='#'}box-it{/if}">{$lang.$string|default:$string}</a> 
							</li>	
						{/foreach}
						<li class="separator"></li>
					{/if}
				{/if}

				{assign var=actions value=$user->getOptionsFastFor("datamodel")}
				{foreach from=$actions item=action}
					{assign var=string value="datamodel_action_"|cat:$action.uid_accion}
					{assign var=params value=$action.href|parseparams}
					<li {if $action.href[0]=='#' && $params.m}name="{$params.m}"{/if}> 
						<a href="{$action.href}" class="{if $action.href[0]!='#'}box-it{/if}">{$lang.$string|default:$string}</a> 
					</li>	
				{/foreach}
				{*<li> <a href="nuevo.php?m=datamodel" class="box-it">Crear modelo</a> </li>
				<li name="datamodel"> <a href="#analytics/list.php?m=datamodel">Modelos</a> </li>*}
			</ul>
		</td>
		<td id="analytics-data">
		
		</td>
	</tr></table>
</div>
