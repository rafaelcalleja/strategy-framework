<?php
	require_once("../api.php");

	$idSeleccionado = obtener_uid_seleccionado();
	if ( !is_numeric($idSeleccionado) || !isset($_GET["o"]) ){ exit; }

	// ---- Validamos que el modulo sea válido
	if( !in_array(obtener_modulo_seleccionado(), util::getAllModules() ) ){
		die("Error: Modulo no especificado!");
	}

	// ---- Instanciamos la plantilla
	$template = Plantilla::singleton();

	$modulo = obtener_modulo_seleccionado();
	$elementoActual = new $modulo( $_GET["o"] );
	$documento = new documento( $idSeleccionado, $elementoActual);

	if( !$usuario->accesoElemento($elementoActual) ){ 
		if( $usuario->esStaff() ) $template->display("sin_acceso_perfil.tpl");
		else die("Inaccesible");
	}	



	// ---- Parametros actuales para usar en redirecciones
	$currentParams = "?m=". $modulo. "&o=". $elementoActual->getUID()." &p=0&poid=". $idSeleccionado;


	// --- only one request
	$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;


	if( isset($_REQUEST["action"]) ){
		if( isset($_REQUEST["m"]) && isset($_REQUEST["oid"]) ){
			$documentoHistorico = new documento_historico( $_REQUEST["oid"], obtener_modulo_seleccionado() );
			$documentoHistorico->downloadFile();
			exit;
		}
	}



	if( isset($_REQUEST["send"]) ){

	}

	if (isset($_REQUEST["selected"]) && $selected = $_REQUEST["selected"]) {
		$template->assign("selected", $selected);

		if ($req === null) {
			$class = "anexo_historico_{$modulo}";
			$req = new $class($selected);
		}
	}
	

	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento);
	$template->assign("selectedRequest", $req);
	$template->display("historicodocumento.tpl");
