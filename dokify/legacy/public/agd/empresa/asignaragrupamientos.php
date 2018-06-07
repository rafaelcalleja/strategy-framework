<?php
	require_once("../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//----- INSTANCIAMOS LA PLANTILLA
	$template = Plantilla::singleton();

	//----- COMPROBAMOS ACCESO AL MODULO

	if( ( $uid = obtener_uid_seleccionado() ) && $usuario->accesoAccionConcreta("empresa","35") ){
		$empresaActiva = new empresa($uid);	
	} else {
		die("Inaccesible");
	}

	if (isset($_REQUEST["send"]) ){
		$availables = new ArrayObjectList;
		$assigned 	= new ArrayObjectList;
		if (isset($_REQUEST["elementos-disponibles"])) {
			$uidList 		= new ArrayIntList($_REQUEST["elementos-disponibles"]);
			$availables 	= $uidList->toObjectList('agrupamiento');
		}

		if (isset($_REQUEST["elementos-asignados"])) {
			$uidList 		= new ArrayIntList($_REQUEST["elementos-asignados"]);
			$assigned 		= $uidList->toObjectList('agrupamiento');
		}


		$estado = $empresaActiva->actualizarAgrupamientos($availables, $assigned, $usuario);

		// Remove groups from element 
		foreach ($availables as $available) {
			$groups = $available->obtenerAgrupadores();
			if (count($groups) && $empresaActiva->quitarAgrupadores($groups->toIntList()->getArrayCopy(), $usuario) == false) {
				$estado = "error_texto";
			}
		}

		if( $estado === true ){
			$empresaActiva->writeLogUI(logui::ACTION_ADD_ORGANIZATION, "", $usuario);
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado);
		}
	}



	$empresaUsuario = $usuario->getCompany();
	$agrupamientosDisponibles = $agrupamientosAsignados = new ArrayObjectList();
	if ($empresaUsuario->obtenerEmpresasInferiores()->contains($empresaActiva) || $empresaUsuario->compareTo($empresaActiva)){ 

		$agrupamientosAsignados =  $empresaActiva->obtenerAgrupamientosCorporacionAsignados($usuario);
		$agrupamientosTotales = array();
	
		if ($corp = $empresaActiva->perteneceCorporacion()){
			$agrupamientosTotales = $corp->obtenerAgrupamientosPropios($usuario);
		}
	
		$agrupamientosDisponibles = elemento::discriminarObjetos($agrupamientosTotales, $agrupamientosAsignados);
	}
	


	$template->assign( "asignados" , $agrupamientosAsignados  );
	$template->assign( "disponibles" , $agrupamientosDisponibles  );
	$template->display( "configurar/asignarsimple.tpl" );

?>
