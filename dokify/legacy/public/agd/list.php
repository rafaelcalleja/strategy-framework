<?php
	set_time_limit(0);
	require __DIR__ . "/../api.php";

	$data = array();
	$json = new jsonAGD();
	
	if( ($comefrom = obtener_comefrom_seleccionado()) && ($m = obtener_modulo_seleccionado()) && ($uid = obtener_uid_seleccionado()) ){

		// antes de instanciar para evitar instanciar cosas que puede que no existan
		if ($comefrom == 'empresa' && !$usuario->getCompany()->getStartIntList()->contains($uid) ) {
			die('Inaccesible');
		}

		$parent = new $comefrom($uid);
		$action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : str_replace("_","",$m) . "s";
		$method = array( $parent, "obtener". $action );	
		$URLdata = obtener_params('data');
		$params = ($params=obtener_params()) ? $params : array();

		$canAccessItem = $usuario->accesoElemento($parent) && $usuario->accesoModulo($m, (int) @$_REQUEST["config"]);

		if ($canAccessItem || $usuario->canView($parent, @$URLdata['context'], $params) ){

			if (isset($_SERVER['HTTP_X_TREE'])) {
				$getTotal = array($parent, "getNum{$action}");
				if (is_callable($getTotal)) {
					$total = call_user_func_array($getTotal, array($usuario));

					$paginacion = preparePagination(100, $total);
					$params[] = array($paginacion["sql_limit_start"], $paginacion["sql_limit_end"]);

					$json->addPagination($paginacion);
				}
			}

			$params[] = $usuario; //add user as last method parameter
			


			$coleccion = call_user_func_array($method, $params);
			$options = isset($_REQUEST["options"]) ? (bool)$_REQUEST["options"] : true;
			if( count($coleccion) && $coleccion instanceof ArrayObjectList ){
				$data = $coleccion->toArrayData($usuario, (int) @$_REQUEST["config"], $URLdata, $options);
			}
		} else {
			die("Inaccesible");
		}
	} else {
		die("Inaccesible");
	}





	$template = Plantilla::singleton();

	$accionesRapidas = $usuario->getOptionsFastFor($m, (int) @$_REQUEST["config"]);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"] . "poid={$parent->getUID()}&comefrom={$comefrom}", "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($m, (int) @$_REQUEST["config"]);
	if ($accionesLinea) {
		foreach( $accionesLinea as $accion ){
		// Delta solo para España -- HACK total, pero no encuentro alternativa que compense...
			if ($accion['uid_accion'] == 134 && getCurrentLanguage() != 'es_ES') continue;

			$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
			$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
			$json->element("options", "button", $accion);
		}
	
	}
	
	$json->informacionNavegacion(
		$template->getString("inicio"), 
		array( "innerHTML" => $parent->getUserVisibleName(), "href" => $parent->obtenerUrlFicha(), "className" => "box-it",  "img" => ( $parent instanceof solicitable ) ? $parent->getStatusImage($usuario) : false ), 
		$template("{$action}")
	);

	$json->menuSeleccionado($comefrom);
	$json->establecerTipo("data");
	$json->nombreTabla($m);
	$json->datos( $data );
	$json->display();
?>
