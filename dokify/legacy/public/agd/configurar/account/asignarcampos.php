<?php
	require_once("../../../api.php");
	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//----- INSTANCIAMOS LA PLANTILLA
	$template = Plantilla::singleton();



	//----- COMPROBAMOS ACCESO AL MODULO
	

	//if( $usuario->esSATI() || $usuario->esAdministrador() ){
	//	$log->resultado("error acceso modulo", true); exit;
	//}


	//----- INSTANCIAMOS AL OBJETO CLIENTE
	$empresaSeleccionada = new empresa( obtener_uid_seleccionado() );

	if( isset($_REQUEST["send"]) ){
		$estado = $empresaSeleccionada->actualizarCampos();
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}
	}



	$camposAsignados = $empresaSeleccionada->obtenerCamposDinamicos();
	$camposDisponibles = elemento::discriminarObjetos(config::obtenerCamposDinamicos(), $camposAsignados);


	$template->assign( "asignados" , $camposAsignados  );
	$template->assign( "disponibles" , $camposDisponibles  );
	$template->display( "configurar/asignarsimple.tpl" );
?>
