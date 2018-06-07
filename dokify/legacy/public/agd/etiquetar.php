<?php

	include( "../api.php");

	if( !$modulo = obtener_modulo_seleccionado() ){
		die("Inaccesible");
	}

	if( !$uids = obtener_uids_seleccionados() ){
		die("Inaccesible");
	}

	$template = new Plantilla();
	

	if( $usuario->isViewFilterByLabel() ){
		$etiquetas = $usuario->obtenerEtiquetas();
	} else{
		$etiquetas = $usuario->getCompany()->obtenerEtiquetas();
	}

	$coleccion = $uids->toObjectList($modulo);
	if( is_callable("{$modulo}::referenceEtiquetable") ){
		$modulo = $modulo::referenceEtiquetable();
		$coleccion = $coleccion->transform( $modulo );
	}

	if( isset($_REQUEST["send"]) ){
		$estado = $modulo::actualizarEtiquetasMasivamente($coleccion, @$_REQUEST["elementos-asignados"] );
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} elseif($estado === null) {
			$template->assign( "error" , "ningun_cambio"  );
		} else {
			$template->assign( "error" , $estado  );
		}
	}

	

	$etiquetasAsignadas = $coleccion->obtenerEtiquetas();
	if( $etiquetasAsignadas && count($etiquetasAsignadas) ){
		$etiquetas = $etiquetas->discriminar($etiquetasAsignadas);
	}


	$template->assign( "selected" , obtener_uids_seleccionados() );
	$template->assign( "asignados" , $etiquetasAsignadas  );
	$template->assign( "disponibles" , $etiquetas  );
	$template->display( "configurar/asignarsimple.tpl" );


?>

