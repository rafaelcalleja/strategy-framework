<div style="text-align: center;">

		<strong class="ucase">{$elemento->getUserVisibleName()}</strong>

		{if isset($datos.descripcion)}{$datos.descripcion}{/if}
		<hr />
			

		<div class="cbox-content">
			<div style="text-align: center">
				<h3>
				{$lang.texto_concepto_pago} <input type="checkbox" href="{$smarty.server.PHP_SELF}?action=pago&poid={$smarty.get.poid}" {if isset($pago)&&$pago}checked{/if} class="line-check box-it" />
				</h3>
				<br />
			</div>
		</div>
</div>

