<?php
	include( "../../api.php");

	$template = new Plantilla();


	if( isset($_REQUEST["send"]) ){
		//$agrupamientoNuevo = agrupamiento::crearNuevo( $_REQUEST );
		$agrupamientoNuevo = new agrupamiento( $_REQUEST, $usuario);
		if( $agrupamientoNuevo instanceof agrupamiento && !$agrupamientoNuevo->error ){

			if( ($etiquetas = $usuario->obtenerEtiquetas()) && count($etiquetas) ){
				$agrupamientoNuevo->actualizarEtiquetas($etiquetas->toIntList()->getArrayCopy());
			}

			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", $agrupamientoNuevo->error);
		}
	}

	$template->assign ("titulo","titulo_nuevo_elemento");
	$template->assign ("boton","crear");
	$template->assign ("campos", agrupamiento::publicFields(elemento::PUBLIFIELDS_MODE_INIT));
	$template->display("form.tpl");
