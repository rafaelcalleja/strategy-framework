
<div name="company-clients" id="company-clients" style="width: 750px">
	<div class="box-title">
		{$lang.clientes}
	</div>

	{if isset($companies) && count($companies)}
		{assign var="unsuitableItemCompanies" value=$userCompany->getUnsuitableItemClient($userCompany)}
		<div class="linetip" style="margin-bottom: 10px;">
			<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/information.png" />
			<a href="https://support.dokify.net/entries/24452838--C%C3%B3mo-puedo-consultar-el-estado-de-las-cadenas-de-contrataci%C3%B3n-que-me-unen-con-mis-clientes-" target="_blank">
				{$lang.texto_ayuda_clientes}
			</a>
		</div>

		{include file=$succespath}
		{include file=$infopath}
		{include file=$errorpath}

		<div class="cbox-content" style="margin-bottom: 10px;">
			
			{if !$globalview}
				<p class="padded message highlight">{$lang.activar_vista_global_seleccionar_clientes}</p>
				<hr />
			{/if}

			<table class="item-list" id="table-clients">
				<tbody id="company-clients-list">
					{if $currentCompanyHasDocuments}
						{assign var="isHidden" value=$hiddenCompanies->contains($userCompany)}
						{if $isHidden}
							{assign var="limiterUser" value=$user->getUserLimiter($userCompany)}
							{if $limiterUser && $limiterUser->compareTo($user)}
								{assign var="hiddenByItself" value=true}
							{/if}
						{/if}
						<tr class="separated-row light-blue">
							<td colspan="2"> </td>
							<td>
								<span class="help" title="{$lang.mi_empresa}">{$userCompany->getUserVisibleName()}</span>
							</td>
							<td style="text-align: right">
								{if !$isHidden}
									<a href="empresa/clients.php?action=hide&poid={$userCompany->getUID()}" class="box-it" style="margin-right: top;"> 
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eye.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.ocultar_mis_documentos}"/>
									</a>
								{else}
									{if isset($hiddenByItself) && $hiddenByItself}
										<a href="empresa/clients.php?action=show&poid={$userCompany->getUID()}" class="box-it" style="margin-right: top;"> 
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eyecross.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.mostrar_mis_documentos}"/>
										</a>
									{else}
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eyecross.png" style="vertical-align: top;" height="16px" width="16px" title="{if isset($limiterUser) && $limiterUser instanceof usuario}{$lang.restrict_visibility_known_user|sprintf:$limiterUser->getUsername()}{else}{$lang.restrict_visibility_unknown_user}{/if}"/>
									{/if}
									
								{/if}
							</td>
							<td>
							</td>
						</tr>
					{/if}
					{foreach from=$companies item=company}
						{assign var="isHidden" value=$hiddenCompanies->contains($company)}
						{assign var="hasAttr" value=$company->countCorpDocuments()}
						{assign var="hiddenByItself" value=false}
						{if count($empresasSuperiores)}
							{assign var="isCloseClient" value=$empresasSuperiores->contains($company)}
						{else}
							{assign var="isCloseClient" value=false}
						{/if}
						{if $isHidden}
							{assign var="limiterUser" value=$user->getUserLimiter($company)}
							{if $limiterUser && $limiterUser->compareTo($user)}
								{assign var="hiddenByItself" value=true}
							{/if}
						{/if}

						{if !$isHidden || ($isHidden && isset($hiddenByItself) && $hiddenByItself)}
							{assign var="showActions" value=true}
						{else}
							{assign var="showActions" value=false}
						{/if}
						<tr class="separated-row">
							{assign var="isCorporation" value=$company->esCorporacion()}
							<td {if $showActions}{if $hasAttr && !$isCorporation} class="toggle show-toggle" data-info="show" target="tr.sup-of-{$company->getUID()}" title="{$lang.title_chains}"{elseif !$isCorporation}class="opacity-information" title="{$lang.cliente_no_solicita}"{/if}{/if}></td>
							<td class="width-s">
								{if !$isCorporation}
									{if $hasAttr} 
										{if ($userCompany->getGlobalStatusForClient($company, $user))}
											<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" style="vertical-align: top;" title="{$lang.cadena_cliente_valida}"/>	
										{elseif ($showActions) }
											<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cancel.png" style="vertical-align: top;" title="{$lang.cadena_cliente_no_valida}"/>
										{/if}
									{/if}
								{/if}
							</td>								
							<td>
								<span>{$company->getUserVisibleName()}</span>
							</td>
														
							<td style="text-align: right"> 
								{if count($unsuitableItemCompanies) && $unsuitableItemCompanies->contains($company)}
									<img class="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bell_error.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.client_unsuitable_mycompany|sprintf:$company->getShortName()}"/>
								{/if}
								{if $company->countOwnDocuments() || $company->perteneceCorporacion()}
									{if !$isHidden}
										<a href="empresa/clients.php?action=hide&poid={$company->getUID()}" class="box-it" style="margin-right: top;"> 
											<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eye.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.title_hide_document}"/>
										</a>
									{else}
										{if isset($hiddenByItself) && $hiddenByItself}
											<a href="empresa/clients.php?action=show&poid={$company->getUID()}" class="box-it" style="margin-right: top;">	<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eyecross.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.title_show_document}"/>
											</a>
										{else}
											<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/eyecross.png" style="vertical-align: top;" height="16px" width="16px" title="{if isset($limiterUser) && $limiterUser instanceof usuario}{$lang.restrict_visibility_known_user|sprintf:$limiterUser->getUsername()}{else}{$lang.restrict_visibility_unknown_user}{/if}"/>
										{/if}
									{/if}
								{/if}
							</td>
							<td style="text-align: center" height="20px" width="30px"> 
								{if $isCloseClient && $showActions}							
									<a href="solicitudeliminarcontrata.php?poid={$company->getUID()}" class="box-it"> 
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bin.png" style="vertical-align: top;" height="16px" width="16px" title="{$lang.title_eliminar_cliente}"/>
									</a>							
								{/if}
							</td>
						</tr>
						{assign var="chains" value=$userCompany->getClientChains($company, $user)}
						
						{if $isCloseClient && !$isCorporation && $hasAttr}
							{assign var="index" value=1}
							<tr class= "sup-of-{$company->getUID()} chain-row alternate" style="display: none;">
								<td style="width: 40px">
									<img src="{$smarty.const.RESOURCES_DOMAIN}/img/link.png" style="vertical-align: top;"/>
								</td>
								{assign var=message value=$userCompany->getMessageWithCompany(null, 0, 1, $company)}
								{assign var=statusWithCompany value=$userCompany->getStatusWithCompany(null, 0, 1, $company)}
								{if $statusWithCompany == 'solicitable::STATUS_VALID_DOCUMENT'|constant}
									<td class="width-s">
										<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" style="vertical-align: top;" title="{$message}"/>
									</td>
									<td colspan="3">
										<span>
											{$company->getShortName()}<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bullet_go.png" style="vertical-align: center; margin: 5px; margin-top: 0px;" />{$userCompany->getShortName()}
										</span>
									</td>
								{else}
									<td>
										<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cancel.png" style="vertical-align: top;" title="{$message}"/>
									</td>
									<td colspan="3">
										<span>
											{$company->getShortName()}<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bullet_go.png" style="vertical-align: center; margin: 5px; margin-top: 0px;" /><font color="red">{$userCompany->getShortName()}</font>
										</span>
									</td>
								{/if}
							</tr>
						{else}
							{assign var="index" value=0}
						{/if}

						{if count($chains) && $hasAttr}
							{foreach from=$chains item=chain}
								{assign var="title" value=$chain->getMessageStatusChain()}
								{assign var="index" value=$index+1}
								<tr class= "sup-of-{$company->getUID()} chain-row {if ($index%2)} alternate {/if}" style="display: none;">
									<td>
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/link.png" style="vertical-align: top;"/>
									</td>
									{if $chain->getGlobalStatusChain($user)}
										<td>
											<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/accept.png" style="vertical-align: top;" title="{$title}"/>											
										</td>
										<td colspan="3">
											{$chain->getHtmlName()}
										</td>
									{else}
										<td>
											<img class ="help" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cancel.png" style="vertical-align: top;" title="{$title}"/>
										</td>
										<td colspan="3">
											{$chain->getHtmlName()}
										</td>
									{/if}
								</tr>	
							{/foreach}
						{/if}
					{/foreach}
				</tbody>
			</table>
		</div>
		
		<input type="hidden" name="send" value="1" />
	{else}
		<div style="text-align: center">
			<div class="message highlight" style="text-align: center;">
				{$lang.select_sin_elementos}
			</div>
		</div>

		<div class="cboxButtons"></div>
	{/if}
</div>


