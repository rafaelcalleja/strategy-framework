<?php
	/* -----------
		LISTADO DE USUARIOS
	----------- */
	include( "../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();

	//--------- Se guardaran los datos de todas las empresas
	$datosMaquinas = array();


	if( !$idEmpresaSeleccionada = obtener_uid_seleccionado() ){ //--------- Empresa que queremos ver
		$idEmpresaSeleccionada = $usuario->getCompany()->getUID();
	}

	//--------- Instanciamos nuestra empresa
	$empresaActual = new empresa( $idEmpresaSeleccionada, true);
	$corp = $empresaActual->esCorporacion() ? true : false;


	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("empresa","listado maquinas",$empresaActual->getUserVisibleName());

	//--------- COMPROBAMOS EL ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("maquina");
	if( !is_array($datosAccesoModulo) ){$log->resultado("error acceso modulo", true); exit;}


	//--------- COMPROBAMOS QUE HAY PERMISO PARA VER LA EMPRESA SELECCIONADA
	//--------- QUEREMOS VER NUESTRA PROPIA EMPRESA O UNA SUBCONTRATA
	if( !$usuario->accesoElemento($empresaActual) && !$usuario->isViewFilterByGroups() ){$log->resultado("error acceso maquinas", true); die("Inaccesible"); }

	// Es posible que ciertos empleados no se vean por que estan ocultos para el cliente actual
	if( $usuario->esStaff() && $empresaActual->compareTo($usuario->getCompany()) ){
		$maquinasOcultas = $empresaActual->elementosOcultos($usuario, "maquina", true);
	}


	//--------- Numero de total de elementos
	$numeroTotalMaquinas = count( $empresaActual->obtenerIdMaquinas(false, false, $usuario) );
	//--------- Datos de la paginacion
	$datosPaginacion = preparePagination( 10, $numeroTotalMaquinas );


	//--------- Buscamos los usuarios
	$coleccionMaquinas = $empresaActual->obtenerMaquinas(false, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), $usuario);

	
	//--------- Recorremos el listado
	foreach( $coleccionMaquinas as $maquina ){
		//--------- Si efectivamente es un objeto empleado
		if( $maquina instanceof maquina ){
			//--------- array que almacenara los datos de el usuario actual
			$datosMaquina = array();

			//--------- concatenamos los valores en el array general | solicitamos la informacion en modo public "true"
			$informacionMaquina = $maquina->getTableInfo( );



			//--------- hay valores publicos que no se necesitan mostrar en la tabla
			$datosMaquina["lineas"] = $informacionMaquina;
			$datosMaquina["inline"] = $maquina->getInlineArray($usuario);
			$datosMaquina["options"] = $maquina->getAvailableOptions( $usuario, true );

			// ---- Informacion de documentos, filtrado por usuario, documentos de subida y obligatorios
			//$informacionDocumentos = $maquina->obtenerEstadoDocumentos($usuario, 0, true);
			//$datosMaquina["estado"] = ( count($informacionDocumentos) == 1 && isset($informacionDocumentos[2]) ) ? true : false;
			$datosMaquina["lineas"]["className"] = $maquina->getLineClass($empresaActual, $usuario);



			//--------- Guardamos los datos de esta maquina
			$datosMaquinas[] = $datosMaquina;
		}
	}


	if( $corp ){
		$empresasCorporacion = $empresaActual->obtenerContratasConMaquinas();
		if( count($empresasCorporacion) && $empresasCorporacion instanceof ArrayObjectList ){
			$data = $empresasCorporacion->toArrayData($usuario, false, array(Ilistable::DATA_CONTEXT => Ilistable::DATA_CONTEXT_LIST_MAQUINA), false);
			$datosMaquinas[] = array( "group" => $template("empresas_del_grupo") );

			$datosMaquinas = array_merge($datosMaquinas, $data);
		}
	}






	/* -------------------------------
	 *
	 * DESDE AQUI NO HAY MAS "NEGOCIO"
	 * DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR 
	 *
	 * -------------------------------
	 */


	$json = new jsonAGD();
	$json->establecerTipo("data");
	$json->nombreTabla("maquina");
	$json->addPagination( $datosPaginacion );

	if( isset($maquinasOcultas) && $num = $maquinasOcultas ){
		$infoHTML = "Hay $num maquinas ocultas";
		if( $usuario->esStaff() ){
			$infoHTML.=". Click <a class='toggle-param' href='forcevisible=true'>aqui</a> para ver todas";
		}
		$json->addInfoLine($infoHTML);
	}


	$accionesRapidas = $usuario->getOptionsFastFor("maquina", 0, $empresaActual);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) . "poid=". $empresaActual->getUID();
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor("maquina", 0, $empresaActual);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m=maquina";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}

	// Exportaciones de datos
	$exportaciones = $empresaActual->obtenerExportaciones("maquina");
	if( count($exportaciones) && is_array($exportaciones) && !$usuario->isAgent()){
		$select = array("options" => array(array('img' => RESOURCES_DOMAIN . "/img/famfam/page_white_excel.png", 'innerHTML' => $template->getString("exportaciones") )));
		foreach( $exportaciones as $exportacion ){
			$select["options"][] = array( "img" => $exportacion->getIcon(), "innerHTML" => $exportacion->getUserVisibleName(), "href" => $exportacion->getURL($empresaActual), "target" => "#async-frame", "className" => "multiple-action continue");
		}
		$json->element("options", "select", $select );	
	}


	//$json->acciones( "Crear nueva maquina", 				"boxadd", 		"maquina/nueva.php", 			"box-it");
	$json->informacionNavegacion(
		// ---- Inicio
		$template->getString("inicio"), 
		array( "innerHTML" => $empresaActual->getUserVisibleName(), "href" => $empresaActual->obtenerUrlPreferida(), "img" => $empresaActual->getStatusImage($usuario) ), 
		$template->getString("maquinas")
	);
	$json->menuSeleccionado( "maquina" );

	//--------- Agregar al objeto los datos y sacar por pantalla
	$json->datos( $datosMaquinas );
	$json->addPubli($usuario);
	$log->resultado("ok", true);
	$json->display();

?>
