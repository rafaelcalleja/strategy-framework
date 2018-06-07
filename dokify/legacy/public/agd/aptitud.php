<?php
	require_once("../api.php");
	$template = new Plantilla();
	$modulo = obtener_modulo_seleccionado();

	//--------- COMPROBAMOS ACCESO AL MODULO
	$datosAccesoModulo = $usuario->accesoModulo($modulo);
	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}
	
	$elementoActual = new $modulo( obtener_uid_seleccionado() );
	$empresaUsuario = $usuario->getCompany();

	if (isset($_REQUEST["send"])) {
		$suitable = (int)(isset($_REQUEST['suitable']) && $_REQUEST['suitable'] == 'on');
		$elementoSuitable = $empresaUsuario->setSuitableItem($elementoActual, $suitable);
		if ($elementoSuitable === true) {
			$template->display( "succes_form.tpl" );
			exit;
		} else {
			$template->assign("message", $template->getString('error_set_item_corp'));
			$template->display( "error.tpl" );
			exit;
		}	
		
	}

	$nombreElemento = $elementoActual->getUserVisibleName();
	$moduloNombre = $template->getString($modulo);
	$template->assign('title', sprintf($template->getString('title_aptitud'),$moduloNombre, $nombreElemento));
	$template->assign('return', '../agd/ficha.php?m='.$modulo.'&poid='.$elementoActual->getUID().'&type=modal');
	if ($empresaUsuario->canSetSuitableItem($elementoActual)) {
		$template->assign('suitable', $empresaUsuario->isSuitableItem($elementoActual));
		$template->assign('message', sprintf($template->getString('mensaje_aptitud_elemento'), $nombreElemento, $nombreElemento));		
	} else $template->assign('error', $template->getString('error_set_item_corp'));	
	

	$template->display('aptitud.tpl');