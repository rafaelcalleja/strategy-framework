<?php

	require_once("../../../api.php");

	$template = new Plantilla();

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();


	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("llamada","listado llamadas","sistema");

	//--------- COMPROBAMOS EL ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("llamada",1);
	if( !is_array($datosAccesoModulo) ){$log->resultado("error acceso modulo", true); exit;}

	$datosLlamadas = array();

	if( isset($_REQUEST["poid"]) ){
		$llamadaSeleccionada = new llamada($_REQUEST["poid"]);	
		$llamadas = $llamadaSeleccionada->obtenerLlamadasHijas();		
	} else {
		$conteoTotal = llamada::obtenerConteoTodasLlamadas();
		$datosPaginacion = preparePagination( 20, $conteoTotal );

		$llamadas = llamada::obtenerTodasLlamadas(null, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]));
	}



	foreach($llamadas as $llamada) {
		$datosLlamada = array();

		
		if( !isset($_REQUEST["poid"]) ){
			if( $llamada->numeroHijas() ) {
				$datosLlamada["tree"] = array(
					"img" => array(
						"normal" => $llamada->getIcon(),
						"open" => $llamada->getIcon("open")
					),
					"checkbox" => true,
					"url" => "configurar/llamada/listado.php?poid=".$llamada->getUID()
				);
			}
		}

	
		$datosLlamada["lineas"] = $llamada->getInfo(true, "table");
		$datosLlamada["inline"] = $llamada->getInlineArray( $usuario );
		$datosLlamada["options"] = $llamada->getAvailableOptions( $usuario, true , 1 );

		$datosLlamadas[] = $datosLlamada;
	}


	


	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	$json->element("options", "button", array(
		'innerHTML' => 'Grafico por usuario', 'className' => 'btn', 'href' => '#configurar/estadisticas/llamadas.php', 'img' => RESOURCES_DOMAIN . '/img/famfam/chart_bar.png' 
	));
	$json->element("options", "button", array(
		'innerHTML' => 'Grafico por ambito', 'className' => 'btn', 'href' => '#configurar/estadisticas/llamadas.php?m=ambito', 'img' => RESOURCES_DOMAIN . '/img/famfam/chart_curve.png'
	));

	$json->addPagination( $datosPaginacion );
	$json->establecerTipo("data");
	$json->nombreTabla("llamada");

	$json->datos( $datosLlamadas );
	$json->display();

?>
