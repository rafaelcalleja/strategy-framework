<?php
	require_once("../api.php");


	$template = new Plantilla();


	//Elemento y documento actual
	if( !$modulo = obtener_modulo_seleccionado() ) die("Inaccesible");
	$elementoActual = new $modulo( $_GET["o"] );
	$documento = new documento( obtener_uid_seleccionado(), $elementoActual);
	if( !$elementoActual instanceof solicitable ) die("Error: Modulo no especificado!");

	if( !$usuario->accesoElemento( $elementoActual ) && !$usuario->esValidador()){
		if( $usuario->esStaff() ){
			if( !isset($_REQUEST["inline"]) ) $template->display("sin_acceso_perfil.tpl");
		} else { 
			$template->assign("objeto", $elementoActual);
			$template->display("sin_acceso_perfil.tpl"); 
			exit;
		}
	}




	// --- only one request
	$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : NULL;

	if (is_mobile_device()) {
		$accionFirmar 	= @$usuario->getAvailableOptionsForModule($documento, "firmar")[0];
		$script 		= is_array($accionFirmar) ? "firmar" : "anexar";

		header("Location: {$script}.php?m={$modulo}&o={$elementoActual->getUID()}&poid={$documento->getUID()}");
		exit;
	}


	$template->assign("selectedRequest", $req);
	$template->assign("elemento", $elementoActual);
	$template->assign("documento", $documento);
	$template->display("infodocs.tpl");
