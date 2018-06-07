<?php
	include( "../../api.php");

	$agrupador = new agrupador( obtener_uid_seleccionado() );


	$log = log::singleton(); //----- INSTANCIAMOS EL OBJETO LOG
		$log->info("agrupador","listado agrupador reverse", $agrupador->getUserVisibleName());
	$template = Plantilla::singleton(); //--- Instanciar la plantilla
	

	//---- Control de acceso
	if( !is_array($usuario->accesoModulo("agrupador") ) ){ $log->resultado("error acceso modulo", true); die("Inaccesible"); }
	if( !$usuario->accesoElemento($agrupador) ){ $log->resultado("error acceso modulo", true); die("Inaccesible"); }


	$datosLineas = array(); //---- Array general de salida


	$query = array("poid" => $agrupador->getUID() );
	if( isset($_GET["oid"]) ){
		$agrupadorSuperior = new agrupador($_GET["oid"]);
		if( $usuario->accesoElemento($agrupadorSuperior) ){
			$elementos = $agrupadorSuperior->obtenerElementosAsignados("agrupador");
		} else {
			$log->resultado("error acceso modulo", true); die("Inaccesible");
		}
	} else {
		$elementos = array($agrupador);
	}


	//dump($agrupador->getUserVisibleName(),"---------------------");
	foreach( $elementos as $subagrupador ){
		if( !$subagrupador->exists() || ( isset($_GET["oid"])&&util::comparar($subagrupador,$agrupador) )  ){ continue; }

		$subagrupamiento = reset( $subagrupador->obtenerAgrupamientosContenedores() );

		// Vista desplegable
		if( isset($_GET["oid"]) ){
			// Si no esta establecido la variable agrupamiento O esta establecida y es diferente a la actual...
			if( !isset($agrupamiento) || ( isset($agrupamiento) && !util::comparar($subagrupamiento,$agrupamiento) ) )
			$datosLineas[] = array( 
				"group" => $subagrupamiento->getUserVisibleName()
			);
			$agrupamiento = $subagrupamiento;
		}
		
		//dump($subagrupador->getUserVisibleName(), $subagrupador->getUID(),"-------------");
		//---- Datos de el elemento principal
		$datosElemento = array();

		$datosElemento["lineas"] = $subagrupador->getInfo( true, "table" );
		$datosElemento["options"]  = $subagrupador->getAvailableOptions( $usuario, true );

		$query["oid"] = $subagrupador->getUID();
		$datosElemento["tree"] = array(
			"img" => array( "normal" => RESOURCES_DOMAIN . "/img/famfam/arrow_join_reverse.png" ),
			"checkbox" => true,
			"autoload" => true,
			"url" => $_SERVER["PHP_SELF"] . "?" . http_build_query($query)
		);

		if( !count($subagrupador->obtenerElementosAsignados("agrupador")) ){
			unset( $datosElemento["tree"]["url"] );	
		}

		$datosLineas[] = $datosElemento;
	}



	$json = new jsonAGD();
	$json->establecerTipo("data");


	$json->informacionNavegacion($template->getString("inicio"), $template->getString("agrupador"), $agrupador->getUserVisibleName());
	$json->nombreTabla("agrupador");
	$json->menuSeleccionado( "agrupamiento" );
	$json->datos( $datosLineas );
	$log->resultado("ok", true);
	$json->display();
?>
