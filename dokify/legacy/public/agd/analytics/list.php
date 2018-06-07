<?php
	include( "../../api.php");

	$data = array();
	$company = $usuario->getCompany();
	
	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$template = Plantilla::singleton();

	if( ($m = obtener_modulo_seleccionado()) && $usuario->accesoModulo($m, (int) @$_REQUEST["config"]) ){
		if( ($comefrom = obtener_comefrom_seleccionado()) && $uid = obtener_uid_seleccionado() ){
			$ref = new $comefrom($uid);
		} else {
			$ref = $usuario;
		}

		$method = array( $ref, "obtener". str_replace("_","",$m) . "s" );
		$coleccion = call_user_func($method);

		if( count($coleccion) && is_traversable($coleccion) ){
			$data = $coleccion->toArrayData($usuario, (int) @$_REQUEST["config"]);
		}
	} else {
		die("Inaccesible");
	}

	





	/*
	$accionesRapidas = $usuario->getOptionsFastFor($m, (int) @$_REQUEST["config"]);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"] . "poid={$parent->getUID()}&comefrom={$comefrom}", "box-it");
	}
	*/
	$accionesLinea = $usuario->getOptionsMultipleFor($m, (int) @$_REQUEST["config"]);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
		if( isset($uid) ){ $accion["href"] .= "&poid={$uid}"; }
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}

	/*
	$json->informacionNavegacion(
		$template->getString("inicio"), 
		array( "innerHTML" => $parent->getUserVisibleName(), "href" => $parent->obtenerUrlFicha(), "className" => "box-it",  "img" => ( $parent instanceof solicitable ) ? $parent->getStatusImage($usuario) : false ), 
		$template->getString("{$m}s")
	);
	*/

	$seleccionado = ( isset($comefrom) ) ? $comefrom : $m;	
	$json->menuSeleccionado($seleccionado);
	$json->informacionNavegacion(
		$template->getString("inicio"), 
		$template->getString("analytics"),
		($comefrom)?$template->getString($comefrom):null,
		($comefrom)?$ref->getUserVisibleName():null,
		$template->getString($m)
	);
	$json->establecerTipo("data");
	$json->iface("analytics");
	$json->nombreTabla($m);
	$json->datos($data);
	$json->display();
?>
