<?php
	require_once("../../api.php");


	$template = Plantilla::singleton();


	//instanciamos al usuario seleccionado
	$empleado = new empleado( obtener_uid_seleccionado() );


	if( isset($_REQUEST["send"]) ){
		$estado = $empleado->actualizarMaquinas();
		/*
		$estado = $usuario->actualizarEtiquetas();
		*/
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}

	}


	$asignadas = $disponibles = new ArrayObjectList();

	$empresasEmpleado = $empleado->obtenerElementosActivables($usuario);
	foreach ($empresasEmpleado as $empresa) {
		$maquinasEmpresa = $empresa->obtenerMaquinas();
		$maquinasAsignadas = $empleado->obtenerMaquinas($usuario);

		$asignadas = $asignadas->merge($maquinasAsignadas);
		$disponibles = $disponibles->merge($maquinasEmpresa);
	}
	$sinAsignar = $disponibles->discriminar($asignadas);

	$template->assign( "asignados" , $asignadas );
	$template->assign( "disponibles" , $sinAsignar  );
	$template->assign( "acciones" , array(
		array("string" => "ver_maquinas_asignadas", "href" => "#buscar.php?p=0&q=tipo:maquina%20empleado:" . $empleado->getUID(), "class" => "unbox-it" )	
	));
	$template->display( "configurar/asignarsimple.tpl" );
?>
