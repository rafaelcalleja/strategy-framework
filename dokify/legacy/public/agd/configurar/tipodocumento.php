<?php
	/* LISTADO DE TIPOS DE DOCUMENTO*/
	include( "../../api.php");

	if( !$usuario->accesoModulo("tipodocumento", 1) ){ die("Inaccesible"); }

	$log = new log(); //----- INSTANCIAMOS EL OBJETO LOG
	$template = Plantilla::singleton();

	$filtro = array( "uid_empresa" => $usuario->getCompany()->getUID() );
	if( isset($_REQUEST["all"]) && $usuario->esStaff() ){
		$filtro = false;
	}

	//----- AÑADIMOS INFORMACION MAS EXACTA AL LOG
	$log->info("tipodocumento","listado tipos","NA");

	//--------- Comprobamos el acceso global al modulo
	$datosAccesoModulo = $usuario->accesoModulo("documento_atributo",1);
	if( !is_array($datosAccesoModulo) ){ $log->resultado("error acceso modulo", true); exit;}



	$conteoTotal = config::obtenerConteoDocumentos($filtro);
	$datosPaginacion = preparePagination( 20, $conteoTotal );



	//elemento donde almacenaremos todos los documentos
	$datosDocumentos = array();

	$documentos = config::obtenerArrayDocumentos(array($datosPaginacion["sql_limit_start"],$datosPaginacion["sql_limit_end"]), $filtro);

	foreach( $documentos as $documento ){
		/*
		if( isset($idDocumentosUsados) ){
			if( !in_array($documento["uid_documento"], $idDocumentosUsados) ){ continue; }
		}
		*/
		$doc = new documento( $documento["uid_documento"] );

		//objeto donde guardaremos los datos de este documento
		$datosDocumento = array();
		
		//salvamos el uid
		$uid = $documento["uid_documento"];

		//asginamos los datos de la linea
		//$datosDocumento["lineas"] = array( $uid => $documento );
		$datosDocumento["lineas"] = $doc->getInfo(true, "table", $usuario);
		$datosDocumento["lineas"][$doc->getUID()][ "nombre" ] = array(
			"innerHTML" => $doc->getUserVisibleName(),
			"title" =>  $doc->getUserVisibleName()
		);

		$opciones = config::obtenerOpciones( $uid, "19" , $usuario, true , 1  );
		if( count($opciones) ){
			$datosDocumento["options"] = $opciones;
		}


		if( $action = $usuario->accesoAccionConcreta(19, 3, 1) ){
			if( $action["href"][0] == "#" ){
				$datosDocumento["href"] = $action["href"] . "?poid={$doc->getUID()}";
			}
		}

		//$datosDocumento["options"] = obtener_opciones_documentos($uid);

		//guardamos el objeto actual al global
		$datosDocumentos[] = $datosDocumento;

	}

	//DISEÑAMOS LA SALIDA QUE VA AL NAVEGADOR
	$json = new jsonAGD();
	$json->addPagination( $datosPaginacion );
	$accionesRapidas = config::obtenerOpciones(null, 19/*EL ID DEL MODULO TIPO_DOCUMENTO*/, $usuario, false, 1, 3);
	if(  is_array($accionesRapidas) && count($accionesRapidas) ){
		foreach( $accionesRapidas as $accion ){
			$json->acciones( $accion["alias"],	$accion["icono"],	$accion["href"], "box-it");
		}
	}

	$json->establecerTipo("data");
	$json->nombreTabla("documento-tipo");

	if($usuario->esStaff()){
		$json->element("options", "button", array('innerHTML' => $template->getString('todos_los_tipos'), 'className' => 'btn searchtoggle pulsar', 'target' => 'all' ));
	}

	$json->informacionNavegacion(
		array( "innerHTML" => $template->getString("configuracion"), "href" => "#configurar.php" ), 
		$template->getString("lista_tipos_documentos")
	);

	//$json->acciones( "Dar de alta un nuevo tipo",	"boxadd" , 	"configurar/documento/nuevo.php", 		"box-it");
	$json->datos( $datosDocumentos );
	$log->resultado("ok", true);
	$json->display();

?>
