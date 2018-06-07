<div style="text-align: center;">
	<strong class="ucase">{$elemento->getUserVisibleName()}</strong>
	<hr />
	<div class="cbox-content">
		{*<div style="text-align: left"><h3 >Selecciona los módulos para los cuales esta categoria se utilizará como organizador</h3><br /></div>*}
		<table class="item-list">
			{foreach from=$organizables key=modulo item=name}
				<tr>
					<td class="field-list">{$lang.$name}</td>
					<td class="field-list">
						<input type="checkbox" class="line-check box-it" href="{$smarty.server.PHP_SELF}?action=organizador&m={$name|lower}&poid={$smarty.get.poid}"
							{if $organizador=$empresa->obtenerOrganizador($modulo.agrupamiento)}
								{if $organizador->getUID()==$elemento->getUID()}
									checked
								{/if}
							{/if}
						 />
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>

