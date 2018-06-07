<div class="box-title">
	{$lang.subcontrata_nueva} {$empresa->obtenerDato("nombre")}
</div>
<form name="confirmar-cliente" action="{$smarty.server.PHP_SELF}" class="form-to-box" id="confirmar-subcontrata" style="display: inline">
	<div class="cbox-content" style="width: 450px;">
		</br>
		{include file=$alertpath}
		{include file=$errorpath}
		{if !isset($alert)}
		</br>
			{$lang.mensaje_nueva_subcontrata} <strong id="nombre-empresa-existente"> {$empresa->obtenerDato("nombre")}</strong>
			<hr />
			{$lang.mensaje_confirmar_subcontrata}
		</div>
		<div class="cboxButtons">
				<div style="float:left">
					<button class="btn" name="cancel" href="/agd/enviarpapelera.php?m=empresa&poid={$empresa->getUID()}&request={$smarty.get.request}"><span><span><img src="{$resources}/img/famfam/cancel.png"> {$lang.no} </span></span></button>
				</div>

				<button class="btn"><span><span><img src="{$resources}/img/famfam/accept.png"> {$lang.si} </span></span></button>
				<input type="hidden" name="send" value="1" />
				{if isset($smarty.get.poid)}<input type="hidden" name="poid" id="poid" value="{$smarty.get.poid}" />{/if}
				{if isset($smarty.get.request)}<input type="hidden" name="request" id="request" value="{$smarty.get.request}" />{/if}
		</div>	
		{/if}
	</div>	
</form>