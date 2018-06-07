<?php
	require_once("../../../api.php");

	$template = Plantilla::singleton();
	$currentUIDUsuario = obtener_uid_seleccionado();

	//instanciamos al usuario seleccionado
	$usuarioSeleccionado = new usuario( $currentUIDUsuario );


	if( isset($_REQUEST["send"]) ){
		$estado = $usuarioSeleccionado->perfilActivo()->actualizarEtiquetas(@$_REQUEST["elementos-asignados"]);
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}
	}

	$userCompany =  $usuarioSeleccionado->getCompany();

	if ( $corporation = $userCompany->perteneceCorporacion()){
		$companies = new ArrayObjectList(array($userCompany,$corporation));
		$etiquetas = $companies->foreachCall("obtenerEtiquetas")->unique();
	} else {
		$etiquetas =$userCompany->obtenerEtiquetas();
	}
	
	$etiquetasAsignadas = $usuarioSeleccionado->perfilActivo()->obtenerEtiquetas();

	if( $etiquetas && $etiquetasAsignadas ){
		$etiquetasDisponibles = $etiquetas->discriminar($etiquetasAsignadas);
	} else {
		$etiquetasAsignadas = new ArrayObjectList;
		$etiquetasDisponibles = $etiquetas;
	}



	$template->assign( "asignados" , $etiquetasAsignadas  );
	$template->assign( "disponibles" , $etiquetasDisponibles  );
	$template->display( "configurar/asignarsimple.tpl" );
?>
