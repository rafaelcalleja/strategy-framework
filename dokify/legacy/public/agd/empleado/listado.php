<?php
	/* -----------
		LISTADO DE EMPLEADOS
	----------- */
	include( "../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//--------- Sobre que tabla trabajamos? Esto nos sirve para refrescar rápidamente
	//$_SESSION["CURRENT_TABLE"] = "empleado";
	//--------- Creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = Plantilla::singleton();
	//--------- Se guardaran los datos de todas las empresas
	$datosElementos = array();

	$userCompany = $usuario->getCompany();


	//--------- Empresa que queremos ver
	if( $uid = obtener_uid_seleccionado() ){
		$empresaActual = new empresa($uid);
	} else {
		$empresaActual = $userCompany;
	}




	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("empresa","listado empleados",$empresaActual->getUserVisibleName());

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo("empleado");

	//--------- COMPROBAMOS QUE HAY PERMISO PARA VER LA EMPRESA SELECCIONADA
	if( !is_array($datosAccesoModulo) || ( !$usuario->accesoElemento($empresaActual) && !$usuario->isViewFilterByGroups() ) ){ $log->resultado("error acceso empleados", true); die("Inaccesible"); }

	//--------- Estamos en una corporacion?
	$corp = $empresaActual->esCorporacion() ? true : false;

	// Es posible que ciertos empleados no se vean por que estan ocultos para el cliente actual
	if( $usuario->esStaff() && !$empresaActual->compareTo($userCompany) ){
		$empleadosOcultos = $empresaActual->elementosOcultos($usuario, "empleado", true);
	}


	$organizador = $empresaActual->obtenerOrganizador();//devuelve agrupamiento

	//------ COMPROBAMOS QUE EL CLIENTE EN ESTE MODULO TIENE ACTIVO EL ORGANIZADOR Y QUE LA EMPRESA QUE VAMOS A VISUALIZAR ES EMPRESA CLIENTE
	if ($organizador instanceof agrupamiento && $userCompany->compareTo($empresaActual)) {
		//----- COMPROBAMOS SI HA SELECCIONADO ALGUN GRUPO PARA MOSTRAR LOS ELEMENTOS
		if( $uidGrupo = obtener_grupo_seleccionado() ){
			//---------- HA SELECCIONADO ALGUN GRUPO, MOSTRAMOS LOS MIEMBROS DE ESE GRUPO, APLICANDO UN FILTRO
			if( $uidGrupo == "-1" ){//---- SI EL GRUPO ES -1 SON EMPLEADOS SIN ASIGNACION DE AGRUPADOR

				$numeroTotalEmpleados = count($organizador->obtenerElementosDeAgrupadoresInactivos($usuario, $empresaActual, "empleado"));			
				$datosPaginacion = preparePagination( 10, $numeroTotalEmpleados );

				$fn = array($organizador, "obtenerElementosDeAgrupadoresInactivos");
				$pars = array($usuario, $empresaActual,"empleado", false, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]));
				$nombreGrupo = $template->getString("sin_asignar");
			} else {
				$agrupador = new agrupador( $uidGrupo );

				if(!$usuario->accesoElemento($agrupador)){$log->resultado("error acceso empleados por organizador", true);die("Inaccesible");}

				$numeroTotalEmpleados = count($agrupador->obtenerElementosAsignados("empleado", $empresaActual, false, $usuario));
				$datosPaginacion = preparePagination( 10, $numeroTotalEmpleados );

				$fn = array($agrupador, "obtenerElementosAsignados");
				$pars = array("empleado", $empresaActual, $datosPaginacion, $usuario);
				$nombreGrupo = $agrupador->getUserVisibleName();
			}

			$coleccionElementos = call_user_func_array($fn, $pars);

		} else {
			//---------- SI NO HA SELECCIONADO NINGUN GRUPO, MOSTRAMOS LOS GRUPOS DE UN AGRUPAMIENTO	
			$coleccionElementos = $organizador->obtenerAgrupadoresActivos($empresaActual,false,false,false,false,"empleado",$usuario);
			$numeroTotalEmpleados = count($coleccionElementos);

			//SI HAY ELEMENTOS SIN ASIGNAR SE CREA EL GRUPO SIN ASIGNAR Y SE LE SUMA 1 A LOS POSIBLES AGRUPADORES CON ELEMENTOS. PAGINACION
			$grupoSinAsignar = $organizador->obtenerElementosDeAgrupadoresInactivos($usuario, $empresaActual, "empleado", true);

			$numeroTotalEmpleados = ( is_array($grupoSinAsignar) ) ? $numeroTotalEmpleados+1 : $numeroTotalEmpleados ; 
			$datosPaginacion = preparePagination( 20, $numeroTotalEmpleados );

			if( is_array($grupoSinAsignar) && count($grupoSinAsignar) ){
				$datosElementos[] = $grupoSinAsignar;
			}
		}
	} else {
		//-------mostramos listado empleados sin filtro
		$numeroTotalEmpleados = $empresaActual->obtenerEmpleados(false, false, $usuario, true);
		$datosPaginacion = preparePagination( 10, $numeroTotalEmpleados );
		$coleccionElementos = $empresaActual->obtenerEmpleados( false, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), $usuario );
	}


	if( isset($coleccionElementos) && count($coleccionElementos) ){
		foreach( $coleccionElementos as $elemento ){
			//--------- Si efectivamente es un objeto empleado
			if( $elemento instanceof empleado ){
				//--------- array que almacenara los datos de el empleado actual
				$datosEmpleado = array();

				//--------- solicitamos los valores
				$datosEmpleado["lineas"] = $elemento->getTableInfo($usuario);
				$datosEmpleado["inline"] = $elemento->getInlineArray($usuario);
				$datosEmpleado["options"] = $elemento->getAvailableOptions( $usuario, true );
				$datosEmpleado["lineas"]["className"] = $elemento->getLineClass($empresaActual, $usuario);

				//--------- Guardamos los datos de este empleado en el conjunto global
				$datosElementos[] = $datosEmpleado;
				//echo( $elemento  . "<br>"); flush(); ob_flush();exit;
			} elseif( $elemento instanceof agrupador ){
				$datosGrupo = array();
				$url = "#empleado/listado.php?poid=".$empresaActual->getUID()."&g=". $elemento->getUID();

				$datosGrupo["lineas"] = $elemento->getInfo( true, "table" );
				$totalElementosGrupo = count($elemento->obtenerElementosAsignados("empleado",$empresaActual,false,$usuario));
				$datosGrupo["inline"][] = array( "img" => RESOURCES_DOMAIN."/img/famfam/group_go.png", 
												array( "nombre" => "".$totalElementosGrupo." Empleado(s)",
														"href" => $url 
													 )
											);
				$datosGrupo["href"] = $url;
				$datosGrupo["type"] = $elemento->getType();

				$datosElementos[] = $datosGrupo;			
			}
		}
	}


	if( $corp ){
		$empresasCorporacion = $empresaActual->obtenerContratasConEmpleados();
		if( count($empresasCorporacion) && $empresasCorporacion instanceof ArrayObjectList ){
			$data = $empresasCorporacion->toArrayData($usuario, false, array(Ilistable::DATA_CONTEXT => Ilistable::DATA_CONTEXT_LIST_EMPLEADO), false);
			$datosElementos[] = array( "group" => $template("empresas_del_grupo") );

			$datosElementos = array_merge($datosElementos, $data);
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
	$json->nombreTabla("empleado");
	$json->addPagination( $datosPaginacion );

	if( isset($empleadosOcultos) && ($num = $empleadosOcultos) ){
		$infoHTML = "Hay $num empleados ocultos";
		if( $usuario->esStaff() ){
			$infoHTML.=". Click <a class='toggle-param' href='forcevisible=true'>aqui</a> para ver todos";
		}
		$json->addInfoLine($infoHTML);
	}


	$accionesRapidas = $usuario->getOptionsFastFor("empleado", 0, $empresaActual);
	foreach( $accionesRapidas as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) . "poid=". $empresaActual->getUID();
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}

	$accionesLinea = $usuario->getOptionsMultipleFor("empleado", 0, $empresaActual);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m=empleado";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$accion["confirm"]  = $template->getString("are_you_sure_to_proceed");

		$json->element("options", "button", $accion);
	}

	if ($userCompany->getStartList()->contains($empresaActual) && !$empresaActual->esCorporacion()) {
		$json->element("options", "button", array(
			"img" => RESOURCES_DOMAIN . '/img/qr.png',
			"innerHTML" => "QRcode",
			"className" => "multiple-action continue",
			"confirm-all" => $template('confirmar_generar_carnets'),
			"href" => "/qr.php?poid=".$empresaActual->getUID(),
			"target" => "#async-frame",
			"title" => $template('generar_carnets')
		));
	}


	// Exportaciones de datos
	$exportaciones = $empresaActual->obtenerExportaciones("empleado");
	if( count($exportaciones) && is_array($exportaciones) && !$usuario->isAgent() ){
		$select = array( "options" => array(
			array( 'img' => RESOURCES_DOMAIN . "/img/famfam/page_white_excel.png", 'innerHTML' => $template("exportaciones") )
		));
		foreach( $exportaciones as $exportacion ){
			$select["options"][] = array( "img" => $exportacion->getIcon(), "innerHTML" => $exportacion->getUserVisibleName(), "href" => $exportacion->getURL($empresaActual), "target" => "#async-frame", "className" => "multiple-action continue");
		}
		$json->element("options", "select", $select );	
	}

	$json->informacionNavegacion(
		$template("inicio"), 
		array( "innerHTML" => $empresaActual->getUserVisibleName(), "href" => $empresaActual->obtenerUrlPreferida(), "img" => $empresaActual->getStatusImage($usuario) ), 
		$template( $organizador ? $organizador->getUserVisibleName() : "empleados" )
	);
	$json->menuSeleccionado( "empleado" );

	//--------- Agregar al objeto los datos y sacar por pantalla
	$json->datos( $datosElementos );
	$json->addPubli($usuario);
	$log->resultado("ok", true);
	$json->display();

?>
