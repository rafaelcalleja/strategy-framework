<?php
	include("config.php");

	
	$template = new Plantilla(); //creamos la instancia de la plantilla
	$log = log::singleton();

	if( !isset($_GET["token"]) ){
		header("Location: /sinacceso.html"); exit;
	}
	
	if ( !$publicfile = publicfile::getByToken($_GET["token"]) ) {
		header("Location: /sinacceso.html"); exit;
	}	

	$link = $publicfile->getUrl() ."&action=zip&send=1"; // para iniciar la descarga

	$img = RESOURCES_DOMAIN . "/img/dokify-google-logo.png";

	$log->info("descarga publicfile", "acceso apra descargar publicfile ", log::getIPAddress());
	$log->resultado("ok", true);

	$template->assign("style", array(
		"img" => $img
	));
	$template->assign("url", $link);
	$template->display( "publicfile.tpl" ); //mostramos la plantilla
?>