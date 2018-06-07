<?php
	/* LISTADO DE ITEMS */

	include( "../../api.php");

	/*
		- AQUI ACCEDEMOS A TRES MODULO DIFERENTES CON LO QUE DE MOMENTO PARA ESTABLECER SEGURIDAD MINIMA LO HACEMOS POR MODULO_ACCION DE LISTAR SALUD
		- ES DECIR DE MOMENTO NO SE ESTABLECE EL ACCESO POR MODULO, SINO POR ACCESO A ACCION	
	*/
	if( !is_array( $usuario->accesoAccionConcreta("empleado", 123) ) ){ die("Inaccesible");}

	$m = obtener_modulo_seleccionado();
	$data = array();

	if( $uid = obtener_uid_seleccionado() ){
		$empleado = new empleado($uid);

		if( $m === "documento" ){	
			$documentos = $empleado->getDocuments(0, null, false, array("modulo_salud" => 1), false, false);
			$coleccion = new ArrayObjectList($documentos);
		} else {
			if( isset($_REQUEST["oid"]) && ($oid=$_REQUEST["oid"]) && $m == "citamedica" ){
				$convocatoria = new convocatoriamedica($oid);
				$coleccion = $convocatoria->obtenerCitamedicas();
			} else {
				$m = ($m) ? $m : "baja"; 
				$fn = array($empleado, "obtener{$m}s");
		
				$coleccion = call_user_func($fn);
			}
		}

		if( count($coleccion) && is_traversable($coleccion) ){
			$data = $coleccion->toArrayData($usuario);
		}

	} else {
		die("Inaccesible");
	}

	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$template = Plantilla::singleton();

	
	$accionesRapidas = $usuario->getOptionsFastFor($m);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"] . "poid={$empleado->getUID()}", "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($m);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}&poid={$empleado->getUID()}";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}
	



	$tabs = array();
	$tabs[] = array(
		"className" => ($m=="baja")?"selected":null,
		"innerHTML" => $template->getString("bajas"),
		"count" => $empleado->obtenerBajas(true),
		"img" => RESOURCES_DOMAIN . "/img/famfam/heart_delete.png",
		"href" => "#salud/listado.php?poid=" . obtener_uid_seleccionado()
	);
	$tabs[] = array(
		"className" => ($m=="convocatoriamedica")?"selected":null,
		"innerHTML" => $template->getString("citas"),
		"count" => $empleado->obtenerConvocatoriaMedicas(true),
		"img" => RESOURCES_DOMAIN . "/img/famfam/heart_add.png",
		"href" => "#salud/listado.php?m=convocatoriamedica&poid=" . obtener_uid_seleccionado()
	);


	$docs = $empleado->getDocumentsId(0, null, false, array("modulo_salud" => 1), false, false);
	if (count($docs)) {
		$tabs[] = array(
			"className" => ($m=="documento")?"selected":null,
			"innerHTML" => $template->getString("documentos"),
			"count" => count($docs),
			"img" => RESOURCES_DOMAIN . "/img/famfam/folder.png",
			"href" => "#salud/listado.php?m=documento&poid=" . obtener_uid_seleccionado()
		);
	}

	$json->informacionNavegacion("inicio", 
		array( "innerHTML" => $empleado->getUserVisibleName(), "href" => $empleado->obtenerUrlFicha(), "className" => "box-it", "title" => $empleado->getUserVisibleName(), "img" => $empleado->getStatusImage($usuario) ), 
		$template->getString("salud"),
		$template->getString($m)
	);
	

	$json->addDataTabs($tabs);
	$json->menuSeleccionado("empleado");
	$json->establecerTipo("data");
	$json->nombreTabla($m);
	$json->datos( $data );
	$json->display();


?>
