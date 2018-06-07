<?php
	require_once("../api.php");
	ini_set('memory_limit', '256M');

	$template = Plantilla::singleton(); //----- INSTANCIA DE LA PLANTILLA
	$log = log::singleton();	//----- INSTANCIAMOS EL LOG


	$modulo = obtener_modulo_seleccionado();
	if( !$docUID = obtener_uid_seleccionado() ){
		die("Inaccesible");
	} 


	if( !isset($_REQUEST["o"]) || !$uid = $_REQUEST["o"] ){
		die("Inaccesible");
	}
	$elementoActual = new $modulo($uid);


	//----- COMPROBACION DE MODULO
	if( !$elementoActual instanceof solicitable ){
		$log->nivel(5);
		$log->resultado("modulo no valido", true);
		die("Error: Modulo no especificado!");
	}

	if( !$usuario->accesoElemento($elementoActual) ){ 
		if( $usuario->esStaff() ){
			if( !isset($_REQUEST["action"]) && !isset($_REQUEST["send"]) && !$usuario->esValidador() ) $template->display("sin_acceso_perfil.tpl");
		} else { die("Inaccesible"); }
	}


	$documento = new documento($docUID, $elementoActual);


	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($modulo,"descargar documento ".$documento->getUserVisibleName(), $elementoActual->getUserVisibleName() );


	// --- only one request
	$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;


	if( isset($_REQUEST["action"]) ){
		if( isset($_REQUEST["oid"]) ){

			$modoDescargar = ( isset($_REQUEST["descargable"]) ) ? true : false;
			$log->resultado("ok", true);

			if( $modoDescargar ){
				$anexo = new anexo($_REQUEST["oid"], "empresa");
				$documento = $anexo->obtenerDocumentoAtributo();

				$solicitud = ( $uid = obtener_comefrom_seleccionado() ) ? new solicituddocumento($uid) : NULL;	

				
				$data = array();

				// Notify the selected context
				if (isset($_REQUEST[Ilistable::DATA_CONTEXT]) && $context = $_REQUEST[Ilistable::DATA_CONTEXT]) $data[Ilistable::DATA_CONTEXT] = $context;

                // Notify the selected request
                if (isset($_REQUEST["req"])) $data["req"] = $_REQUEST["req"];

                // Notify if it has been use from validation page
                if (true === isset($_REQUEST["calledFromValidation"])) {
                    $data["calledFromValidation"] = $_REQUEST["calledFromValidation"];
                }

				try {
    				$documento->downloadFile(false, $elementoActual, $usuario, $solicitud, $data);
				} catch(HTML2PDF_exception $e){
					die("<script>alert('{$e->getMessage()}')</script>");
				} catch(Exception $e){
					die($e->getMessage());
				}

			} else {
				$anexo = new anexo($_REQUEST["oid"], $elementoActual);

				if (isset($_REQUEST["setcookie"])) setcookie("dlfile", "1", time()+60*60*24*30, '/');
				
				$anexo->download();
			}
		}
		exit;
	}


	if( isset($_REQUEST["send"]) ){
		if( $uids = obtener_uids_seleccionados() ){
			$anexos = array();
			foreach($uids as $uid){
				$anexos[] = new anexo($uid, $elementoActual);
			}
		
			if( $documento->zipAll($anexos) ){
				$log->resultado("ok", true);
			} else {
				header('HTTP/1.1 404 Not Found');
			}
		}

		exit;
	}

	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento);
	$template->assign("selectedRequest", $req);
	$template->display("descargar.tpl");
