<?php
	/* DAR DE ALTA UNA NUEVO CONTACTO DE EMPRESA */
	include( "../../api.php");

	$template = Plantilla::singleton();
	$empresaSeleccionada = new empresa( obtener_uid_seleccionado(), false);

	if( isset($_REQUEST["send"]) ){
		$datosNuevoContacto = $_REQUEST;
		$datosNuevoContacto = array_merge_recursive( array("uid_empresa" => $empresaSeleccionada->getUID()), $datosNuevoContacto );
		$nuevoContacto = new contactoempresa( $datosNuevoContacto, $usuario );

		if( $nuevoContacto->exists() ){
			$template->assign("acciones", array( array("href" => "empresa/contacto.php?poid=". $empresaSeleccionada->getUID(), "string" => "volver_a_contactos") ) );	

			if( isset($_REQUEST["return"]) ){
				header("Location: ". $_REQUEST["return"] ); exit;
			}

			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", $nuevoContacto->error );
		}
	}

	$botonesExtra = array(
		array( "innerHTML" => $template->getString("volver_a_contactos"), "className" => "box-it", "href" => "empresa/contacto.php?poid=". $empresaSeleccionada->getUID() )
	);

	$template->assign ("titulo","titulo_nuevo_contacto");
	$template->assign ("boton","boton_nuevo_contacto");
	$template->assign ("botones", $botonesExtra);
	$template->assign ("campos", contactoempresa::publicFields(contactoempresa::PUBLIFIELDS_MODE_INIT) );
	$template->display("form.tpl");
	
?>
