<?php
	include("../../api.php");

	// comprobando acceso 
	$datosAccesoModulo = $usuario->accesoModulo("buscador");
	//if( !is_array($datosAccesoModulo) ){ die("Inaccesible"); }
	$template = Plantilla::singleton();
	$json = new jsonAGD();	
	$json->establecerTipo("data");

	// obtenemos las busquedas del usuario activo;
	$busquedas = $usuario->obtenerBusquedas();
	$busquedasCompartidas = $usuario->obtenerBusquedasCompartidas();
	$datosBusquedas = array();

	// Reutilizamos codigo...
	function recorrerBuquedas($busquedas, &$datosBusquedas, $usuario, $propias=true){
		// creamos el array que formatearemos para crear el json
		foreach ($busquedas as $busqueda){
			$datosBusqueda = array();
			/*
			$info = $busqueda->getInfo(true);
			$datosBusqueda["href"] = '#buscar.php?q='.$info[$busqueda->getUID()]["cadena"];
			$n = $info[$busqueda->getUID()]['nombre'];

			unset($info[$busqueda->getUID()]['nombre']);
			$info[$busqueda->getUID()]['nombre']['innerHTML'] = $n;
			$info[$busqueda->getUID()]['nombre']['title'] = 'Cadena que se buscará: '.$info[$busqueda->getUID()]['cadena'];
			//$info[$busqueda->getUID()]['nombre']['className'] = 'link';
			//$info[$busqueda->getUID()]['nombre']['href'] = '#buscar.php?q='.$info[$busqueda->getUID()]["cadena"];
			unset($info[$busqueda->getUID()]['cadena']);
			$datosBusqueda["lineas"] = $info;/**/
			$datosBusqueda["lineas"] = $busqueda->getTableInfo($usuario, NULL, array());


			$datosBusqueda["inline"] = $busqueda->getInlineArray($usuario);

			if( $url = $busqueda->getClickURL() ){
				$datosBusqueda["href"] = $url;
			}

			
			$opciones = $busqueda->getAvailableOptions($usuario, true);
			if ( count($opciones) ) {
				$datosBusqueda["options"] = $opciones;
			}	
			
			$datosBusquedas[] = $datosBusqueda;    
		}
	}

	recorrerBuquedas( $busquedas, $datosBusquedas, $usuario );

	if( is_array($busquedasCompartidas) && count($busquedasCompartidas) ){
		$datosBusquedas[] = array( 
			"group" => "Busquedas compartidas conmigo"
		);
		recorrerBuquedas( $busquedasCompartidas, $datosBusquedas, $usuario, false );
	}

	// accion multiple para borrar varias busquedas a la vez
	/*
	$accionesMultiples = $usuario->getOptionsMultipleFor('buscador');
	foreach( $accionesMultiples as $accion ){
		$json->element('options', 'button', array(
			'innerHTML' =>  $accion['name'], 
			'class' => 'multiple-action btn', 
			'href' => $accion['href'], 
			'img' =>  $accion['img']
		));
	}
	*/
	$json->informacionNavegacion('Inicio', 'Búsquedas guardadas');
	$json->menuSeleccionado('busquedas');
	$json->establecerTipo('data');
	$json->nombreTabla('busqueda');
	$json->datos($datosBusquedas);
	$json->display();
