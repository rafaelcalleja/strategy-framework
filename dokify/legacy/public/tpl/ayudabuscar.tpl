{*
Descripcion
	HTML simple para cargar via AJAX

En uso actualmente
	-	/agd/gettpl.php

Variables
*}	
<div>
	<div class="box-title">
		<div class="cbox-close-title" title="Cerrar"></div>
		<h2>Ayuda para buscar</h2>
	</div>
	<div class="main">
		<strong>Busquedas simples</strong>
		<div class="explicacion">
			Para buscar cualquier elemento escribe la palabra a buscar
		</div>

		<strong>Busquedas avanzadas</strong>
		<div class="explicacion">
			Introduce el campo por el cual quieres filtrar, por ej: <i>tipo</i> seguido de "<i>:</i>" y a continuación el tipo a buscar.
			Después puedes introducir texto para filtrar además como en el modo simple. 
			El resultado si queremos buscar empresas que contengan la cadena de texto <i>com</i> sería asi: "<i>tipo:empresa com</i>"
		</div>

		{*
		<strong>Formulario de búsqueda avanzada</strong>
		<div class="explicacion">
			para acceder pulse en el enlace, <a href='javascript:void(s=document.createElement("script"));void(document.body.appendChild(s));void(s.src="http://estatico.afianza.net/js/app/helpsearch.js");' title="Buscador AGD">Buscador AGD</a>
		</div>
		*}
	</div>
</div>

