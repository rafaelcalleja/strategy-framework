<?php
	require_once("../api.php");
	$idSeleccionado = obtener_uid_seleccionado();
	if ( !is_numeric($idSeleccionado) || !isset($_GET["o"]) ){ exit; }

	
	$log = log::singleton();//----- INSTANCIAMOS EL OBJETO LOG
	$template = new Plantilla(); //----- INSTANCIAMOS LA PLANTILLA


	//----- BUSCAMOS NUESTRO ELEMENTO ACTUAL
	$modulo = obtener_modulo_seleccionado();
	$elementoActual = new $modulo($_GET["o"]);
	$documento = new documento( $idSeleccionado, $elementoActual);


	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($modulo, "borrar documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName() );

	//----- COMPROBAMOS QUE EL MODULO SEA VÁLIDO
	if( !$elementoActual instanceof solicitable ){
		$log->nivel(5);
		$log->resultado("modulo no valido", true);
		die("Error: Modulo no especificado!");
	}


	if( !$usuario->accesoElemento($elementoActual) ){ 
		if( $usuario->esStaff() ){ $template->display("sin_acceso_perfil.tpl"); } 
		else { die("Inaccesible"); }
	}

	// --- only one request
	$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;

	if( isset($_REQUEST["send"]) ){
		if( $uids = obtener_uids_seleccionados() ){
			$solicitudes = new ArrayObjectList(); 
			foreach($uids as $uid){
				$anexo = new anexo($uid, $elementoActual);
				$solicitudes[] = $anexo->getSolicitud();
				if( !$anexo->eliminar($usuario) ){
					$template->assign("error", "error_borrar");
				}
				
			}

			if( !$template->get_template_vars("error") ){
				$reqType = new requirementTypeRequest($solicitudes, $elementoActual);
				$reqType->saveComment("", $usuario, comment::ACTION_DELETE);
				$log->resultado("ok", true);
				$template->display( "succes_form.tpl" );
				exit;
			}

		} else {
			$template->assign("error", "sin_seleccionar");
		}
	}

	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento);
	$template->assign("selectedRequest", $req);
	$template->display("borrar.tpl");
