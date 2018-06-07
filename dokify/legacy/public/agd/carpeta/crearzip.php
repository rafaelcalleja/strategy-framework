<?php

	include("../../api.php");

	$template = Plantilla::singleton();
	$ids = obtener_uids_seleccionados();

	if( isset($_REQUEST["send"]) ){
		if( isset($_REQUEST["radio"]) ){
			if( isset($_REQUEST["action"]) ){
				$carpetas = array();
				foreach( $ids as $uid ){
					$carpeta = new carpeta($uid);
					$carpetas[] = $carpeta;
				}

				ini_set('memory_limit', '256M');
				carpeta::descargarZip(carpeta::filtrarNoVisibles($carpetas, $usuario));
			} else {
				$url = "http://". $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] ."&action=dl";
				switch( $_REQUEST["radio"] ){
					case "zip":
						$template->assign("title", "Descargando...");
						$template->assign("html", "Descargando archivo zip... <a class='link' href='$url' target='async-frame'>Descargar</a> ");
						$template->assign("frameto", $_SERVER["REQUEST_URI"] ."&action=dl" );
						$template->display("simplebox.tpl");
					break;
					case "url":
						$template->assign("title", "URL Descarga");
						$template->assign("html", "Puedes copiar y pegar el siguiente link: <a href='$url'>Link de descarga</a> ");
						$template->display("simplebox.tpl");
					break;
				}
			}	
		}
		exit;
	}

	$acciones = array();

	$acciones["zip"] = array(
		"innerHTML" => "Descargar ahora"
	);

	$acciones["url"] = array(
		"innerHTML" => "Crear url"
	);

	$template->assign("checked", "zip");
	$template->assign("title", "Zip");
	$template->assign("array", $acciones);
	$html = $template->getHTML("functions/array2radio.tpl");
	header("Content-type: application/json");
	print json_encode( array("cbox" => $html ) );
?>
