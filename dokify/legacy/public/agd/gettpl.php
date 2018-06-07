<?php
	require_once("../api.php");


	//usamos una plantilla
	$template = Plantilla::singleton();

	//buscamos la plantilla que se solicita
	$plantilla = db::scape($_REQUEST["tpl"]).".tpl";
	
	if( is_readable( DIR_TEMPLATES . $plantilla ) ){
		//la mostramos por pantalla
		$template->display( $plantilla );
	}
?>
