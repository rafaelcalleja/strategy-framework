<?php

	include( "../api.php");


	if( !$modulo = obtener_modulo_seleccionado() ){
		die("Inaccesible");
	}

	if( !$uids = obtener_uids_seleccionados() ){
		die("Inaccesible");
	}

	$template = new Plantilla();
	$coleccion = $uids->toObjectList($modulo);
	

	
	if( $usuario->isViewFilterByLabel() ){
		$agrupadores = $usuario->obtenerAgrupadoresVisibles();
	} else{
		$agrupadores = $usuario->getCompany()->obtenerAgrupadoresVisibles("!config_al_vuelo");
	}


	
	if( isset($_REQUEST["send"]) ){
		$addOnly = ( isset($_POST["solo_asignar"]) && $_POST["solo_asignar"] ) ? true : false;

		// Podemos tirarnos la vida con esto...
		session_write_close();
		set_time_limit(0);
		ignore_user_abort(true);

		$estado = $modulo::actualizarAgrupadoresMasivamente($coleccion, @$_REQUEST["elementos-asignados"], $usuario, $addOnly);
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} elseif($estado === null) {
			$template->assign( "error" , "ningun_cambio"  );
		} else {
			$template->assign( "error" , $estado  );
		}
	}
	

	/*
	$etiquetasAsignadas = $coleccion->obtenerEtiquetas();
	if( $etiquetasAsignadas && count($etiquetasAsignadas) ){
		$etiquetas = $etiquetas->discriminar($etiquetasAsignadas);
	}
	*/
	$asignados = $coleccion->obtenerAgrupadores($usuario);
	$disponibles = $agrupadores->discriminar($asignados);

	$campos = new FieldList();
		$campos["solo_asignar"] = new FormField(array("tag" => "input", "type" => "checkbox", "className" => "iphone-checkbox", "value" => 1));

	$template->assign( "campos" , $campos );
	$template->assign( "groupby" , array("getNombreTipo") );
	$template->assign( "elemento" , "categorizable" );
	$template->assign( "selected" , obtener_uids_seleccionados() );
	$template->assign( "asignados" , $asignados  );
	$template->assign( "disponibles" , $disponibles  );
	$template->display( "configurar/asignarsimple.tpl" );


?>

