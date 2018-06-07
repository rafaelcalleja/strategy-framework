<?php
	require_once("../../api.php");

	$template = new Plantilla();


	$carpeta = new carpeta( obtener_uid_seleccionado() );

	if( isset($_REQUEST["send"]) ){
		$estado = $carpeta->actualizarDocumentos();
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}
	}


	$documentos = $carpeta->obtenerDocumentosDisponibles($usuario);
	$asignados = $carpeta->obtenerDocumentos();
	$disponibles = elemento::discriminarObjetos($documentos, $asignados);

	$template->assign( "asignados" , $asignados  );
	$template->assign( "disponibles" , $disponibles  );
	$template->display( "configurar/asignarsimple.tpl" );
?>
