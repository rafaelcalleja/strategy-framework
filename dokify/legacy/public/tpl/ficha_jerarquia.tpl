<div style="text-align: center;">

		<strong class="ucase">{$elemento->getUserVisibleName()}</strong>

		{if isset($datos.descripcion)}{$datos.descripcion}{/if}
		<hr />
			

		<div class="cbox-content">

			<div style="text-align: left">
				{$lang.texto_jerarquia}
				<br><br>					
			</div>

			<div style="text-align: center">
				<h3>
				{$lang.jerarquia} <input type="checkbox" href="{$smarty.server.PHP_SELF}?action=jerarquia&poid={$smarty.get.poid}" {if isset($jerarquia)&&$jerarquia}checked{/if} class="line-check box-it" />
				</h3>
				<br />
			</div>

		</div>

</div>

