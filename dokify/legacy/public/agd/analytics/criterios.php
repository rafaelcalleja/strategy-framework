<?php
	include( "../../api.php");

	$tpl = Plantilla::singleton();

	if( !$m = obtener_modulo_seleccionado() ) die("Inaccesible");
	
	if( isset($_REQUEST["action"]) ){

		// Siempre debemos recibir el id del datamodel en $_REQUEST["oid"]
		
		if( $uid = @$_REQUEST["oid"] ){
			$dataItem = new $m($uid);

			
			// Que acción se va a realizar
			switch($_REQUEST["action"]){
				case "add":
					if( $fieldID = obtener_uid_seleccionado() ){
						$data = array("uid_datafield" => $fieldID, "uid_elemento" => $uid, "uid_modulo" => $dataItem->getModuleId());

						$criterion = new datacriterion($data, $usuario);
						if( !$criterion->error && $criterion->exists() ){
							die(json_encode(array("refresh" => 1, "iface" => "analytics", "open" => "configurar/modificar.php?m=datacriterion&poid={$criterion->getUID()}", "jGrowl" => "Se asignó correctamente")));
						} else {
							die(json_encode(array("jGrowl"=> $tpl->getString($criterion->error), "iface" => "analytics")));
						}
					} 
					exit;
				break;
				case "remove":
					if( $criterionID = obtener_uid_seleccionado() ){
						if( $criterion = new datacriterion($criterionID) ){
							if( $criterion->eliminar($usuario) ){
								die(json_encode(array("refresh" => 1, "jGrowl"=> "Se eliminó correctamente", "iface" => "analytics")));
							} else {
								die(json_encode(array("jGrowl"=> "Error al eliminar", "iface" => "analytics")));
							}
						}
					} 
					exit;
				break;
				default:
					// Dejamos que cargue normal
				break;
			}
			
		}

	} else {
		if( $uid = obtener_uid_seleccionado() ){
			$dataItem = new $m($uid);
		}
	}


	if( !isset($dataItem) || !$dataItem->exists() ) die("Inaccesible");
	$model = $dataItem->getDataModel();
	if ($model->exists() == false) {
		$tpl 	= Plantilla::singleton();
		$data 	= [
			"alert" 	=> $tpl('no_model_found'),
			'iface' 	=> 'analytics',
			"action" 	=> [
				"go" => "#analytics/list.php?m=dataexport"
			]
		];
		print json_encode($data);
		exit;
	}




	/*************************************************
	**************** LISTAR DATOS ********************
	*************************************************/

	$data = new extendedArray();

	$data[] = array(
		"id" => "available-fields", 
		"group" => "Campos disponibles para  <strong>{$dataItem->getUserVisibleName()}</strong>", 
		"droppable" => "analytics/criterios.php?m={$dataItem->getModuleName()}&action=remove&oid={$dataItem->getUID()}", 
		//"route" => $_SERVER["REQUEST_URI"],
		"css" => array("height" => "200px", "overflow" => "auto") ,
		"moveable" => true,
		"searchable" => true
	);

	$coleccion = $dataItem->obtenerAvailableDataFields();
	if( count($coleccion) && is_traversable($coleccion) ){
		$fields = $coleccion->toArrayData($usuario);
		$data = $data->merge($fields);
	}
	

	$coleccion = $dataItem->obtenerDataCriterions();
	
	$searchable = ( count($coleccion) > 6 ) ? true : false;
	$data[] = array(
		"id" => "used-fields", 
		"group" => "Campos en uso por <strong>{$dataItem->getUserVisibleName()}</strong>", 
		"droppable" => "analytics/criterios.php?m={$dataItem->getModuleName()}&action=add&oid={$dataItem->getUID()}", 
		//"route" => "analytics/criterios.php?m={$dataItem->getModuleName()}&comefrom=assigned&oid={$dataItem->getUID()}",
		"css" => array("height" => "320px", "overflow" => "auto"),
		"moveable" => true,
		"searchable" => $searchable
	);

	if( count($coleccion) && is_traversable($coleccion) ){
		$fields = $coleccion->toArrayData($usuario);
		$data = $data->merge($fields);
	}



	$json = new jsonAGD();
	$json->informacionNavegacion(
		$tpl->getString("inicio"), 
		$tpl->getString("analytics"),
		$tpl->getString($dataItem->getModuleName()),
		$model->getUserVisibleName(),
		$tpl->getString("criterios")
	);
	$json->menuSeleccionado($dataItem->getModuleName());
	$json->iface("analytics");
	$json->establecerTipo("data");
	$json->nombreTabla("{$dataItem->getModuleName()}-$uid");
	$json->datos($data);
	$json->display();
?>
