<?php
	/* -----------
		ASIGNAR AGRUPADORES A OTRO AGRUPADOR
	----------- */
	include( "../../api.php");


	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();

	//--------- Se guardaran los datos de todas las empresas
	$datosListado = array();


	$agrupador = new agrupador( obtener_uid_seleccionado() );

	/** CONTROL DE ACCESO */
	if( !$usuario->accesoElemento($agrupador) ){ die("Inaccesible"); }


	echo "Ok";

?>
