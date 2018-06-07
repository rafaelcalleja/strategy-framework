<?php
	
	$empresaUsuario = $usuario->getCompany();
	$empresaEliminar = $elementoSeleccionado;
	$template->assign('alert',sprintf($template->getString("demasiadas_cadenas_activas"),$empresaEliminar->numSubcontracts($empresaUsuario) ));

	if (isset($_REQUEST["elementos"]) && is_array($_REQUEST["elementos"]) && $items = new ArrayIntList($_REQUEST["elementos"])) {
		// Ahora mismo esto solo se usa para empresas... pero deberiamos usar un método estatico, o similar
		$parentItems = $items->toObjectList('empresa');
		$empresaUsuario = reset($parentItems);
		$template->assign('elementos',$empresaUsuario);
	}
	
	if ($empresaUsuario->esCorporacion()) {
		$empresaUsuario = reset($parentItems);
	}
	//SEND PARA TRATAR SOLICITUD
	if( isset($_REQUEST["sendrequest"]) ){
		$filter = array( 
			'type' => solicitud::TYPE_ELIMINARCLIENTE, 
			'uid_elemento' => $empresaEliminar,
			'uid_empresa_origen' => $empresaUsuario->getUID()
		);
		$empresaUsuario->newRequest(solicitud::TYPE_ELIMINARCLIENTE, $empresaEliminar, $usuario, $filter, $empresaUsuario);	
		$template->display( "succes_form.tpl" );
		exit;
	}

	$template->assign('empresaEliminar',$empresaEliminar);
	$template->display("solicitudeliminarrelacion.tpl");	
?>