<div style="text-align: center;">

		<strong class="ucase">{$elemento->getUserVisibleName()}</strong>

		{if isset($datos.descripcion)}{$datos.descripcion}{/if}
		<hr />
			

		<div class="cbox-content">

			<div style="text-align: left">
				{$lang.texto_al_vuelo}
				<br><br>					
			</div>

			<div style="text-align: center">
				<h3>
				{$lang.al_vuelo} <input type="checkbox" href="{$smarty.server.PHP_SELF}?action=al_vuelo&poid={$smarty.get.poid}" {if isset($al_vuelo)&&$al_vuelo}checked{/if} class="line-check box-it" />
				</h3>
				<br />
			</div>

		</div>

</div>

