<?php
	require_once("../../../api.php");

	$template = new Plantilla();


	$currentUIDAtributodocumento = db::scape( $_REQUEST["poid"] );
	$documento = new documento_atributo($currentUIDAtributodocumento);

	if( isset($_REQUEST["send"]) ){
		$estado = $documento->actualizarEtiquetas(@$_REQUEST["elementos-asignados"]);
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign("error", $estado);
		}
	}


	$etiquetas = $usuario->getCompany()->obtenerEtiquetas();//obtener_array_etiquetas();
	$etiquetasAsignadas = $documento->obtenerEtiquetas();

	$etiquetasDisponibles = elemento::discriminarObjetos($etiquetas,$etiquetasAsignadas);

	$template->assign( "asignados" , $etiquetasAsignadas  );
	$template->assign( "disponibles" , $etiquetasDisponibles  );
	$template->display( "configurar/asignarsimple.tpl" );
?>
