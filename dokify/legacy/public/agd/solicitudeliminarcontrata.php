<?php

	include( "../api.php");
	$template = new Plantilla();	
	$empresaUsuario = $usuario->getCompany();
	$empresaEliminar = new empresa (@$_REQUEST["poid"]);

	if (!$empresaUsuario->obtenerEmpresasSuperiores(false, $usuario)->contains($empresaEliminar)) {
		die("Inaccesible");
	}

	if ( ($num = $empresaUsuario->numSubcontracts($empresaEliminar)) > 15 ) {
		$template->assign('alert',sprintf($template->getString("demasiadas_cadenas_activas"),$num ));
	}

	//SEND PARA TRATAR SOLICITUD
	if( isset($_REQUEST["sendrequest"]) ){
		$filter = array( 
			'type' => solicitud::TYPE_ELIMINARCONTRATA, 
			'uid_elemento' => $empresaEliminar,
			'uid_empresa_origen' => $usuario->getCompany()->getUID()
		);
		$empresaUsuario->newRequest(solicitud::TYPE_ELIMINARCONTRATA, $empresaEliminar, $usuario, $filter);	
		$template->display( "succes_form.tpl" );
		exit;
	}

	$template->assign('empresaEliminar',$empresaEliminar);
	$template->display("solicitudeliminarrelacion.tpl");	
?>