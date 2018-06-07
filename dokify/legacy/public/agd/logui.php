<?php
	require_once("../api.php");

	$template = new Plantilla();
	$json = new jsonAGD();

	if ($m = obtener_modulo_seleccionado()) {
		if ($uid = obtener_uid_seleccionado()) {
			$item = new $m($uid);
		}
	}

	(string) $html = '';
	if (isset($item)) {

		$total = $item->getLogUIEntries(true, null, null, null, $usuario);
		$paginacion = preparePagination(20, $total);
		

		$list = $item->getLogUIEntries(false, $paginacion["sql_limit_start"], $paginacion["sql_limit_end"], null, $usuario);

		$dataLogui = array();
		foreach($list as $logui) {
			$data = array();
			$data["lineas"] = $logui->getTableInfo($usuario);
			$dataLogui[] = $data;
		}
		
		$json->addPagination($paginacion);
		$json->establecerTipo("data");
		$json->nombreTabla("logui");
		$json->datos($dataLogui);
		$json->menuSeleccionado("");

		$json->informacionNavegacion( $template("inicio") );

		if (is_callable(array($item, "obtenerEmpleado") ) && $empleado = $item->obtenerEmpleado()) {
			$json->informacionNavegacion(array("innerHTML" => $empleado->getUserVisibleName(), "href" => $empleado->obtenerUrlPreferida()), $template(get_class($item)));
		}

		if ($usuario instanceof usuario) {
			$json->informacionNavegacion(array("innerHTML" => $item->getUserVisibleName(), "href" => $item->obtenerUrlPreferida() ), $template("registro_de_cambios"));
		}

		$json->informacionNavegacion($template("registro_de_cambios"));
	}

	$json->display();
?>
