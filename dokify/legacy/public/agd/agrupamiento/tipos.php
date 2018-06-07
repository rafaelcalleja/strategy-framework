<?php
	include( "../../api.php");

	
	$log = log::singleton(); //----- INSTANCIAMOS EL OBJETO LOG
	$template = Plantilla::singleton(); //--- Instanciar la plantilla

	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("cliente","listado agrupamientos",$usuario->getCompany()->getUserVisibleName());


	//---- Control de acceso
	if( !is_array($usuario->accesoModulo("agrupamiento")) ){ $log->resultado("error acceso modulo", true); die("Inaccesible"); }

	$datosAgrupamientos = array();
	$empresaUsuario = $usuario->getCompany();
	
	$agrupamientosPropios = $empresaUsuario->obtenerAgrupamientosPropios($usuario);
	$agrupamientosAsignadosCorp = $empresaUsuario->obtenerAgrupamientosCorporacionAsignados($usuario);

	if (is_traversable($agrupamientosPropios) && count($agrupamientosPropios)){
		if (is_traversable($agrupamientosAsignadosCorp) && count($agrupamientosAsignadosCorp)){
			$datosAgrupamientos[] = array("group" => $template->getString("agrupamientos_propios"));
		}
		
		$agrupamientosPropios = $agrupamientosPropios->toArrayData($usuario);
		$datosAgrupamientos = array_merge($datosAgrupamientos,$agrupamientosPropios);
	}
	
	
	if (is_traversable($agrupamientosAsignadosCorp) && count($agrupamientosAsignadosCorp)){
		$datosAgrupamientos[] = array("group" =>  $template->getString("agrupamientos_asignados"));
		$agrupamientosAsignados = $agrupamientosAsignadosCorp->toArrayData($usuario);
		$datosAgrupamientos = array_merge($datosAgrupamientos,$agrupamientosAsignados);
	}
	
		

	$json = new jsonAGD();
	$json->addHelpers( $usuario );
	$json->establecerTipo("data");

	//--------- Acciones
	$accionesRapidas = $usuario->getOptionsFastFor("agrupamiento");
	foreach( $accionesRapidas as $accion ){
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}


	$json->informacionNavegacion($template->getString("inicio"), $template->getString("agrupamientos"));
	$json->nombreTabla("agrupamiento");
	$json->menuSeleccionado( "agrupamiento" );
	$json->datos( $datosAgrupamientos );
	$log->resultado("ok", true);
	$json->display();
?>
