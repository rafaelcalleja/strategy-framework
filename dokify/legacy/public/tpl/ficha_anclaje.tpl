


	<div style="text-align: center;">
		
			{assign var="datos" value=$elemento->getInfo(true)}
			{assign var="modulos" value=$elemento->obtenerModulos()}
			{assign var="disponibles" value=$elemento->obtenerModulosDisponibles()}
			<strong class="ucase">{$elemento->getUserVisibleName()}</strong>

			{if isset($datos.descripcion)}{$datos.descripcion}{/if}
			<hr />
				

			<div class="cbox-content">

				<div style="text-align: left">
					{$lang.texto_anclaje}
					<br><br>					
				</div>

				<div style="text-align: center"><h3 >{$lang.anclaje}<input type="checkbox" href="{$smarty.server.PHP_SELF}?action=anclaje&poid={$smarty.get.poid}" {if isset($anclaje)&&$anclaje}checked{/if} class="line-check box-it"></h3><br /></div>

			</div>

		
	</div>

