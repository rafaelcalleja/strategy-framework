<?php
	
	require __DIR__ . '/../api.php';

	$company = $usuario->getCompany();	
	$query = "tipo:empresa uid:{$company->getUID()} estado:error+tipo:empleado estado:error empresa:{$company->getUID()}+tipo:maquina estado:error empresa:{$company->getUID()}";

	$template = Plantilla::singleton();
	$json = new jsonAGD();
	$json->nombreTabla("buscar");
	$json->informacionNavegacion($template("buscar"));

	if ($result = buscador::get($query, $usuario, true)){
		if (count($result)) {
			$extraData = array(Ilistable::DATA_SEARCH => $query);
			if ($comefrom = obtener_comefrom_seleccionado()) {
				$extraData[Ilistable::DATA_COMEFROM] = $comefrom;
			}

			$busquedas = $result->toArrayData($usuario, NULL, $extraData);
		} else {
			$json->establecerTipo("simple");
			$json->nuevoSelector("#main", $template->getHTML('empty_list.tpl'));
			$json->display();
			exit;
		}
	}

	
	$json->establecerTipo("data");
	if (isset($result->pagination)){
		$json->addPagination($result->pagination);
	}
	
	if (isset($busquedas)) $json->datos($busquedas);
	$json->addData("cachetime", 8000); // 8 segundos
	$json->display();