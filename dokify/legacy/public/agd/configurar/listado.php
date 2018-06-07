<?php
	/* LISTADO DE ITEMS */
	include( "../../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();
	$parent = null;
	$data = array();
	$comefrom = obtener_comefrom_seleccionado();
	$config = (int) @$_REQUEST["config"];
	if( $m = obtener_modulo_seleccionado() /* && $datosAccesoModulo = $usuario->accesoModulo($m, 1)*/ ){

		$parent = $usuario->getCompany();

		$method = array( $parent, "obtener". str_replace("_","", ucfirst($m)) . "s" );
		$coleccion = call_user_func($method);
		

		if( count($coleccion) && is_traversable($coleccion) ){
			$data = $coleccion->toArrayData($usuario,$config);
			/*
			foreach( $coleccion as $item ){
				//objeto donde guardaremos los datos de este documento
				$datosItem = array();

				//el nombre
				$HTMLName = "<span class='ucase'>". $item->getUserVisibleName() ."</span>";

				$datosItem["lineas"] = $item->getInfo(true, elemento::PUBLIFIELDS_MODE_TABLEDATA, $usuario);
				//asginamos los datos de la linea
				//$datosItem["lineas"] = array( $item->getUID() => array($HTMLName) );

				$datosItem["options"] = config::obtenerOpciones( $item->getUID(), $m, $usuario, true, 1 );

				//guardamos el objeto actual al global
				$data[] = $datosItem;
				
			}
			*/
		}

	} else {
		die("Inaccesible");
	}





	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	$accionesRapidas = $usuario->getOptionsFastFor($m, 1);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"])."poid={$parent->getUID()}&comefrom={$comefrom}&config={$config}";
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($m, 1);

	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}

	$json->informacionNavegacion(
		$template->getString("inicio"), 
		array( "innerHTML" => $parent->getUserVisibleName(), "href" => $parent->obtenerUrlFicha(), "className" => "box-it",  "img" => ( $parent instanceof solicitable ) ? $parent->getStatusImage($usuario) : false ), 
		$template("{$m}")
	);
	$json->establecerTipo("data");
	$json->nombreTabla("config-".$m);
	$json->datos( $data );
	$json->display();


?>
