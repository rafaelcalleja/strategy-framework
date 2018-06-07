<div style="text-align: center;">
	<strong class="ucase">{$elemento->getUserVisibleName()}</strong>
	<hr />
	<div class="cbox-content">
		{*<div style="text-align: left"><h3 >Selecciona los módulos para que en las asignaciones masivas se asigne este Agrupamiento</h3><br /></div>*}

		<table class="item-list">

			{foreach from=$replicables item=modulo}
				<tr>
					<td class="field-list">{$lang.$modulo}</td>
					<td class="field-list">

						{assign var="opcion" value="replica_"|cat:$modulo}							
				
						<input type="checkbox" class="line-check box-it" href="{$smarty.server.PHP_SELF}?action=replicar&m={$modulo|lower}&poid={$elemento->getUID()}"
							{if ($elemento->configValue($opcion))}
								checked
							{/if}
						/>

					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>
