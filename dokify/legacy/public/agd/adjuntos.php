<?php
	include("../api.php");

	if( !($modulo = obtener_modulo_seleccionado()) || !($uid = obtener_uid_seleccionado()) ){
		die("Inaccesible");
	}
	
	

	$item = new $modulo($uid);

	if( !$usuario->accesoElemento($item) ){
		die("Inaccesible");
	}

	$template = Plantilla::singleton();
	$template->assign("elemento", $item);

	// Si se envia el archivo
	if( isset($_REQUEST["send"]) ){
		if( isset($_SESSION["FILES"]) && $files = unserialize($_SESSION["FILES"]) ){
			try {	
				if( $item->attach($files["archivo"], $_REQUEST) ){
					$template->assign("succes", "exito_texto");
					$template->display("adjuntos.tpl");
					exit;
				} else {
					$template->assign("error", "error_desconocido" );
				}
			} catch(Exception $e) {
				$template->assign("error", $e->getMessage() );
			}
		}
	}


	switch( @$_REQUEST["action"] ){
		case "download":
			$item->download();
		break;
		case "attach":
			// Campos extra..
			$inputs = array();
				$inputs[] = array("innerHTML" => "nombre", "name" => "nombre", "blank" => false );


			// Display
			$template->assign( "inputs", $inputs);
			$template->display( "anexar_descargable.tpl" );
		break;
		default:
			$template->display( "adjuntos.tpl" );
		break;
	}
?>
