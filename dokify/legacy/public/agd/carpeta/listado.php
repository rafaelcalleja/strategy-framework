<?php
	include("../../api.php");

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("carpeta");
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible"); }

	$embedded = isset($_GET['embedded']);

	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();


	$modulo = obtener_modulo_seleccionado();

	// --- Instanciamos el agrupador...
	$referencia = new $modulo( obtener_uid_seleccionado() );

	// --- Comprobar acceso....
	if( !$usuario->accesoElemento($referencia) ){ die("Inaccesible"); }

	$ficheros = array();
	if( isset($_GET["show"]) && $_GET["show"] == "alarmas" && $referencia->getType() == "agrupador" ){
		$ficheros = $referencia->getAllFiles(false, true, $usuario);
	} else {
		// --- Recuperar todas las carpetas...
		if ( isset($_GET["folder"]) && is_numeric($_GET["folder"]) ) {
			$carpetaSeleccionada = new carpeta( $_GET["folder"] );

			if( isset($_GET["oid"]) ){
				$parentModule = obtener_comefrom_seleccionado();
				$elemento = new $parentModule( $_GET["oid"] );
				
				$elementos = $carpetaSeleccionada->obtenerDocumentos($elemento);
			} else {
				if ( $carpetaSeleccionada instanceof carpeta && $carpetaSeleccionada->getUID() ){
					if( !isset($_REQUEST["p"]) ){
						$carpetas = $carpetaSeleccionada->obtenerCarpetas(false, 0, $usuario);
						$ficheros = $carpetaSeleccionada->obtenerFicheros();	
					}

					if (!$embedded) {
						$totalElementos = $carpetaSeleccionada->obtenerElementosConDocumentos($usuario, "empresa", $referencia);

						//--------- Datos de la paginacion, restamos uno ya que siempre mostramos la empresa del usuario

						$datosPaginacion = preparePagination( 20, count($totalElementos), 0 );

						$elementos = $carpetaSeleccionada->obtenerElementosConDocumentos($usuario, "empresa", $referencia, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]) );
					}
				}
			}
		} elseif( isset($_GET["file"]) ){
			$ficheroHistorial = new fichero($_GET["file"]);
		} else {
			$carpetas = $referencia->obtenerCarpetas(false, 0, $usuario);
		}

	}


  	// --- Array donde almacenaremos la salida al navegador de la coleccion de carpetas, ficheros y otros elementos si es necesario...
	$datosCarpetas = $datosFicheros = $datosElementos = array();


	if (isset($elementos) && $elementos && count($elementos)) {
		
		foreach($elementos as $elemento){
			$informacionElemento = array();
			$informacionElemento["lineas"] = $elemento->getInfo(true, "folder", $usuario);
			$informacionElemento["options"]  = $elemento->getAvailableOptions( $usuario, true );

			$informacionElemento["inline"] = $elemento->getInlineArray( $usuario, true, array("comefrom" => "folder", "filtro" => $referencia) );
			if( $informacionElemento["inline"] === false ){ continue; }

			// link desplegable
			if( $elemento instanceof solicitable ){
				// --- Parametros de la url deslizance...
				$parametros = array(
					"m" => $modulo,
					"poid" => $referencia->getUID(),
					"oid" => $elemento->getUID(),
					"folder" => $carpetaSeleccionada->getUID(),
					"comefrom" => $elemento->getModuleName()
				);

				$informacionElemento["tree"] = array(
					"img" => array(
						"normal" => $elemento->getIcon(),
						"open" => $elemento->getIcon("open")
					),
					"checkbox" => true,
					"url" => $_SERVER["PHP_SELF"] . "?". http_build_query($parametros)
				);
			} elseif ( $elemento instanceof documento ){
				$informacionElemento["tree"] = array(
					"img" => array(
						"normal" => $elemento->getIcon(),
					)
				);
			}

			$datosElementos[] = $informacionElemento;
		}
		//$datosCarpetas = array_merge($datosCarpetas, $datosElementos);
		$datosCarpetas = $datosElementos;
	/*
		$coleccion = new ArrayObjectList($elementos);
		$datosCarpetas = $coleccion->toArrayData($usuario);
	/**/
	}



	if( isset($carpetas) && is_traversable($carpetas) ){
		// --- Recorremos cada carpeta para mostrar los datos...
		$coleccion = new ArrayObjectList($carpetas);
		$extradata = array(
			Ilistable::DATA_CONTEXT => Ilistable::DATA_CONTEXT_LISTADO,
			'referencia' => $referencia,
			'modulo' => $modulo
			);

		$datosCarpetas = array_merge($datosCarpetas, $coleccion->toArrayData($usuario,0,$extradata,true));
	}


	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("fichero");
	if( is_traversable($datosAccesoModulo) && is_traversable($ficheros) && count($ficheros) ){

		// --- Recorremos cada carpeta para mostrar los ficheros existentes...
		foreach($ficheros as $fichero){

			// --- Array donde almacenamos la informacion defichero actual...
			$informacionFichero = array();


			// --- Informacion de la linea actual....
			$informacionFichero["lineas"] = $fichero->getTableInfo();
			$informacionFichero["inline"] = $fichero->getInlineArray( $usuario );

			$opciones = $fichero->getAvailableOptions($usuario, true);
			if( count($opciones) ){
				$informacionFichero["options"] = $opciones;
			}

			$options = $usuario->getAvailableOptionsForModule($fichero->getModuleId(), "descargar");
			if ($accion = reset($options)) {
				// --- Parametros de la url deslizance...
				$parametros = array(
					"m" => $modulo,
					"poid" => $referencia->getUID(),
					"file" => $fichero->getUID()
				);


				$informacionFichero["tree"] = array(
					"img" => array(	"normal" => $fichero->getIcon()	),
					"checkbox" => true
				);

			}


			// --- Guardamos en el array general los datos de este fichero...
			$datosFicheros[] = $informacionFichero;
		}

		$datosCarpetas = array_merge($datosCarpetas,$datosFicheros);
	
	}



	if( isset($ficheroHistorial) ){
		$versiones = $ficheroHistorial->getVersions();

		$datosVersiones = array();
		foreach( $versiones as $version ){
			$datosVersion = array();

			$datosVersion["tree"] = array(
				"img" => array(
					"normal" => $ficheroHistorial->getIcon("historico"),					
				)
			);

			$upTime = strtotime($version->fecha);
			$datosVersion["lineas"] = array(array( "fecha" => date("d-m-Y h:i", $upTime)  ));


			$parametros = array(
				"oid" => $version->uid_fichero_archivo,
				"m" => $modulo,
				"send" => "1",
				"poid" => $ficheroHistorial->getUID()
			);


			$datosVersion["href"] = "carpeta/fichero/descargar.php?". http_build_query($parametros);
			$datosVersion["target"] = "#async-frame";
			$datosVersiones[] = $datosVersion;
		}


		$datosCarpetas = array_merge($datosCarpetas,$datosVersiones);
	}




	// --- Salida tipica en JSON
	$json = new jsonAGD();


	if( !isset($_GET["folder"]) ){

		// --------------- Mostrar la ficha del agrupador
		if( $referencia->getType() == "agrupador" ){
			$templateTable = Plantilla::singleton();
			$templateTable->assign("elemento", $referencia);

			//dump( $referencia->getPublicFields(true,'ficha-tabla',$usuario) ); exit;
			$json->addInfoLine( $templateTable->getHTML("ficha_tabla.tpl") );

			if( isset($_GET["show"]) && $_GET["show"] == "alarmas" ){
				$infoHTML = "Filtrando ficheros con alarma. Click <a class='toggle-param' href='show'>aqui</a> para ver todos";
				$json->addInfoLine($infoHTML);
			}
		}

		$accionesMultiples = $usuario->getOptionsMultipleFor("carpeta", 0, $referencia);
		if ($accionesMultiples) {
			foreach( $accionesMultiples as $accion ){
				$json->element("options", "button", array(
					'innerHTML' =>  $accion["name"], 
					'class' => 'multiple-action btn', 
					'href' => $accion["href"], 
					"img" =>  $accion["img"]
				));
			}
		}

		// -------------- Funciones de la parte izquierda config::obtenerOpciones(null, "carpeta", $usuario, false, 0, 3);
		$accionesRapidas = $usuario->getOptionsFastFor("carpeta", 0, $referencia);
		if( is_array($accionesRapidas) && count($accionesRapidas) ){
			foreach( $accionesRapidas as $accion ){
				$concat = ( strpos($accion["href"],"?") === false ) ? "?" : "&";
				$href = $accion["href"] . $concat. "m=".$modulo."&poid=" .$referencia->getUID();
				$json->acciones( $accion["innerHTML"],	$accion["img"], $href, "box-it iframe");

				if ($embedded) {
					$json->element("options", "button", array(
						'innerHTML' => $accion["innerHTML"],
						'class' => 'btn box-it',
						'href' => $href,
						"img" =>  $accion["img"]
					));
				}
			}
		}



		if( $referencia instanceof estructura ){
			//--------- Para mostrar claramente al usuario donde se encuentra
			$json->informacionNavegacion($template->getString("estructuras"), $referencia->getUserVisibleName());
		} else {
			$agrupamiento = $referencia->obtenerAgrupamientoPrimario();

			if($agrupamiento){
				//--------- Para mostrar claramente al usuario donde se encuentra
				$json->informacionNavegacion(
					$template->getString("inicio"),
					array( "innerHTML" => $agrupamiento->getUserVisibleName(), "href" => $agrupamiento->obtenerURLPreferida() ),
					array( "innerHTML" => $referencia->getUserVisibleName(), "href" => $referencia->obtenerURLPreferida() )
				);
			}
		}
	}

	if( isset($datosPaginacion) ) $json->addPagination( $datosPaginacion );

	//$nombreTabla = ( isset($_REQUEST["folder"]) ) ? "carpeta-". $referencia->getUID() ."-". $carpetaSeleccionada->getUID() : "carpeta-". $referencia->getUID();
	$json->establecerTipo("data");
	$json->nombreTabla("carpeta");
	$json->datos( $datosCarpetas );
	$json->display();
?>
