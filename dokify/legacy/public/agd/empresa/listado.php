<?php
	/* -----------
		LISTADO DE EMPRESAS
	----------- */
	include( "../../api.php");

	$log = log::singleton();
	$template = Plantilla::singleton();
	$json = new jsonAGD();
	$datosEmpresas = array();

	//--------- INSTANCIAMOS LA EMPRESA QUE SE SOLICITA
	if( !$idEmpresaSeleccionada = obtener_uid_seleccionado() ){
		$idEmpresaSeleccionada = $usuario->getCompany()->getUID();
	}

	$empresaActual = new empresa($idEmpresaSeleccionada, true);
	$comefrom = obtener_comefrom_seleccionado();

	//--------- empresa actual, hay contratas ocultas...
	if( $usuario->esStaff() && !$empresaActual->compareTo($usuario->getCompany()) ){
		$contratasOcultas = $empresaActual->elementosOcultos($usuario, "empresa", true);
	}


	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("empresa","listado contratas",$empresaActual->getUserVisibleName());

	//--------- Comprobamos el acceso global al modulo
	$datosAccesoModulo = $usuario->accesoModulo("empresa");

	//--------- COMPROBAMOS QUE HAY PERMISO PARA VER LA EMPRESA SELECCIONADA
	//--------- QUEREMOS VER NUESTRA PROPIA EMPRESA O UNA SUBCONTRATA
	if( !is_array($datosAccesoModulo) || ( !$usuario->accesoElemento($empresaActual) && !$usuario->isViewFilterByGroups() ) ) { 
		$log->resultado("error acceso empresa", true); 
		die("Inaccesible"); 
	}

	$empresa = $usuario->getCompany();
	$pendingInvitations = array();

	$isSelf = $empresa->compareTo($empresaActual) || ($empresa->esCorporacion() && $empresa->obtenerEmpresasInferiores()->contains($empresaActual));
	if ($isSelf){
		$pendingInvitations = $empresaActual->getPendingInvitations();
	}

	if (!count($pendingInvitations) || !is_traversable($pendingInvitations)){
		$comefrom = "empresa";
	}
	
	switch($comefrom){

		case "invitacion":

			foreach( $pendingInvitations as $invitation ){
					
					$pendingInvitation["lineas"] = $invitation->getTableInfo($usuario);
					$pendingInvitation["inline"] = $invitation->getInlineArray($usuario);
					$pendingInvitation["options"] = $invitation->getAvailableOptions($usuario, true);
					$datosEmpresas[] = $pendingInvitation;
				
				}
			
		break;
		default:

			$distancia = empresa::DEFAULT_DISTANCIA;
			if( $empresa->esCorporacion() ){
				$distancia++;
			}

			$verContratas = $usuario->accesoAccionConcreta(1, 19); // acceso al modulo de empresas, ver subcontratas
			if( !$verContratas || $empresaActual->obtenerDistancia($usuario->getCompany(), false) > $distancia ){
				$coleccionEmpresas = array( $empresaActual );
			} else {
				//--------- Numero de empresas inferiores
				$numeroTotalEmpresas = $empresaActual->obtenerIdEmpresasInferiores(false, false, $usuario, 0, true );
				//--------- Datos de la paginacion, restamos uno ya que siempre mostramos la empresa del usuario
				$datosPaginacion = preparePagination( 10, $numeroTotalEmpresas, 0 );
				//--------- buscamos todas las empresas inmediatamente inferiores
				$coleccionEmpresas = $empresaActual->obtenerEmpresasInferiores( false, array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), $usuario);
			}

			//--------- recorremos cada empresa para ver sus datos
			foreach( $coleccionEmpresas as $empresaInferior ){
				//--------- Si efectivamente es un objeto empresa
				if ($empresaInferior instanceof empresa) {


					//--------- array que almacenara los datos de la empresa actual
					$datosEmpresaInferior = array();


					//--------- concatenamos los valores en el array general | solicitamos la informacion en modo public "true"
					$datosEmpresaInferior["lineas"] = $empresaInferior->getTableInfo($usuario, $empresaActual);
					if( $usuario->esSATI() ){
						$datosEmpresaInferior["lineas"][ $empresaInferior->getUID() ]["uid"] = $empresaInferior->getUID();
					}

					//--------- mas columnas, ahora en formato dinámico
					$datosEmpresaInferior["inline"] = $empresaInferior->getInlineArray( $usuario );
					$datosEmpresaInferior["options"] = $empresaInferior->getAvailableOptions( $usuario, true );
					
					if( $class = $empresaInferior->getLineClass($empresaActual, $usuario) ){
						$datosEmpresaInferior["lineas"]["className"] = $class;
					}
					$datosEmpresas[] = $datosEmpresaInferior;

				}
			}

			if (isset($datosPaginacion)) $json->addPagination($datosPaginacion);

		break;
	}

	
	$json->establecerTipo("data");
	$json->nombreTabla("empresa");
	
	$json->addHelpers( $usuario );
	
	if( isset($contratasOcultas) && ($num = $contratasOcultas) ){
		$infoHTML = "Hay $num contratas ocultas";
		
		if( $usuario->esStaff() ){
			$infoHTML.=". Click <a class='toggle-param' href='forcevisible=true'>aqui</a> para ver todas";
		}
		$json->addInfoLine($infoHTML);
	}

	/*
		PARA NO HACER OTRA FUNCIÓN QUE HAGA LO MISMO UTILIZAMOS LA CALSE CONFIG
			PASAREMOS EL ID DEL OBJETO, EN ESTE CASO SON OPCIONES GENERALES ASIQUE NULL
			EL MODULO
			EL USUARIO ACTUVO
			MODO PUBLIC, ES FALSE, DEBIDO A QUE SIRVE PARA CONCATENAR EL ID PUBLICO
			CONFIGURACIÓN 0, ES DECIR ACCIONES NORMALES
			TIPO 3, ACCIONES RÁPIDAS
	*/

	$options = array();
	$accionesRapidas = $usuario->getOptionsFastFor("empresa", 0, $empresaActual);
	foreach( $accionesRapidas as $accion ){
		if ((int)$accion["uid_accion"] ===  22 && $empresaActual->esCorporacion() && $usuario->esStaff() === false) {
			continue;
		}
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) . "poid=". $empresaActual->getUID();
		$options[$accion["uid_accion"]] = $accion;
		$json->acciones( $accion["innerHTML"],	$accion["img"], $accion["href"], "box-it");
	}


	$accionesLinea = $usuario->getOptionsMultipleFor("empresa", 0, $empresaActual);
	foreach( $accionesLinea as $accion ){
		$accion["href"] = $accion["href"] . get_concat_char($accion["href"]) ."m=empresa";
		$accion["class"] = ( trim($accion["class"]) ) ? $accion["class"] : 'multiple-action btn';
		$json->element("options", "button", $accion);
	}


	if (count($pendingInvitations) && is_traversable($pendingInvitations) && $usuario->accesoAccionConcreta(1, 22)){

		$tabs = array();

		$tabs[] = array(
			"className" => (!$comefrom)?"selected":null,
			"innerHTML" => $template->getString('empresas'),
			"title"		=> $template->getString('empresas'),
			"img" => RESOURCES_DOMAIN . "/img/32x32/iface/empresa.png",
			"href" => "#empresa/listado.php"
			
		);

		$tabs[] = array(
			"className" => ($comefrom=="invitacion")?"selected":null,
			"innerHTML" => $template->getString('invitaciones'),
			"title"		=> $template->getString('invitaciones'),
			"img" => RESOURCES_DOMAIN . "/img/famfam/table_go.png",
			"count" => (count($pendingInvitations)>0)?count($pendingInvitations):null,
			"href" => "#empresa/listado.php?comefrom=invitacion&poid=".$empresaActual->getUID()
		);

		$json->addDataTabs($tabs);

	}
	
	// Exportaciones de datos
	$exportaciones = $empresaActual->obtenerExportaciones("empresa");
	if( count($exportaciones) && is_array($exportaciones) && !$usuario->isAgent()){
		$select = array( "options" => array(
			array( 'img' => RESOURCES_DOMAIN . "/img/famfam/page_white_excel.png", 'innerHTML' => $template->getString("exportaciones") )
		));
		foreach( $exportaciones as $exportacion ){
			$select["options"][] = array( "img" => $exportacion->getIcon(), "innerHTML" => $exportacion->getUserVisibleName(), "href" => $exportacion->getURL($empresaActual), "target" => "#async-frame", "className" => "multiple-action continue");
		}
		$json->element("options", "select", $select );	
	}


	//--------- Para mostrar claramente al usuario donde se encuentra
	//dump("--->".$empresaActual->getUserVisibleName()."<-----------");
	$json->informacionNavegacion($template->getString("inicio"), $empresaActual->getUserVisibleName(), $template->getString("empresas"));
	$json->menuSeleccionado( "empresa" );

	//--------- Agregar al objeto los datos y sacar por pantalla
	$json->datos( $datosEmpresas );


	if ($isSelf && isset($options[22]) && $option = $options[22]) { // puede este usuario crear empresas?
		$json->ifNoData(array(
			'innerHTML' => $template('sin_contratas_todavia'),
			'href'		=> $option['href'],
			'class' 	=> 'box-it'
		));
	}

	$log->resultado("ok", true);
	$json->addPubli($usuario);
	$json->display();

?>
