<?php
	include("../../../api.php");

	// carpeta activa en la que vamos a crear el fichero...
	$fichero = new fichero( obtener_uid_seleccionado() );

	// template
	$template = Plantilla::singleton();

	if( isset($_REQUEST["getlast"]) ){
		$versiones = $fichero->getVersions();
		archivo::descargar($versiones[ (count($versiones)-1) ]->realpath);
		exit;
	}

	// Si se envia el archivo
	if( isset($_REQUEST["send"]) ){
		$version = $fichero->getVersions( $_GET["oid"] );
		archivo::descargar($version->realpath);
		exit;
	}

	$template->assign("fichero", $fichero);
	$template->display("descargarfichero.tpl");