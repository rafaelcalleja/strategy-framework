{*
Descripcion
	Da un aviso de error, indicando que no se puede ejecutar por el nivel de subcontratacion

En uso actualmente
	-	/agd/empresa/nueva.php

Variables
*}
	<h1> {$lang.bloquear_asignacion} </h1> 
	<br />
	<div class="cbox-content">
		Permite bloquear esta asignación para que no se elimine bajo ningún concepto. Esta opción anulará el proceso de anclaje.
		<hr />
		{$lang.bloquear_asignacion} <input type="checkbox" name="bloquear" {if isset($bloqueado)&&$bloqueado}checked{/if} /> 
	</div>
