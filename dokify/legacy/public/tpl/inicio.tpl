{assign var="modulos" value=$inicio->obtenerModulos($user)}
<div class="margenize">
	{foreach from=$modulos item=modulo }
		<div style="margin: 10px 20px;">
			<fieldset class="padded">
				<legend>{$modulo.nombre}</legend>
				Demo
			</fieldset>
	{/foreach}
</div>
