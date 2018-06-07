<?php
	/* -----------
		LISTADO DE ELEMENTOS QUE AGRUPAN (PUESTOS, PROYECTOS)
	----------- */
	include( "../../api.php");

	$agrupador = new agrupador( obtener_uid_seleccionado() );


	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las imágenes de los iconos
	$template = Plantilla::singleton();


	$agrupadoresAsignacdos = $agrupador->obtenerAgrupadores();

	
	foreach( $agrupadoresAsignacdos as $key => $agrupador )	{	
		if( $usuario->accesoElemento($agrupador) ){
		
			$agrupamiento = $agrupador->obtenerAgrupamientoPrimario(); // EXTRAEMOS SU AGRUPAMIENTO
			$campo = $agrupador->getNombreTipo();
			$valor = $agrupador->getUserVisibleName();

			$data[] = array( 
				$template->getString("agrupamiento") =>  $agrupamiento->obtenerUrlFicha($campo), 
				"" => "&raquo;", 
				$template->getString("opt_asignaciones") => $agrupador->getIcon() . " " . $agrupador->obtenerUrlFicha($valor)
			);
		}
	}

	$template->assign("titulos_columnas", true);
	$template->assign("data", $data);

	$template->display( "extended_simple.tpl" );
	
?>

