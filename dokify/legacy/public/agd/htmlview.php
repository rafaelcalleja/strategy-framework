<?php
	/*MENSAJE DE INICIO DE AGD*/
	include("../auth.php");
	include("../config.php");
	//include("../func/common.php");

	//creamos la instancia de la plantilla
	$template = new Plantilla();
	//mostramos la plantilla
	$template->display( "view_" . $_REQUEST["view"] . ".tpl");

?>
