<?php
	/* LISTADO DE ATRIBUTOS DE DOCUMENTO */
	include( "../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = new log();


	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();

	$currentUIDdocumento = obtener_uid_seleccionado();

	$documentoSeleccionado = new documento( $currentUIDdocumento, true );

	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("documento","listado atributos",$documentoSeleccionado->getUserVisibleName());

	//--------- Comprobamos el acceso global al modulo
	$datosAccesoModulo = $usuario->accesoModulo("documento_atributo", 1);
	if( !is_array($datosAccesoModulo) ){ $log->resultado("error acceso modulo", true); exit;}

	$filter = array();
	$filter["uid_empresa_propietaria"] =  $usuario->getCompany()->getUID();
	if( isset($_REQUEST["all"]) && $usuario->esStaff() ){
		unset($filter["uid_empresa_propietaria"]);
	}
	//buscamos todos los atributos de documento
	//$solicitantes = $documentoSeleccionado->obtenerSolicitantes( $usuario );

	$datosDocumento = $documentoSeleccionado->verDatos($filter);
	$datosPaginacion = preparePagination( 20, count($datosDocumento) );


	$datosDocumento = $documentoSeleccionado->verDatos($filter, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]) );

	//elemento donde almacenaremos todos los documentos
	$datosDocumentos = array();
	if( is_array($datosDocumento) && count($datosDocumento) ){
		foreach( $datosDocumento as $documento ){


			$empresaPropietaria = new empresa($documento["uid_empresa_propietaria"]);
			$solicitante = $documentoSeleccionado->obtenerSolicitanteDesdeIdAtributo( $documento["uid_documento_atributo"] );


			//------------ SE PUEDE O NO SE PUEDE VER
			if( $usuario->esStaff() ){
				// No se hace ningun filtro
			} else {
				if( $solicitante instanceof empresa && $solicitante->getUID() !== $usuario->getCompany()->getUID() ){
					continue;
				} else {
					if( $solicitante instanceof agrupador && !$solicitante->accesiblePara($usuario) ){
						//dump( $solicitante );
						continue;
					}
				}
			}

			//id del atributo
			$uid = $documento["uid_documento_atributo"];
			$doc = documento::instanceFromAtribute($uid);
			$atributo = new documento_atributo($uid);



			$linea = $atributo->getTableInfo($usuario);


			if ($usuario->esStaff()) $linea[] = $documento["uid_documento_atributo"];

			// --- datos extra del atributo
			$datosDocumento["inline"] = $atributo->getInlineArray($usuario); 

			// --- Esta pagina es generica, vamos a mostrar a quien pertenece cada atributo..
			$datoCliente = array( 
				"img" => RESOURCES_DOMAIN . "/img/famfam/house.png",
				array( "nombre" => $empresaPropietaria->getUserVisibleName() ) 
			);
			array_unshift($datosDocumento["inline"], $datoCliente);


			$datosDocumento["lineas"] = $linea;

			$estado = $atributo->obtenerDato("activo");
			if ($estado == 0) {
				$datosDocumento["lineas"]["className"] = "color red";
			} else {
				$datosDocumento["lineas"]["className"] = "color green";
			}

			$datosDocumento["options"] = config::obtenerOpciones( $uid, "5" /* MODULO ATRIBUTOS DOCUMENTOS */, $usuario, true /* PUBLIC MODE */, 1 /*  MODO CONFIGURACION */ );

			//$datosDocumento["options"] = obtener_opciones_documentos($uid);
			//guardamos el objeto actual al global
			$datosDocumentos[] = $datosDocumento;
		}
	}



	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$json->addPagination( $datosPaginacion );
	$accionesRapidas = config::obtenerOpciones(null, 5, $usuario, false, 1, 3);
	foreach( $accionesRapidas as $accion ){
		$href = $accion["href"] . get_concat_char($accion["href"]) . "tipo_documento=" . obtener_uid_seleccionado() ."&step=1";
		$json->acciones( $accion["alias"],	$accion["icono"],	$href, "box-it");
	}

	$json->establecerTipo("data");
	$json->nombreTabla("documento_atributo-".$_REQUEST["poid"]);

	if($usuario->esSATI() || $usuario->esAdministrador()){
		$json->element("options", "button", array('innerHTML' => $template->getString('ver_todo'), 'className' => 'btn searchtoggle pulsar', 'target' => 'all' ));
	}

	$docName = $documentoSeleccionado->getUserVisibleName();
	$json->informacionNavegacion(
		array( "innerHTML" => $template->getString("configuracion_sistema"), "href" => "#configurar.php" ), 
		array( "innerHTML" => $template->getString("lista_tipos_documentos"), "href" => "#configurar/tipodocumento.php" ),
		array( "innerHTML" => string_truncate($docName, 80), "title" => $docName  )
	);

	//$jsonObject->addPagination( $datosPaginacion["pagina_anterior"], $datosPaginacion["pagina_siguiente"], $datosPaginacion["pagina_total"]);
	$json->datos( $datosDocumentos );
	$log->resultado("ok", true);
	$json->display();

?>
