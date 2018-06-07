<?php
	/* LISTAR ITEMS */
	include( "../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	//$template = Plantilla::singleton();

	$data = new extendedArray();


	$m = @$_GET["m"];
	$methodString = str_replace("_","", ucfirst($m));
	$methodName = "obtener{$methodString}s";

	if( $uid = obtener_uid_seleccionado() ){
		$empleadoSeleccionado = new empleado($uid);
		$method = array( $empleadoSeleccionado, $methodName );
	} else {
		$method = array( $usuario, $methodName );
	}

	if( is_callable($method) /* && $datosAccesoModulo = $usuario->accesoModulo($m, 1)*/ ){
		$coleccion = call_user_func($method, @$_REQUEST["params"]);

		if( $coleccion instanceof ArrayObject ){
			try {
				$data = $data->merge( $coleccion->toArrayData($usuario) );
			} catch(Exception $e) {
				if( CURRENT_ENV != 'prod') dump($e->getMessage());
			}
		}
	} else {
		die("Inaccesible");
	}





	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	/*
	$accionesRapidas = $usuario->getOptionsFastFor($m, 1);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($m, 1);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}
	*/

	$json->menuSeleccionado(@$_GET["m"]);
	$json->establecerTipo("data");
	$json->nombreTabla("list-".$m);
	$json->datos( $data );
	$json->display();


?>
