<?php
	include( "../api.php");

	//instanciamos la plantillas
	$template = new Plantilla();

	if( !isset($_REQUEST["o"]) || !is_numeric($_REQUEST["o"]) ){ 
		//$template->assign("objeto", $elementoActual);
		$template->end($usuario); exit;
	}

	$idSeleccionado = $_REQUEST["o"];


	//modulo activo
	$modulo = obtener_modulo_seleccionado();

	// ----- Elemento actual
	$elementoActual = new $modulo($idSeleccionado);
	if( !$usuario->accesoElemento($elementoActual) ){
		$template->end($usuario); // acabará la ejecución del script o enviará mensaje de aviso 
	}

	$documento = new documento( obtener_uid_seleccionado(), $elementoActual);
	$elementoActual->setUser($usuario);
	$template->assign( "elemento", $elementoActual);


	// --- only one request
	$req 			= (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;
	$resultString 	= array();

	// ---- Se entrará en el if si se quiere desactivar documentos
	if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "send" ){
		$atributos = array();
		$documento = new documento( obtener_uid_seleccionado(), $elementoActual);
		$solicitudes = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario, array(), null, $req);
		$itemName = $elementoActual->getUserVisibleName();

		if( isset($_REQUEST["send"]) ){
			if( $list = obtener_uids_seleccionados() ){
				foreach($list as $uid){
					$doc = new documento($uid);
					$solicitudes = $doc->obtenerSolicitudDocumentos($elementoActual, $usuario);

					foreach($solicitudes as $solicitud){
						$reqName =  str_replace('&laquo;', '>', $solicitud->getRequestString());
						$log = new log();
						$log->info($modulo, "filtrar solicitud - ". $itemName ." - ". $reqName, $solicitud->obtenerDocumentoAtributo()->getUserVisibleName() );

						if( $solicitud->enviarPapelera($elementoActual, $usuario) ){
							$log->resultado("ok", true);
							$resultString[] = "El documento {$solicitud->getUserVisibleName()} se desactivo";
						} else {
							$log->resultado("error", true);
							$resultString[] = "El documento {$solicitud->getUserVisibleName()} no se ha podido desactivar";
						}
					}
				}
				$result = array("jGrowl" => implode("<br /><br />", $resultString), "refresh" => true );
				die( json_encode($result) );

			} else {
				$solicitudes = ( isset($_REQUEST["elementos"]) && is_array($_REQUEST["elementos"]) ) ? $_REQUEST["elementos"] : array();
				foreach($solicitudes as $key => $uid){
					$solicitud = new solicituddocumento($uid);
					$reqName =  str_replace('&laquo;', '>', $solicitud->getRequestString());

					$log = new log();
					$log->info($modulo, "filtrar solicitud - ". $itemName ." - ". $reqName, $solicitud->obtenerDocumentoAtributo()->getUserVisibleName() );

					if( $solicitud->enviarPapelera($elementoActual, $usuario) ){
						$log->resultado("ok", true);
						$resultString[] = "El atributo {$solicitud->getUserVisibleName()} se desactivo";
					} else {
						$log->resultado("error", true);
						$resultString[] = "El atributo {$solicitud->getUserVisibleName()} no se ha podido desactivar";
					}
				}

				
				
				if (count($resultString)) {
					$string = implode("<br />", $resultString);
					$template->assign("succes", $string);
					$template->assign("title", "solicitantes");
					$template->display("succes_string.tpl");
					exit;
				} else {
					$solicitudes = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario, array(), null, $req);
					$totalSolicitudes = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario);
					$template->assign("elemento", $elementoActual);
					$template->assign("documento", $documento);
					$template->assign("totalSolicitudes", $totalSolicitudes);
					$template->assign("solicitudes", $solicitudes);
					$template->assign("selectedRequest", $req);
					$template->assign("title", "solicitantes");
					$template->assign("error", "must_select_requirement");
					$template->display("filtrarsolicitud.tpl");
					exit;
				}
			}
		} else {
			$totalSolicitudes = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario);

			$template->assign("elemento", $elementoActual);
			$template->assign("documento", $documento);
			$template->assign("totalSolicitudes", $totalSolicitudes);
			$template->assign("solicitudes", $solicitudes);
			$template->assign("selectedRequest", $req);
			$template->assign("title", "solicitantes");
			$template->display("filtrarsolicitud.tpl");
		}
	
		exit;
	}




	$solicitudes = $elementoActual->obtenerSolicitudDocumentos($usuario, array("papelera" => 1) );

	if( isset($_REQUEST["send"]) ){
		if( isset($_REQUEST["restaurar"]) && is_array($_REQUEST["restaurar"]) ){
			foreach( $_REQUEST["restaurar"] as $i => $uid ){
				if( in_array($uid, $solicitudes->toIntList()->getArrayCopy() ) ){
					$solicitud = new solicituddocumento($uid);
					$solicitud->restaurarPapelera($elementoActual, $usuario);
				}
			}

			$template->assign("acciones", array( array("href" => "documentopapelera.php?m={$modulo}&o={$elementoActual->getUID()}&poid={$elementoActual->getUID()}", "string" => "ver_papelera") ) );
			$template->display("succes_form.tpl");
			exit;
		} else {
			$template->assign("error", "sin_seleccionar");
		}
	}


	
	//asignamos el array a la plantilla
	$template->assign("elementos", $solicitudes);

	//mostramos la plantilla
	$template->display("verpapelera.tpl");


