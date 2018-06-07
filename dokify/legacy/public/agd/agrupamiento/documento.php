<?php
	/* LISTADO DE ATRIBUTOS DE DOCUMENTO DE UN AGRUPADOR */
	include( "../../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();

	$m = obtener_modulo_seleccionado();

	if( $m == "agrupamiento" ){
		$elemento = new agrupamiento(obtener_uid_seleccionado());
	} else {
		$elemento = new agrupador(obtener_uid_seleccionado());
	}

	$owner = $elemento->getCompany();
	if ($owner->esCorporacion() && $usuario->getCompany()->perteneceCorporacion() && !$owner->obtenerEmpresasInferiores()->contains($usuario->getCompany())){
		die("Inaccesible");
	}else if (!$usuario->getCompany()->perteneceCorporacion() && !$owner->compareTo($usuario->getCompany())){
		die("Inaccesible");
	}

	$documentos = $elemento->obtenerDocumentoAtributos($activo = true, $usuario);

	//elemento donde almacenaremos todos los documentos
	$datosDocumentos = array();

	//$documentos = obtener_array_atributo_documentos( $currentUIDdocumento );
	foreach( $documentos as $attr ){
		//id del atributo
		$uid = $attr->getUID();

		//objeto donde guardaremos los datos de este documento
		$datosDocumento = array();


		$datosDocumento["inline"] = $attr->getInlineArray($usuario); //datos extra del atributo
		$datosDocumento["lineas"] = $attr->getInfo(true);
		if( $usuario->esSati() ) $datosDocumento["lineas"][ $uid ]["uid"] = $uid;

		//opciones
		$datosDocumento["options"] = config::obtenerOpciones( $uid, "5" /* MODULO ATRIBUTOS DOCUMENTOS */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );

		//$datosDocumento["options"] = obtener_opciones_documentos($uid);
		//guardamos el objeto actual al global
		$datosDocumentos[] = $datosDocumento;
	}

	if( $m === NULL ){
		$agrupamiento = $elemento->obtenerAgrupamientoPrimario();
		$atributos = $agrupamiento->obtenerDocumentoAtributos($activo = true, $usuario);
		if( count($atributos) ){
			$datosDocumentos[] = array( "group" =>  sprintf( $template->getString("documentos_herados_de"), $agrupamiento->getUserVisibleName()) );
			foreach( $atributos as $attr ){
				$uid = $attr->getUID();
				$datosDocumento = array();

				$datosDocumento["inline"] = $attr->getInlineArray($usuario); //datos extra del atributo
				$datosDocumento["lineas"] = $attr->getInfo(true);
				$datosDocumento["options"] = config::obtenerOpciones( $uid, "5" /* MODULO ATRIBUTOS DOCUMENTOS */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );

				$datosDocumentos[] = $datosDocumento;
			}
		}
	}



	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();


	/*
	$accionesRapidas = config::obtenerOpciones(null, $elemento->getModuleId(), $usuario, false, 1, 3);
	foreach( $accionesRapidas as $accion ){
		$href = $accion["href"] . get_concat_char($accion["href"]) . "poid=" . obtener_uid_seleccionado();
		$json->acciones( $accion["alias"],	$accion["icono"],	$href, "box-it");
	}
	*/

	$accionesRapidas = $usuario->getOptionsFastFor($elemento->getModuleId(), 1);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"] . "poid={$elemento->getUID()}&comefrom=$m", "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor($elemento->getModuleId(), 1);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$m}";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}
	
	$accionesLinea = $usuario->getOptionsMultipleFor("documento_atributo", 1);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]);
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}

	$json->establecerTipo("data");
	$json->nombreTabla("documento_atributo-".$elemento->getModuleName());
	$json->menuSeleccionado( "agrupamiento" );
	$json->informacionNavegacion("inicio", $template->getString("agrupamientos"), 
		array("innerHTML"=>$elemento->getUserVisibleName(), "href"=>$elemento->obtenerUrlPreferida(), "title"=>$elemento->getUserVisibleName()),
		$template->getString("documentos")  
	);
	$json->datos( $datosDocumentos );
	$json->display();

?>
