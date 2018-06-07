<?php
	/*MENSAJE DE INICIO DE AGD*/

	include("../auth.php");
	include("../config.php");

	//creamos la instancia de la plantilla
	$template = new Plantilla();

	$message = "Ocurrio un error";

	//definimos varibales de la plantilla
	$template->assign("message", $message );

	//mostramos la plantilla
	$template->display( "error.tpl");
?>
