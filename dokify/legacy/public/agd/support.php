<?php

	include '../api.php'; 


	$URL = isset($_GET["page"]) ? 'http://support.dokify.net' . $_GET["page"] : $usuario->getZendeskURL();

	$log = log::singleton();


	$log->info("empresa", "acceso faq", $usuario->getCompany()->getUserVisibleName(), "ok", true);

	$plantilla = new Plantilla();

	// THE JSON 
	$json = new jsonAGD();

	// Define the view type ( can be simple or data )
	$json->establecerTipo("simple");

	// Define the data
	$json->nuevoSelector("#main", '<div style="width:100%;overflow-x:hidden"><iframe id="support-iframe" src="'. $URL .'" style="width:105%;height:1000px; overflow-x:hidden;margin-top:-100px"></iframe></div>');

	// Navigation menú
	$json->informacionNavegacion($plantilla("preguntas_frecuentes"));

	$json->menuSeleccionado("faq");

	// JSON Display
	$json->display();