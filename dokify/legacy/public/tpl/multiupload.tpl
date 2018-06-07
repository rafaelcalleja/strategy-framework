<div class="cbox-content">

	{if $others}

		<div class="center">
		<span class="red">{$lang.upload_correct_employees|sprintf:$selectedItem->getUserVisibleName()}</span>

		{if $others instanceof ArrayEmployeeList}
			<br />
			{assign var="intList" value=$others->toIntList()}
			<a href="#buscar.php?q=tipo:empleado%23{$intList}" class="unbox-it">{$lang.click_aqui_ver}</a>
		{/if}
	
		</div>
	{else}
		{if !$selected}
			<span class="red">
				{$lang.upload_correct_file}<br><br>
			</span>
		{/if}
		{$lang.take_minutes_proccess}
		<div style="margin-top: 10px">
			<div class="inline-info" style="float:left">
				{$lang.loading_for} <strong id="apply-to-count" data-init="1">{if $selected} {$items|count} {else} 1 {/if}</strong> {$lang.$module}s
			</div>
			
			<div style="float:right">
				{if !$disable}<div style="float:left; margin-top:9px"><a class="light checkall"  target="#upload-to" data-trigger="#apply-to-count">{$lang.marcar_desmarcar}</a></div>{/if}
				<div style="float:right; margin-left:10px"><input type="text" class="find-html" search="{$lang.buscando_elemento_s}" target="#upload-to" rel="li" placeholder="{$lang.buscar}" style="float:right" /></div>
			</div>
		</div>

		<div class="clear" style="margin-bottom: 0.5em;"></div>


		<li style="list-style:none; margin:0px 0px 8px 0px; background-color: #EEE; border: 1px solid #ccc;">
			<input type="checkbox" name="items[]" value="{$selectedItem->getUID()}" checked disabled style="vertical-align: middle" class="count" data-count-target="#apply-to-count"/>
			{$selectedItem->getUserVisibleName()}
			<div style="float:right;margin-right:10px;color:grey">
				<img style="width:10px; vertical-align:middle;" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user.png" />
				<span style="font-size:10px;">Empleado actual</span>
			</div>
		</li>

		<div class="scrollbox" style="height: 100px;">
			<ul class="item-list" id="upload-to">
				{foreach from=$items item=item}
					{if !$selectedItem->compareTo($item)}
						{assign var="requests" value=$documento->obtenerSolicitudDocumentos($item, $user)}
						{assign var="statuses" value=$requests->foreachCall('getStatus')}
						{assign var="statuses" value=$statuses->unique()}

						<li>
							<input type="checkbox" name="items[]" value="{$item->getUID()}" {if $disable} disabled {/if} {if $selected } checked {/if} style="vertical-align: middle" class="count" data-count-target="#apply-to-count"/>
							{$item->getUserVisibleName()}

							{foreach from=$statuses item=status}
								{assign var="string" value="css_.stat_"|cat:$status}
								<span class="stat stat_{$status}">{$lang.$string}</span>
							{/foreach}
						</li>
					{/if}
				{/foreach}
			</ul>
		</div>
	{/if}
</div>
<hr />