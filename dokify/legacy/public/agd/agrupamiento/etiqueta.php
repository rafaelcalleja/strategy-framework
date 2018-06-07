<?php
	include( "../../api.php");

	$template = Plantilla::singleton();


	$module = obtener_modulo_seleccionado();

	//instanciamos al usuario seleccionado
	$objetoSeleccionado = new $module( obtener_uid_seleccionado() );


	if( isset($_REQUEST["send"]) ){
		$estado = $objetoSeleccionado->actualizarEtiquetas(@$_REQUEST["elementos-asignados"]);
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}
	}

	

	$etiquetas = $objetoSeleccionado->getCompany()->obtenerEtiquetas();
	$etiquetasAsignadas = $objetoSeleccionado->obtenerEtiquetas();
	$etiquetasDisponibles = $etiquetas->discriminar($etiquetasAsignadas);
	/*
	$IdEtiquetasAsignadas = $etiquetasAsignadas->toIntList()->getArrayCopy();
	$IdEtiquetasAsignadas = $etiquetasDisponibles = array();


	if( count($etiquetas) && is_traversable($etiquetas) ){
		foreach( $etiquetas as $etiqueta ){
			if( !in_array($etiqueta->getUID(), $IdEtiquetasAsignadas) ){
				$etiquetasDisponibles[] = $etiqueta;
			}
		}
	}
	*/


	$template->assign( "asignados" , $etiquetasAsignadas  );
	$template->assign( "disponibles" , $etiquetasDisponibles  );
	$template->display( "configurar/asignarsimple.tpl" );
?>
