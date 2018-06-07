<?php
	require_once("../api.php");

	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) || !isset($_GET["o"]) ){ exit; }
	
	$log = log::singleton(); //----- INSTANCIAMOS EL OBJETO LOG 
	$template = new Plantilla(); //----- INSTANCIAMOS LA PLANTILLA


	//----- BUSCAMOS NUESTRO ELEMENTO ACTUAL
	$modulo = obtener_modulo_seleccionado();
	$elementoActual = new $modulo( $_GET["o"] );
	$documento = new documento( $idSeleccionado, $elementoActual);

	if( !$usuario->accesoElemento($elementoActual) || !$usuario->accesoAccionConcreta(elemento::obtenerIdModulo( $modulo."_Documento" ),131) ){ 
		if( $usuario->esStaff() ){ $template->display("sin_acceso_perfil.tpl"); } 
		else { die("Inaccesible"); }
	}
	

	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($_REQUEST["m"], "revisar documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName() );

	//----- COMPROBAMOS QUE EL MODULO SEA VÁLIDO
	if( !$elementoActual instanceof solicitable ){
		$log->nivel(5);
		$log->resultado("modulo no valido", true);
		die("Error: Modulo no especificado!");
	}


	// --- only one request
	$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;


	if( isset($_REQUEST["send"]) ){
		if( $anexos = obtener_uids_seleccionados() ){
			array_walk($anexos, function(&$uid, $i, $elementoActual){
				$uid = new anexo($uid, $elementoActual);
			}, $elementoActual);

			$anexos = new ArrayObjectList($anexos);

			$succes = true;
			foreach ($anexos as $anexo) {
				$succes = $anexo->revisar($usuario) && $succes;
			}	

			if ($succes) {
				$template->display( "succes_form.tpl" ); 
				exit;
			} else $template->assign("error", "error_revisar");	

		} else {
			$template->assign("error", "sin_seleccionar");
		}
	}

	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento);
	$template->assign("selectedRequest", $req);
	$template->display("revisar.tpl");
