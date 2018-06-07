<div style="text-align: center;">
	<strong class="ucase">{$elemento->getUserVisibleName()}</strong>
	<hr />
	<div class="cbox-content">
		<div style="text-align: left"><h3 >{$lang.seleccionar_agrupamientos_aplicables}</h3><br /></div>

		<table class="item-list">
			{foreach from=$agrupamientos item=agrupamiento}
				<tr>
					<td class="field-list">{$agrupamiento->getUserVisibleName()}</td>
					<td class="field-list">
						<input type="checkbox" class="line-check box-it" href="{$smarty.server.PHP_SELF}?action=modulo&oid={$agrupamiento->getUID()}&poid={$smarty.get.poid}" {if $agrupamiento->asignadoPara($elemento)}checked{/if} />
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>

