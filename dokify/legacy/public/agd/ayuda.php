<?php
	require("../api.php"); //requerimos la api

	//instanciamos la plantilla
	$template = new Plantilla();

	$num = codigollamada::genera();
	
	//mientras exista el numero va generando otros
	while (codigollamada::existe($num)) $num = codigollamada::genera();	
	
	codigollamada::register($usuario, $num);
	
	$template->assign('codigo',$num);
	$template->display("ayuda.tpl");