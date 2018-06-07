<div id="validation-iface">
	<div id="validation-content">
		{if !isset($force)}
			<div id="validation-menu-content">
				<ul id="validation-menu">

					{assign var="urgent" value='validation::TYPE_VALIDATION_URGENT'|constant}
					{assign var="normal" value='validation::TYPE_VALIDATION_NORMAL'|constant}
					{assign var="others" value='validation::TYPE_VALIDATION_OTHERS'|constant}
					{assign var="stats" value='validation::TYPE_VALIDATION_STATS'|constant}
					{assign var="review" value='validation::TYPE_VALIDATION_REVIEW'|constant}
					{assign var="audit" value='validation::TYPE_VALIDATION_AUDIT'|constant}

					{if $pendingUrgent > 0}
					<a href="#validation.php?force=true&tab={$urgent}">
						<span class="{if $tab == $urgent}active{/if}">{$lang.urgente} ({$pendingUrgent})</span>
					</a>
					{/if}

					{if $tab == $normal}
						<span class="active" {if $filterClients || $filterReqtypes}title="{$lang.filter_enabled}"{/if}>
							{$lang.normal} ({$pendingNormal})
							{if $filterClients || $filterReqtypes}*{/if}
						</span>
					{else}
						<a href="#validation.php?tab={$normal}">
							<span class="{if $tab == $normal}active{/if}">
								{$lang.normal} ({$pendingNormal})
							</span>
						</a>
					{/if}


					{if $pendingOthers > 0}
						<a href="#validation.php?tab={$others}">
							<span class="{if $tab == $others}active{/if}">{$lang.others} ({$pendingOthers})</span>
						</a>
					{/if}

					<span style="float:right; cursor:default" title="Documentos validados hoy"><strong>{$counter}</strong></span>

					{if true === $user->isAuditor()}
						<a style="float:right" href="#validation.php?tab=audit">
							<span class="{if $tab == $audit}active{/if}" {if $filterClients || $filterReqtypes}title="{$lang.filter_enabled}"{/if}>Auditar ({$pendingAudit}) {if $filterClients || $filterReqtypes}*{/if}</span>
						</a>
					{/if}

					{if $superValidator}
						<a style="float:right" href="#validation.php?tab=review">
							<span class="{if $tab == $review}active{/if}">Revisar</span>
						</a>
						<a style="float:right" href="#validation.php?tab=stats">
							<span class="{if $tab == $stats}active{/if}">Estadísticas</span>
						</a>
					{/if}


				</ul>
			</div>
		{/if}
		<div id="validation-fileContent">
			{if $tab == $normal}
				<div class="filters">
					<a class="box-it" href="validation/clientsFilter.php">
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/filter-add-icon.png" alt="{$lang.clients_filter}" title="{$lang.clients_filter}"/>
					</a>
					<a class="box-it" href="validation/reqtypesFilter.php">
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/page_white_add.png" title="{$lang.reqtypes_filter}"/>
					</a>
				</div>
			{/if}

			{if $tab == $audit}
				<div class="filters">
					<a class="box-it" href="validation/auditClientFilter.php">
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/filter-add-icon.png" alt="{$lang.clients_filter}" title="{$lang.clients_filter}"/>
					</a>
					<a class="box-it" href="validation/auditReqtypeFilter.php">
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/page_white_add.png" title="{$lang.reqtypes_filter}"/>
					</a>
				</div>
			{/if}

			{if $tab == $stats && $superValidator}
				{include file=$smarty.const.DIR_TEMPLATES|cat:'/validation/stats.tpl'}
			{else}
				{include file=$smarty.const.DIR_TEMPLATES|cat:'/validation/main.tpl'}
			{/if}
		</div>
	</div>
</div>
