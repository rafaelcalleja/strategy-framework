<?php
	set_time_limit(10);
	/* LISTADO DOCUMENTOS DE UN ELEMENTO */
	include( "../api.php");

	$modulo = obtener_modulo_seleccionado();
	$comefrom = obtener_comefrom_seleccionado();

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = new Plantilla();



	//INSTANCIAMOS LA EMPRESA DE EL USUARIO
	$elementoActual = new $modulo( obtener_uid_seleccionado() );

	if( !$elementoActual instanceof solicitable ) die("Error: Modulo no especificado!");	


	if( !$usuario->accesoElemento( $elementoActual ) || !$usuario->accesoModulo("$modulo"."_documento") ){ die("Inaccesible"); }
	$elementoActual->setUser($usuario);


	$json = new jsonAGD();
	$isViewFiltered = false;
	$filterWithDocs = (isset($_GET["doc"])) ? true : false;


	$tabs = array();
	$tabs[] = array(
		"className" => (!$comefrom)?"selected":null,
		"innerHTML" => $template("solicitudes"),
		"title"		=> $template("documentos_tienes_cargar"),
		//"count" => 1,
		"img" => RESOURCES_DOMAIN . "/img/famfam/arrow_join.png",
		"href" => "#documentos.php?m={$modulo}&poid={$elementoActual->getUID()}"
	);

	$IdsEmpresas = ( $elementoActual instanceof empresa ) ? $elementoActual->getUID() : $elementoActual->getCompanies()->toIntList();

	$isExternal = $usuario->configValue('externo');

	if ($usuario->getCompany()->getStartIntList()->match($IdsEmpresas) && $isExternal === false) {
		$tabs[] = array(
			"className" => ($comefrom=="descargables")?"selected":null,
			"innerHTML" => $template("descargables"),
			"title"		=> $template("documentos_descargables"),
			//"count" => 1,
			"img" => RESOURCES_DOMAIN . "/img/famfam/arrow_join_reverse.png",
			"href" => "#documentos.php?m={$modulo}&poid={$elementoActual->getUID()}&comefrom=descargables"
		);
	}

	if ($isExternal === false) {
		$tabs[] = array(
			"className" => ($comefrom=="files")?"selected":null,
			"innerHTML" => $template("mis_archivos"),
			"title"		=> $template("otros_ficheros_cargar"),
			//"count" => 1,
			"img" => RESOURCES_DOMAIN . "/img/famfam/folder_user.png",
			"href" => "#documentos.php?m={$modulo}&poid={$elementoActual->getUID()}&comefrom=files"
		);
	}

	$informacionDocumentos = $elementoActual->informacionDocumentos($usuario, 0, true, true);

	if (!$filterWithDocs) {
		// Preguntamos si tiene documentos de certificacion y si hay alguno pendiente de revisar (caducado, anulado, sin-anexar o anexado)
		if( count($informacionDocumentos) > 0  ){

			$pendientes = $elementoActual->obtenerSolicitudDocumentos($usuario, array("!estado" => documento::ESTADO_VALIDADO, "certificacion" => 1) );

			$url = "#documentos.php?m={$modulo}&poid={$elementoActual->getUID()}&comefrom=certificacion";
				$tabs[] = array(
					"className" => ($comefrom=="certificacion")?"selected":null,
					"innerHTML" => $template("certificacion"),
					"title"		=> $template("certificacion_cargar"),
					"count" => (count($pendientes)>0)?count($pendientes):null,
					"img" => RESOURCES_DOMAIN . "/img/famfam/page_white_text.png",
					"href" => "#documentos.php?m={$modulo}&poid={$elementoActual->getUID()}&comefrom=certificacion"
					);

			if (obtener_comefrom_seleccionado() != "certificacion" && count($pendientes)>0) {
				$json->addInfoLine("<br /><div class='message highlight'> <a href='$url'><strong>". $template("documentos_habilitacion_por_revisar") .". ". $template("click_aqui_ver") ."</strong></a></div><br /><br />", 0);
			}
		}

		$json->addDataTabs($tabs);
	}	


	//--------- Para mostrar claramente al usuario donde se encuentra
	//--------- Mostrar empresa, si no la vemos ya.
	if ( obtener_modulo_seleccionado() == "empresa" ) {
		$infoEmpresa = false;
		$nombreVisibleElemento = $elementoActual->getUserVisibleName();
	} else {
		$empresaContexto = $elementoActual->obtenerEmpresaContexto();

		if( $empresaContexto instanceof empresa ){
			if( $usuario->accesoElemento($empresaContexto) ){
				$infoEmpresa = array(
					"innerHTML" => $empresaContexto->getUserVisibleName(),
					"href" => $empresaContexto->obtenerUrlFicha(),
					"img" => $empresaContexto->getStatusImage($usuario),
					"className" => "box-it"
				);
			} else {
				$infoEmpresa = $empresaContexto->getUserVisibleName();
			}
		}
		$nombreVisibleElemento = $elementoActual->getUserVisibleName();

		if( obtener_modulo_seleccionado() == "empleado" ){
			$datos = $elementoActual->getInfo();
			$nombreVisibleElemento = string_truncate($nombreVisibleElemento, 17); 
			if ($usuario->accesoAccionConcreta(8,10,'','dni')) {
				$nombreVisibleElemento .= "(". $datos["dni"] .")";
			}
		}
	}



	$json->informacionNavegacion(
		"inicio",
		// ---- Si no es empresa, a cual pertenece...
		//$infoEmpresa,
		// ---- Modulo actual
		//$template->getString($modulo."_plural"), 
		// ---- Objecto Actual
		array( "innerHTML" => $nombreVisibleElemento, "href" => $elementoActual->obtenerUrlFicha(), "title" => $elementoActual->getUserVisibleName(), "img" => $elementoActual->getStatusImage($usuario), "className" => "box-it" )
	);




	switch($comefrom){
		default:
			// Esto actualizará la solicitud de documentos cada vez que se visite, desde otro lugar la página de documentos 
			/** NO DEBERÍA SER NECESARIO COMPROBAR ESTO AQUI, DEBERÍAMOS TENER LA GARANTIA DE QUE ESTAN CORRECTAMENTE ACTUALIZADOS 
			if( ( isset($_SERVER["HTTP_X_LAST_PAGE"]) && $_SERVER["HTTP_X_LAST_PAGE"] && $_SERVER["HTTP_X_LAST_PAGE"] != basename(__FILE__) ) || !isset($_SERVER["HTTP_X_LAST_PAGE"]) ){
				session_write_close();
				if( !$elementoActual->actualizarSolicitudDocumentos($usuario) ){
					die("Error en la comprobacion de los documentos..");
				}
			}**/


			/**
			  * Vamos a comprobar si tiene algun documento de certificación para redirigir la página
			  */




			//se guardaran los datos de todas las empresas
			$datosDocumentos = $filtroDocumentos = $todasEtiquetasActual = array();
				/* FILTROS DEFINIDOS */
				if( isset($_GET["empresa"]) ){
					$filtroDocumentos["uid_empresa_propietaria"] = $_GET["empresa"] ;
				}
				if( isset($_GET["estado"]) ){
					$filtroDocumentos["estado"] = $_GET["estado"];
				}
				if( isset($_GET["relsource"]) ){
					// $filtroDocumentos["rebote"] = $_GET["relsource"];
					$filtroDocumentos["rebote"] = $_GET["relsource"];
				}

				/** LIMITAR POR ETIQUTAS **/
				if( isset($_GET["etiqueta"]) ){
					$filtroDocumentos["uid_etiqueta"] =  $_GET["etiqueta"];
					//PENDIENTE
				}
				/* Limitar la visibilidad de los datos por agrupadores
				 * para hacer una vista de los datos mas comoda  */
				if( isset($_GET["stype"]) && is_numeric($_GET["stype"]) && isset($_GET["solicitante"]) && is_numeric($_GET["solicitante"]) ){
					$filtroDocumentos["uid_modulo_origen"] =  $_GET["stype"];
					$filtroDocumentos["uid_elemento_origen"] =  $_GET["solicitante"];
				}

				/** LIMITAR POR OBLIGATORIEDAD **/
				if( isset($_GET["obligatoriedad"]) ){
					$filtroDocumentos["obligatorio"] = $_GET["obligatoriedad"];
				}

				/** Mostrar un doc en concreto **/
				if (isset($_GET["doc"])) {
					$filtroDocumentos["uid_documento"] = $_GET["doc"];
				}

			if (is_mobile_device()) {
				$filtroDocumentos["estado"] = array(0, 3, 4);
			}

			// Marcamos la variable a TRUE por si nos interesa conocer este dato
			if( count($filtroDocumentos) ) $isViewFiltered = true;
			//buscamos los uid_documento que se le piden a esta empresa, en modo subida (0)
			$arrayIdDocumentos = $elementoActual->getDocumentsId(0, null, false, $filtroDocumentos, false, $filterWithDocs?null:false);
			//dump($arrayIdDocumentos);
			//Numero de documentos que se le muestran a la empresa
			$numeroTotalDocumentos = count($arrayIdDocumentos);

			$docsPerPage = is_mobile_device() ? $numeroTotalDocumentos : 60;
			//datos de la paginacion, en este caso no restamos nada a la paginacion
			$datosPaginacion = preparePagination($docsPerPage, $numeroTotalDocumentos, 0 );
			$arrayIdDocumentos = $elementoActual->getDocumentsId(0, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), false, $filtroDocumentos, false, $filterWithDocs?null:false);
			//informacion para facilitarle la vida al usuario al seleccionar filtros
			//$limitarPorEtiqueta = $usuario->configValue("limiteetiqueta");

			//----- Datos extra para poder ejecutar operaciones en el objeto
			$extraData = array("filtro" => $filtroDocumentos );

			//recorremos cada empresa para ver sus datos
			foreach( $arrayIdDocumentos as $idElemento ){
				$lineaDocumento = array();

				$documento = new documento($idElemento, $elementoActual);
				$datosDocumentoActual = $documento->getTableInfo($usuario, $elementoActual, $extraData);

				//----- Opciones disponibles para este elemento con este usuario
				$opciones = $documento->getAvailableOptions( $usuario, true );


				//----- DEFINIMOS LA SALIDA POR PANTALLA DE ESTA LINEA
				$inline = $documento->getInlineArray($usuario, true, $extraData);
				if( $inline === false ){ continue; }


				$lineaDocumento["inline"] = $inline;
				$lineaDocumento["lineas"] = $datosDocumentoActual;
				$lineaDocumento["options"] = $opciones;
				//if( $usuario->esSATI() || $usuario->esAdministrador() ){
				//	$lineaDocumento["className"] = "drop-area";
				//}
				// Almacenar esta linea en el array de todas las lineas de la pantalla
				$datosDocumentos[] = $lineaDocumento;		
			}  








			/* 
			 * Procesamos las posibles etiquetas que
			 * nos encontremos en esta página, para crear el filtro
		 	 * solo con las que existan
			 *
			 */
			//$todasEtiquetasActual = array_multiple_unique( $todasEtiquetasActual );
			$etiquetasJson = array();
			$todasEtiquetasActual = $elementoActual->getDocumentsEtiquetas(0, null, null, false, $filtroDocumentos);
			if( count($todasEtiquetasActual) ){
				$etiquetasJson =  array('options' => array(
					0 => array('innerHTML' => $template->getString("filtrar_por")." ".$template->getString("etiquetas")."..."  ),
					1 => array('innerHTML' => $template->getString("no_filtrar"), 'rel' => 'etiqueta', 'name' => 'null', 'class' => 'filter ucase' )
				));
				foreach( $todasEtiquetasActual as $etiqueta ){
					$etiquetasJson['options'][] = array('innerHTML' => $etiqueta->getUserVisibleName(), 'rel' => 'etiqueta', 'name' => $etiqueta->getUID(), 'class' => 'filter ucase');
				}
			}
	

			/*
			 * Creamos el filtro de solicitantes, solo
			 * con los que actualemente se ven en esta página
		 	 *
			 */
			$solicitantesFiltro = $elementoActual->getDocumentsSolicitantes(0, null, null, false, $filtroDocumentos);
			$solicitantesJson = array();
			if( count($solicitantesFiltro)>1 ){
				$solicitantesJson = array('options' => array(
					0 => array('innerHTML' => $template->getString("filtrar_por")." ".$template->getString("solicitantes")."..."  ),
					1 => array('innerHTML' => $template->getString("no_filtrar"), 'rel' => 'stype;solicitante', 'name' => 'null;null', 'class' => 'filter ucase' )
				));
				foreach($solicitantesFiltro as $solicitante){	
					$tipo = ( $solicitante instanceof empresa ) ? $solicitante->getType() : $solicitante->getTypeString();

					$html = $solicitante->getUserVisibleName()." ($tipo)";
					$solicitantesJson['options'][] = array(
						'innerHTML' => $html, 
						'rel' => 'stype;solicitante', 
						'name' => $solicitante->getModuleId().";".$solicitante->getUID(), 
						'class' => 'filter ucase'
					);	
				}
			}


			/*
			 * Creamos el filtro de clientes
			 * con los que actualemente se ven en esta página
		 	 *
			 */
			$empresasFiltro = $elementoActual->getDocumentsEmpresas(0, null, false, $filtroDocumentos);
			$empresasJson = array();
			if( count($empresasFiltro) > 1 ){
				$empresasJson = array('options' => array(
					0 => array('innerHTML' => $template->getString("filtrar_por")." ".$template->getString("empresas")."..."  ),
					1 => array('innerHTML' => $template->getString("no_filtrar"), 'rel' => 'empresa', 'name' => 'null', 'class' => 'filter ucase' )
				));

				foreach($empresasFiltro as $empresa){
					$html = $empresa->getUserVisibleName();
					$empresasJson['options'][] = array(
						'innerHTML' => $html, 
						'rel' => 'empresa', 
						'name' => $empresa->getUID(), 
						'class' => 'filter ucase'
					);
				}
			}


			$link = "https://support.dokify.net/entries/23817488";
			$img = "<img src='". RESOURCES_DOMAIN ."/img/famfam/information.png' />";
			$text = $template('texto_ayuda_documentos');
			$infoHTML = "{$img} <a href='{$link}' target='_blank'>$text</a>";
			$json->addInfoLine(array(
				'innerHTML' => $infoHTML,
				'className' => 'tip'
			));
			

			$selectsOptions = new ArrayObjectList();

			$selectsOptions['estado'] =	array( 'options' 	=> array(
				0 => array('innerHTML' => $template->getString("filtrar_por")." ".$template->getString("estados")."..." ),
				1 => array('innerHTML' => $template->getString('no_filtrar'), 	'name' => 'null', 	'class' => 'filter', 'rel' => 'estado'),
				2 => array('innerHTML' => $template->getString('sin_ningun_documento'),	'name' => '0', 		'class' => 'filter', 'rel' => 'estado'),
				3 => array('innerHTML' => $template->getString('con_documentos_pendientes'), 		'name' => '1', 		'class' => 'filter', 'rel' => 'estado'),
				4 => array('innerHTML' => $template->getString('con_documentos_validos'), 	'name' => '2', 		'class' => 'filter', 'rel' => 'estado'),
				5 => array('innerHTML' => $template->getString('con_documentos_caducados'), 	'name' => '3', 		'class' => 'filter', 'rel' => 'estado'),
				6 => array('innerHTML' => $template->getString('con_documentos_anulados'), 	'name' => '4', 		'class' => 'filter', 'rel' => 'estado')
				)
			);

			$selectsOptions['obligatorio'] = array( 'options' 	=> array(
				0 => array('innerHTML' => $template->getString("filtrar_por")." ".$template->getString("obligatorio")."..." ),
				1 => array('innerHTML' => $template->getString('no_filtrar'), 	'name' => 'null', 	'class' => 'filter', 'rel' => 'obligatoriedad'),
				2 => array('innerHTML' => $template->getString('obligatorio'),	'name' => '1', 		'class' => 'filter', 'rel' => 'obligatoriedad'),
				3 => array('innerHTML' => $template->getString('opcional'),	'name' => '0', 		'class' => 'filter', 'rel' => 'obligatoriedad')
				)
			);


			if( count($etiquetasJson) ) $selectsOptions['uid_etiqueta'] = $etiquetasJson;
			if( count($solicitantesJson) ) $selectsOptions['solicitantes'] = $solicitantesJson;
			if( count($empresasJson) ) $selectsOptions['uid_empresa_propietaria'] = $empresasJson;

			// array donde guardamos los select bien formados si hay hay filtros aplicados
			$auxCambios = array();

			foreach ($selectsOptions as $nameOption => $selectOptions) {
				// Hay filtros que son diferentes al standar y hay que tratar a parte, como por ejemplo solicitantes
				switch ($nameOption) {
					case 'solicitantes':
						if (isset($filtroDocumentos["uid_modulo_origen"]) && isset($filtroDocumentos["uid_elemento_origen"])) {
							foreach ($selectOptions['options'] as $key => $value) {
								if (isset($value['name'])) {
									$compValor = explode(';',$value['name']);
									if (count($compValor)==2 && $compValor[0]==$filtroDocumentos["uid_modulo_origen"] && $compValor[1]==$filtroDocumentos["uid_elemento_origen"]) {
										unset($selectOptions['options'][0]);
										unset($selectOptions['options'][$key]);
										array_unshift($selectOptions['options'], $value);
										$auxCambios[$nameOption] = $selectOptions;
										continue;
									}	
								}
								
							}							
						}
						break;
					
					default:
						if (isset($filtroDocumentos[$nameOption])) {
							foreach ($selectOptions['options'] as $key => $value) {
								if (isset($value['name']) && $value['name']==$filtroDocumentos[$nameOption]) {
									unset($selectOptions['options'][0]);
									unset($selectOptions['options'][$key]);
									array_unshift($selectOptions['options'], $value);
									$auxCambios[$nameOption] = $selectOptions;
									continue;
								}
							}
						}
						break;
				}
			}

			// aplicamos los cambios pertinentes al array donde se guardan los selects
			if (count($auxCambios)) {
				foreach ($auxCambios as $name => $value) {
					unset($selectsOptions[$name]);
					$selectsOptions[$name] = $value;
				}
			}

			$json->element("options", "select", $selectsOptions['estado']);
			$json->element("options", "select", $selectsOptions['obligatorio']);
			if( count($etiquetasJson) ) $json->element("options", "select", $selectsOptions['uid_etiqueta']);
			if( count($solicitantesJson) )$json->element("options", "select", $selectsOptions['solicitantes']);
			if( count($empresasJson) ) $json->element("options", "select", $selectsOptions['uid_empresa_propietaria']);

			//$json->acciones( $template("opt_crear_nuevo"), RESOURCES_DOMAIN."/img/48x48/iface/boxadd.png", "configurar/asistentedocumentofromitem.php?m={$modulo}&poid={$elementoActual->getUID()}", "box-it");
			$accionesRapidas = config::obtenerOpciones(null, $modulo."_documento", $usuario, false, 0, 3);
			if( is_array($accionesRapidas) && count($accionesRapidas) ){
				foreach( $accionesRapidas as $accion ){
					$href = $accion["href"] . "&o=" .obtener_uid_seleccionado() . "&poid=" .obtener_uid_seleccionado();
					$json->acciones( $accion["alias"],	$accion["icono"],$href, "box-it iframe");
				}
			}



			//------------ MOSTRAR DATOS EN MENU DE NAVEGACIÓN: ESTADOS Y ETIQUETAS
			$estadoDOCS = null;
			if( isset($_REQUEST["estado"]) && is_numeric($_REQUEST["estado"]) ){
				$estadoDOCS = $template->getString("estado").": ".documento::status2String($_REQUEST["estado"]);
			}
			$estadoETIQUETA = null;
			if( isset($_REQUEST["etiqueta"]) && is_numeric($_REQUEST["etiqueta"]) ){
				$etiqueta = new etiqueta($_REQUEST["etiqueta"]);
				if( $etiqueta->exists() ){
					$estadoETIQUETA = $template->getString("etiqueta").": ".$etiqueta->getUserVisibleName();
				}
			}
			$estadoRELACION = null;
			if( isset($_REQUEST["relsource"]) && is_numeric($_REQUEST["relsource"]) ){
				$agrupador = new agrupador($_REQUEST["relsource"]);
				if( $usuario->accesoElemento($agrupador) ){
					$estadoRELACION = $agrupador->getUserVisibleName();
				}
			}
	
			$json->nombreTabla( "anexo_$modulo"."-".$elementoActual->getUID() );

			$nofilterURL = "#documentos.php?m=" . $elementoActual->getModuleName() . "&poid=" . $elementoActual->getUID();


			$json->informacionNavegacion(
				// ---- Actualemente viendo documentos
				array( "innerHTML" => $template->getString("documentos"), "href" => $nofilterURL ), 
				$estadoDOCS,
				$estadoETIQUETA,
				$estadoRELACION
			);
		// FIN DEFAULT CASE
		break;
		case "files":
			$json->informacionNavegacion(
				$template->getString("mis_archivos")
			);


			$accionesLinea = $usuario->getOptionsMultipleFor("mis_archivos", 0, $elementoActual);
			if ($accionesLinea) {
				foreach( $accionesLinea as $accion ){
					$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$modulo}&poid={$elementoActual->getUID()}";
					$class = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
					$json->element("options", "button", array(
						'innerHTML' => $accion["innerHTML"], 'class' => $class, 'href' => $accion["href"], "img" => $accion["img"]) 
					);	
				}
			}
			


			//--------- Acciones
			$accionesRapidas = $usuario->getOptionsFastFor("mis_archivos", 0, $elementoActual);
			if ($accionesRapidas) {
				foreach( $accionesRapidas as $accion ){
					$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m={$modulo}&poid={$elementoActual->getUID()}";
					$class = ( $accion["href"][0] == "#" ) ? "" : "box-it";
					$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], $class);
				}
			}

			$carpetas = $elementoActual->obtenerCarpetas(false, 0, $usuario);
			$datosDocumentos = $carpetas->toArrayData($usuario);

			if ( $datosDocumentos ) {
				$numeroTotalDocumentos = count($datosDocumentos);
			} else $numeroTotalDocumentos = 0;
			

			$datosPaginacion = preparePagination(15, $numeroTotalDocumentos, 0);


			$json->nombreTabla( "mis_archivos_$modulo"."-".$elementoActual->getUID() );
		break;
		// FIN FILES CASE

		case "descargables":

		$json->informacionNavegacion(
				$template("descargables")
			);

		if( !$modulo = obtener_modulo_seleccionado() ){
			die("Error: Modulo no especificado!");
		}

		$elementoActual = new $modulo( obtener_uid_seleccionado() );	

		$extradata = array(
				Ilistable::DATA_CONTEXT => Ilistable::DATA_CONTEXT_DESCARGABLES,
				Ilistable:: DATA_ELEMENT => $elementoActual
				);	

		$datosBusqueda = new extendedArray();
		$numAgrupaciones = 0;
		
		if (isset($_REQUEST["rel"])) {
			$rel = $_REQUEST["rel"];

			if (!$filter = elemento::factory($rel)) die('error!');

			$documentos = $elementoActual->obtenerBusquedaDocumentosDescargables($filter, $usuario);

			$extradata[Ilistable::DATA_PARENT] = $elementoActual;

			$rows = $documentos->toArrayData($usuario, 0, $extradata, false);

			unset($datosBusqueda);
		} elseif (isset($_REQUEST["q"])) {

			$busqueda = utf8_decode(urldecode( $_REQUEST["q"] ));

			$datosBusqueda = array("query" => utf8_encode($busqueda));

			$documentos = $elementoActual->obtenerBusquedaDocumentosDescargables($busqueda, $usuario);

			$extradata[Ilistable::DATA_PARENT] = $elementoActual;

			$rows = $documentos->toArrayData($usuario, 0, $extradata, false);
			
		} else {
			$coleccionEmpresas = $elementoActual->getEmpresasAsignadosConDescargables($usuario);

			$coleccionAgrupadores = $elementoActual->getAgrupadoresAsignadosConDescargables($usuario);

			$groupEmpresas = array(array( 
   				"group" => $template("opt_empresas")
    		));

    		$groupAgrupadores = array(array( 
   				"group" => $template("agrupadores")
    		));
		
			$rowsEmpresas = $coleccionEmpresas->toArrayData($usuario, 0 ,$extradata , false );
			$rowsAgrupadores = $coleccionAgrupadores->toArrayData($usuario, 0 ,$extradata , false );

			$rows = array();

			if (count($rowsAgrupadores) > 0) {
				$numAgrupaciones++;
				$rows = array_merge((array)$groupAgrupadores, (array)$rowsAgrupadores);
			}

			if (count($rowsEmpresas) > 0) {
				$numAgrupaciones++;
				$rows = array_merge((array)$rows,(array)$groupEmpresas, (array)$rowsEmpresas);
			}

		}

		$datosDocumentos = new extendedArray();

		$datosDocumentos = $datosDocumentos->merge($rows);

		$json->nombreTabla( "descargables_$modulo"."-".$elementoActual->getUID() );

		$datosPaginacion = preparePagination( count($rows)-$numAgrupaciones, count($rows)-$numAgrupaciones );

		break;
		// FIN DESCARGABLES CASE		

		case "certificacion":

		$datosDocumentos = new extendedArray();
		
		$json->informacionNavegacion(
				$template("certificacion_contratación")
			);

		if( !$modulo = obtener_modulo_seleccionado() ){
			die("Error: Modulo no especificado!");
		}
		
		
		$json->nombreTabla( "certificacion_$modulo"."-".$elementoActual->getUID() );

		
		$group = 0; // inicializar variable

		// CERTIFICACION - Documentos solicitados
		$arrayDocumentos = $elementoActual->getDocuments(0, null/*array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"])*/, false, false, true );
		foreach( $arrayDocumentos as $i => $documento ){
			if( $i === 0 ){
				if( $group === 1 ) $group++; //Si ya hay un valor entonces sumamos doble para que se situe correctamente
				$group++; //dump("Contando el grupo de los documentos");
				//$datosDocumentos[] = array( "group" => $template("certificacion_contratación") );
			}

			$lineaDocumento = array();

			$lineaDocumento["lineas"] = $documento->getInfo( true, false, $usuario );
			unset($lineaDocumento["lineas"][ $documento->getUID() ]["flags"]); //------- no nos interesan los flags aqui
			unset($lineaDocumento["lineas"][ $documento->getUID() ]["keyword"]); //------- no nos interesan los keywords aqui
			unset($lineaDocumento["lineas"][ $documento->getUID() ]["custom_id"]); //------- no nos interesan los custom_id aqui

			$data = array();

			$data["filtro"] = array("certificacion" => 1);
			$inline = $documento->getInlineArray($usuario, true, $data);
			if( $inline === false ){ continue; }
			$lineaDocumento["inline"] = $inline;


			$lineaDocumento["options"] = $documento->getAvailableOptions( $usuario, true );
		
			$datosDocumentos[] = $lineaDocumento;
		}

		$anexados = $elementoActual->obtenerSolicitudDocumentos($usuario, array("estado" => documento::ESTADO_ANEXADO, "certificacion" => 1) );

		$pendientes = $elementoActual->obtenerSolicitudDocumentos($usuario, array("!estado" => documento::ESTADO_VALIDADO, "certificacion" => 1) );

		if ($pendientes && count($pendientes) > 0){
			if( $anexados && count($anexados) == count($pendientes) && count($anexados) > 0){
				$string = $template("espera_validar_documentos");
				$class = "succes";
			} else {
				$string = $template("aviso_documentacion_necesaria");
				$class = "highlight";
			}
			$json->addInfoLine("<br /><div class='message $class'> <strong>$string</strong></div><br /><br />", $group );
		}
	
		$datosPaginacion = preparePagination( 15, count($datosDocumentos), 0 );

		break;
		// FIN CERTIFICACION CASE	
	}









	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json->establecerTipo("data");
	$json->addPagination( $datosPaginacion );


	$modulo = ( isset($_REQUEST["menu"]) && trim($_REQUEST["menu"]) ) ? $_REQUEST["menu"] :  $modulo;
	$json->menuSeleccionado($modulo);


	if( !$comefrom && $isViewFiltered === false && ($action = $usuario->accesoAccionConcreta($elementoActual, 20)) && !@$pendientes){ // accion asignar
		$json->ifNoData(array(
			'innerHTML' => $template('configurar_asignaciones_continuar'),
			'href'		=> $action['href'] .'&poid='. $elementoActual->getUID() .'&return=3'
		));
	}

	$json->addPubli($usuario);

	// No queremos cache si no hay resultados
	if( !count($datosDocumentos) ){
		$json->addData("cachetime", 100); // 0,1 segundos
	}

	//-------- Agregar los datos y mostrar por pantalla
	$json->datos($datosDocumentos);
	if (isset($datosBusqueda)) $json->busqueda($datosBusqueda);

	$json->display();
