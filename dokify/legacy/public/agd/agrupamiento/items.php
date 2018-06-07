<?php
	include( "../../api.php");

	$data = array();

	if( ($parent=obtener_comefrom_seleccionado()) && ($m = obtener_modulo_seleccionado()) && $uid = obtener_uid_seleccionado() ){
		$empresa = new $parent($uid);
		$method = array( $empresa, "obtener". str_replace("_","", ucfirst($m)) . "s" );
		$coleccion = call_user_func($method);

		if( count($coleccion) && is_traversable($coleccion) ){
			$data = $coleccion->toArrayData($usuario );
		}

	} else {
		die("Inaccesible");
	}





	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();

	$accionesRapidas = $usuario->getOptionsFastFor($m);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"] . "poid={$empresa->getUID()}&comefrom={$parent}", "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($m);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}


	$json->establecerTipo("data");
	$json->nombreTabla($m);
	$json->datos( $data );
	$json->display();


?>
