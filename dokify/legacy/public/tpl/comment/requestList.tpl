<table width="100%">	
	{foreach from=$affectedRequirements item=solicitud key=i name=sol}
		<tr class="{if not $smarty.foreach.sol.last}border{/if}">
			<td class="requirement-name" id="solicitud-{$solicitud->getUID()}">
				{$solicitud->getUserVisibleName()}
			</td>
			<td class="requirement-checkbox">
				<input class="{if $check}solicitudes{/if} count" data-text="{$solicitud->getUserVisibleName(true)}" type="{if $check}checkbox{else}hidden{/if}" name="{if $check}selected[]{/if}"checked="true" value="{$solicitud->getUID()}" data-count-target="#apply-to-count" data-visiblename="{$solicitud->getUserVisibleName()}"/> 
			</td>
		</tr>
	{/foreach}
</table>

