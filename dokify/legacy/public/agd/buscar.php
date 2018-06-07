<?php
	require_once __DIR__ . '/../api.php';
	
	if( ( isset($_GET["q"]) && $query = trim($_GET["q"]) ) || ( isset($searchString) && $query = $searchString ) ){
		// ok, pass...
	} else {
		die("Inaccesible");
	}


	session_write_close();


	$buscarEnPapelera = ( isset($_GET["papelera"]) && $_GET["papelera"] ) ? true : false;
	if( ( isset($_REQUEST["export"]) && $exportType = $_REQUEST["export"] ) || ( isset($searchExport) && $exportType = $searchExport ) ){

		set_time_limit(0);
		ini_set("memory_limit", "800M");

		$results = buscador::export($query, $usuario, $exportType, $buscarEnPapelera, isset($_GET["all"]) );
		if( isset($searchExport) ) return $results;
		//dump($results);
		exit;
	}
	
	require_xhr();
	
	$busquedas = array();

	if( $result = buscador::get($query, $usuario, true, $buscarEnPapelera, isset($_GET["all"]) ) ){
		//dump($result);exit;
		// Si tenemos buscador global, tenemos que limitar los datos
		if (isset($_GET["all"])) {
			foreach($result as $item){
				$datosItem = array();

				$datosItem["lineas"] = $item->getTableInfo($usuario, NULL);
				$datosItem["options"] = $item->getAvailableOptions( $usuario, true );

				if( $item instanceof solicitable ){
					$inlineEmpresas = array("img" => RESOURCES_DOMAIN . "/img/famfam/sitemap_color_inverse.png" );
					$empresasSuperiores = $item->getCompanies(null);
					foreach( $empresasSuperiores as $i => $empresaSuperior ){
						if( $i < 3 ){
							$inlineEmpresas[] = array( 
								"nombre" => $empresaSuperior->getUserVisibleName(),
								"oid" => $empresaSuperior->getUID(),
								"tipo" => "empresa"
							);
						} else {
							$rest = count($empresasSuperiores)-3;
							$inlineEmpresas[] = array(
								"nombre" => "+ $rest", "title" => implode(",", $empresasSuperiores->getNames())
							);
							break;
						}
					}

					$datosItem["inline"] = array($inlineEmpresas);
				}

				$busquedas[] = $datosItem;
			}

		} else {
			$extraData = array(Ilistable::DATA_SEARCH => $query);
			if ($comefrom = obtener_comefrom_seleccionado()) {
				$extraData[Ilistable::DATA_COMEFROM] = $comefrom;
			}

			$busquedas = $result->toArrayData($usuario, NULL, $extraData);
		}
	}



	/*
	 * MOSTRAR POR PANTALLA
	 */
	
	$total = 10;
	$template = Plantilla::singleton();
	$json = new jsonAGD();
	$json->establecerTipo("data");
	if (isset($result->pagination)){
		$json->addPagination($result->pagination);
	}
	
	$json->nombreTabla("buscar");

	// -------------- Funciones de la parte izquierda
	$accionesRapidas = config::obtenerOpciones(null, "buscador", $usuario, true, 0, 3);
	if (is_array($accionesRapidas) && count($accionesRapidas)) {
		foreach ($accionesRapidas as $accion) {
			$accion = (isset($accion["options"])) ? $accion["options"][0] : $accion; //si es ie no tiene opciones, es directo
			$href = $accion["href"] . get_concat_char($accion["href"]) . "q=" . urlencode(base64_encode($query));
			$json->acciones($accion["innerHTML"], $accion["img"], $href, "box-it");
		}
	}
	
	if( isset($_GET["src"]) && $_GET["src"] == "qr" && count($result) == 1 ){
		$data = reset($busquedas[0]["lineas"]);
		$href = $data["nombre"]["href"];
		$href .= "&src=qr";
		$json->addData("open", $href);
	}

	if( isset($_GET["req"]) && is_numeric($_GET["req"]) ){
		$solicitud = new empresasolicitud($_GET["req"]);
		if ($solicitud->exists() && $solicitud->getState() === solicitud::ESTADO_CREADA) {
			$json->addData("open", $solicitud->getURL());
		}
	}

	$json->element("options", "button", array(
		"innerHTML" => $template("buscar"), 'className' => 'btn refresh', "img" => RESOURCES_DOMAIN . "/img/famfam/arrow_rotate_anticlockwise.png" 
	));
	$json->element("options", "button", array(
		"innerHTML" => $template("opt_ver_papelera"), 'className' => 'btn searchtoggle pulsar', 'target' => 'papelera', "img" => RESOURCES_DOMAIN . "/img/famfam/bin.png" 
	));

	if( $usuario->esStaff() ){
		$json->element("options", "button", array('innerHTML' => $template("ver_todo"), 'className' => 'btn searchtoggle pulsar', 'target' => 'all', "img" => RESOURCES_DOMAIN . "/img/famfam/world.png" ));
	}

	if( isset($result->asyncTable) ){
		$json->addData("asyncTable", $result->asyncTable );
	}

	$typesInResult = array();
	//------ TIPOS DE LOS QUE MOSTRAREMOS OPCIONES MULTIPLES
	$availableTypesForMultipleActions = array("agrupador");

	if (isset($result->types)) {
		$typesInResult = $result->types;

		// Añadimos las opciones multiples de los objetos resultantes...
		foreach( $typesInResult as $tipo ){
			if( in_array($tipo, $availableTypesForMultipleActions) ){
				$accionesLinea = config::obtenerOpciones(null, $tipo, $usuario, false, 0, 2);
				foreach( $accionesLinea as $accion ){
					$cncat = get_concat_char($accion["href"]);
					$class = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
					$json->element("options", "button", array(
						'innerHTML' => $accion["alias"], 'class' => $class, 'href' => $accion["href"] . $cncat . "m=" . $tipo, "img" => $accion["icono"], 'data-gravity' => 'w') 
					);	
				}
			}
		}

		if (count($typesInResult) === 1 && $typesInResult[0] === 'empleado') {
			$userCompany = $usuario->getCompany();

			$json->element("options", "button", array(
				"img" => RESOURCES_DOMAIN . '/img/qr.png',
				"innerHTML" => "QRcode",
				"className" => "multiple-action continue",
				"confirm-all" => $template('continuar') . "?",
				"href" => "/qr.php?poid={$userCompany->getUID()}&q=". urlencode($query),
				"target" => "#async-frame",
				"title" => $template('generar_carnets')
			));
		}

		if (count($typesInResult) === 1 && $typesInResult[0] === 'empresa') {
			$allow = $usuario->accesoModulo("empresa_documento") || $usuario->accesoModulo("empleado_documento") || $usuario->accesoModulo("maquina_documento");

			if ($allow) {
				$json->element("options", "button", array(
					"img" => RESOURCES_DOMAIN . '/img/famfam/text_list_bullets.png',
					"innerHTML" => "Resumen cumplimentacion",
					"className" => "multiple-action continue",
					"href" => "/agd/busqueda/requestsummary.php?q=". urlencode($query),
					"target" => "#async-frame",
					"title" => $template('download_requests_summary')
				));
			}
		}
	}

	
	//--------- Para mostrar claramente al usuario donde se encuentra
	$json->informacionNavegacion($template("buscar"));
	$json->datos($busquedas);
	$json->addData("cachetime", 8000); // 8 segundos
	$json->display();