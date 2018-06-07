<?php
	include("../../../api.php");
	

	// carpeta activa en la que vamos a crear el fichero...
	$carpetaActiva = new carpeta( obtener_uid_seleccionado() );
	
	// template
	$template = Plantilla::singleton();

	// Si se envia el formulario...
	if( isset($_REQUEST["send"]) ){

		$fichero = new fichero( $_REQUEST, $usuario );
		if( $fichero instanceof fichero && $fichero->getUID() && $fichero->guardarEn( $carpetaActiva ) ){
			$template->assign("acciones", array( array("href" => $_SERVER["PHP_SELF"] . "?poid=". $carpetaActiva->getUID(), "string" => "insertar_otro") ) );	
			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", "error_texto");
		}
	}

	$template->assign ("campos", fichero::publicFields( elemento::PUBLIFIELDS_MODE_NEW ) );
	$template->display("form.tpl");
?>
