<?php
	/*MENSAJE DE INICIO DE AGD*/

	include( "../../auth.php");
	include( "../../config.php");
	include( DIR_FUNC . "common.php");
	
	$lang = Plantilla::singleton();

	$usuarioActivo = new usuario( $_SESSION["usuario.uid_usuario"] );
	$error = false;

	if( $_REQUEST["selected"] ){
		foreach( $_REQUEST["selected"] as $idSeleccionado ){
			$empresaSeleccionada = new empresa( $idSeleccionado );
			if( !$empresaSeleccionada->dejarDeSerInferiorDe( $usuarioActivo->getCompany()->getUID() ) ){
				$error = true;
			}
		}
	}
	
	if( $error ){
		die( $lang->getString("error_texto") );	
	} else {
		die( $lang->getString("exito_texto") );
	}
	
?>
