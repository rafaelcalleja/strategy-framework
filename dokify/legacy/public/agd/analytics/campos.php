<?php

	include( "../../api.php");
	$tpl = Plantilla::singleton();

	if( isset($_REQUEST["action"]) ){

		// Siempre debemos recibir el id del datamodel en $_REQUEST["oid"]
		if( $uid = @$_REQUEST["oid"] ){
			$model = new datamodel($uid);
			// Que acción se va a realizar
			switch($_REQUEST["action"]){
				case "add":
					if( $fieldID = obtener_uid_seleccionado() ){
						$data = array("uid_datafield" => $fieldID, "uid_datamodel" => $uid);
						$modelfield = new modelfield($data, $usuario);
						if( !$modelfield->error && $modelfield->exists() ){
							$response = array("refresh" => 1, "jGrowl"=> "Se asignó correctamente", "iface" => "analytics");
							if( $modelfield->getDataField()->getParam() ){
								$response["open"] = "configurar/modificar.php?m=modelfield&poid={$modelfield->getUID()}";
							}

							die(json_encode($response));
						} else {
							die(json_encode(array("jGrowl"=> $tpl->getString($modelfield->error), "iface" => "analytics")));
						}
					}
					exit;
				break;
				case "remove":
					if( $fieldID = obtener_uid_seleccionado() ){
						if( $modelfield = new modelfield($fieldID) ){
							if( $modelfield->eliminar($usuario) ){
								die(json_encode(array("refresh" => 1, "jGrowl"=> "Se eliminó correctamente", "iface" => "analytics")));
							} else {
								die(json_encode(array("jGrowl"=> "Error al eliminar", "iface" => "analytics")));
							}
						}
					}
					exit;
				break;
				case "order":
					if( $fieldID = obtener_uid_seleccionado() ){
						$order = $_REQUEST["order"];
						$dir = $_REQUEST["dir"];
						if (is_numeric($order)) {
							$modelFields = $model->obtenerModelFields();
							$limit = count($modelFields) - 1;
							foreach ($modelFields as $modelField) {
								$positionField =  $modelField->getPosition();
								if ($dir == "up") {
									if ($positionField == $order - 1) {
										$modelField->update(array("position"=>$positionField+1), elemento::PUBLIFIELDS_MODE_ATTR);
									} elseif ($positionField == $order && $order > 0) {
										$modelField->update(array("position"=>$order-1), elemento::PUBLIFIELDS_MODE_ATTR);
									}
								} elseif ($dir == "down") {
									if ($positionField == $order + 1) {
										$modelField->update(array("position"=>$positionField-1), elemento::PUBLIFIELDS_MODE_ATTR);
									} elseif ($positionField == $order && $order < $limit) {
										$modelField->update(array("position"=>$order+1), elemento::PUBLIFIELDS_MODE_ATTR);
									}
								}
							}
							header("Content-type: application/json");
							print json_encode(array("refresh" => 1, "iface" => "analytics"));
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
			$model = new datamodel($uid);
		}
	}

	if( !isset($model) ) die("Inaccesible");





	/*************************************************
	**************** LISTAR DATOS ********************
	*************************************************/

	$data = new extendedArray();

	$data[] = array(
		"id" => "available-fields",
		"group" => "Campos disponibles para  <strong>{$model->getUserVisibleName()}</strong>",
		"droppable" => "analytics/campos.php?action=remove&oid={$model->getUID()}",
		//"route" => $_SERVER["REQUEST_URI"],
		"css" => array("height" => "200px", "overflow" => "auto") ,
		"moveable" => true,
		"searchable" => true
	);

	$coleccion = $model->obtenerAvailableDataFields();
	if( count($coleccion) && is_traversable($coleccion) ){
		$fields = $coleccion->toArrayData($usuario);
		$data = $data->merge($fields);
	}


	$coleccion = $model->obtenerModelFields();
	$sarchable = count($coleccion) > 6 ? true : false;
	$data[] = array(
		"id" => "used-fields",
		"group" => "Campos en uso por <strong>{$model->getUserVisibleName()}</strong> " . " - (" . $tpl->getString('order_field') . ")",
		"droppable" => "analytics/campos.php?action=add&oid={$model->getUID()}",
		//"route" => "analytics/campos.php?comefrom=assigned&oid={$model->getUID()}",
		"css" => array("height" => (count($coleccion)*38)."px", "overflow" => "auto"),
		"moveable" => true,
		"searchable" => $sarchable
	);

	if( count($coleccion) && is_traversable($coleccion) ){
		$fields = $coleccion->toArrayData($usuario, 0 , array("tipo" => modelfield::IN_USE, "modeloUID" => $model->getUID()));
		$data = $data->merge($fields);
	}


	$json = new jsonAGD();
	$json->informacionNavegacion(
		$tpl->getString("inicio"),
		$tpl->getString("analytics"),
		$tpl->getString("datamodel"),
		$model->getUserVisibleName(),
		$tpl->getString("campos")
	);

	$json->menuSeleccionado("datamodel");
	$json->iface("analytics");
	$json->establecerTipo("data");
	$json->nombreTabla("datafields-$uid");
	$json->datos($data);
	$json->display();
?>
