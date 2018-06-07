<?php
	require_once("../api.php");
	
	//----------- INSTANCIAMOS LA PLANTILLA
	$template = new Plantilla();

	//----------- RECUPERAMOS NUESTRA EMPRESA
	$empresaActiva = unserialize($_SESSION["OBJETO_EMPRESA"]);
	
	//----------- ASIGNAMOS LA EMPRESA A LA PLANTILLA
	$template->assign("empresa", $empresaActiva);

	//----------- MOSTRAR LA PLANTILLA
	$template->display("mapavisual.tpl");	
?>
