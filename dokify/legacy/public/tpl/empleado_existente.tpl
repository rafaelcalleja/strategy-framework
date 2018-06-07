<div style="width: 550px">
	<div class="box-title">
		{$lang.elemento_existente}
	</div>
	<form class="cbox-content">
		{assign var=id value='<strong id="nombre-empleado-existente">'|cat:$empleado->getId()|cat:'</strong>'}
		{assign var=name value='<strong id="nombre-empleado-existente">'|cat:$empleado->getUserVisibleName()|cat:'</strong>'}
		<div style="text-align:center">
			{$lang.mensaje_empleado_existente|sprintf:$name:$id}

			<hr />
			{if $transferible}
				<a href="empleado/asignarexistente.php?oid={$smarty.request.oid}&amp;poid={$smarty.request.poid}&amp;send=1" class="box-it button green m">{$lang.confirmar_alta_empleado}</a>
			{else}
				{$lang.opciones_trasnferencia_empleado}: 
				<br />

				<div style="margin-top:0.5em">
					<div class="beauty-file">
						<input type="file" name="file" class="validate-file" data-link="#attach-button" data-url="/agd/empleado/altass.php?poid={$smarty.request.oid}" style="left:0;top:0.8em"/>
						<div>
							<button class="button green s" id="attach-button" onclick="return false;">{$lang.subir_alta_ss}</button> 
						</div>
					</div>

					<div style="margin:0.5em">o</div>
					<a href="empleado/asignarexistente.php?oid={$smarty.request.oid}&amp;poid={$smarty.request.poid}&amp;send=1" class="button grey s box-it">{$lang.transferencia_empleado}</a>
				</div>

			{/if}
		</div>



		{if $user->esStaff() && count($companies)}
			<hr>
			<strong>{$lang.empresas_actuales_empleado}</strong>
			<ul>
				{foreach from=$companies item=emp}
					<li><a href="{$emp->obtenerUrlFicha()}" class="box-it">{$emp->getUserVisibleName()}</a></li>
				{/foreach}
			</ul>
		{/if}
	</form>

	<div class="cboxButtons">
		<div style="float:left">
			{if $smarty.get.back}
				<a class="btn box-it" href="empleado/nuevo.php?poid={$smarty.request.poid}"><span><span><img src="{$resources}/img/famfam/arrow_left.png"> {$lang.volver_al_formulario} </span></span></a> 
			{/if}
		</div>

		<div class="clear"></div>
	</div>
</div>