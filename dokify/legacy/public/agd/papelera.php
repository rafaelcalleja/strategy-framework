<?php

	include( "../api.php");
	if( !isset($_REQUEST["m"]) ){ exit; }
	if( !in_array($_REQUEST["m"], util::getAllModules() ) ){
		die("Error: Modulo no especificado!");
	}

	// Modulo seleccionado
	$modulo = obtener_modulo_seleccionado();
		

	//instanciamos la plantillas
	$template = new Plantilla();
	
	// Empresa actual
	if( $uid = obtener_uid_seleccionado() ){
		//por defecto objeto referecnia "empresa" sino lo que le pasemos por "ref"	
		if( $ref = obtener_referencia() ){
			$referencia = new $ref($uid);
		} else {
			$empresaActual = new empresa($uid);
		}
	} else {
		$ref = obtener_referencia();
		if( $ref == "cliente" ){
			$referencia = $usuario->getCompany();
		} else {
			die("Inaccesible");
			//$empresaActual = unserialize( $_SESSION["OBJETO_EMPRESA"] );
		}
	}


	// Nos aseguramos de que hay una empresa...
	if( !isset($empresaActual) || !$empresaActual instanceof empresa  ){
		$empresaActual = $usuario->getCompany();
	}

	if( !isset($referencia) ){ $referencia=$empresaActual; }

	

	//si se quiere restaurar
	if ( isset($_REQUEST["send"]) && isset($_REQUEST['restaurar'])) {	
		if ( in_array($_REQUEST['m'], ['empleado', 'maquina']) && $empresaActual->hasTransferPending() ) {
			if( isset($_REQUEST['action']) ){
				switch ($_REQUEST['action']) {
					case 'accept':
						$empresaActual->setTransferPending(false);
						break;
				}
			} else {
				$template->assign('mensaje', $template("transfer_pending_bulk_action"));
				$template->assign('allow_accept', false);
				$template->display("transfer_pending.tpl");
				exit;		
			}
		}

		$error = false;
		$estatus = true;
		if (isset($_REQUEST['delrel'] ) && $_REQUEST['delrel'] == '1') {
			foreach ( $_REQUEST['restaurar'] as $idElemento ) {
				$elemento = new $modulo( $idElemento );
				if ( $usuario->accesoElemento($elemento, null, null) && $elemento->inTrash($empresaActual) ) {
					// $estatus = $elemento->desasignarEmpresa( $empresaActual );
					set_time_limit(0);
					$estatus = $elemento->removeParent($empresaActual);
				}
				if ($estatus===false) { $error = true; }
			}
		} else {
			foreach ( $_REQUEST["restaurar"] as $idElemento ) {
				//BUSCAMOS EL METODO A UTILIZAR
				//$fn = config::obtenerMetodo( $modulo, "restaurar_papelera");
				$elemento = new $modulo( $idElemento );
				if( $elemento->isActivable() ){
					$estatus = $elemento->restaurarPapelera( $referencia, $usuario);
					$elemento->writeLogUI(logui::ACTION_ENABLE, 'uid_empresa:'.$referencia->getUID(), $usuario);

					if ( $estatus === false ) { $error = true; }
				}
			}
		}
		
		if ( $estatus && !$error ){
			$param = array("m" => $modulo);
			if( $ref = obtener_referencia() ){
				$param["ref"] = $ref;
			}
			if( $uid = obtener_uid_seleccionado() ){
				$param["poid"] = $uid;
			}
			$template->assign("acciones", array( array("href" => $_SERVER["PHP_SELF"]."?". http_build_query($param), "string" => "ver_papelera") ) );

			if ($usuario->getCompany()->justOutRange()) {
				$infoRange = sprintf($template->getString("new_range_license"), CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium');
				$template->assign("textoextra", $infoRange);
			}

			$template->display("succes_form.tpl");
			exit;	
		} elseif( is_string($error) ){
			$template->assign("error", $error);
		} else {
			$template->assign("error", "error_texto");
		}
	}

	//BUSCAMOS LOS ELEMENTOS QUE ESTEN ELIMINADAS ( true )
	$elementosEliminados = $referencia->obtenerElementosPapelera($usuario, obtener_modulo_seleccionado());
	
	//asignamos el array a la plantilla
	$freeCompany = false;
	if ($modulo == 'usuario' && $empresaActual->isFree()){
		$freeCompany = true;
	}

	$template->assign( "usuario", $usuario );
	$template->assign( "freeCompany", $freeCompany );
	$template->assign( "company", $empresaActual );
	$template->assign( "elemento", $referencia );
	$template->assign( "modulo", $modulo );
	$template->assign( "elementos", $elementosEliminados );

	$modulesThatCausesNewRange = solicitable::getModulesCausesNewRange();
	if (in_array($modulo, $modulesThatCausesNewRange) && $empresaActual->dueOutRange()) {
		$template->assign("notify", sprintf($template->getString("trash_company_due_new_range"), CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium'));
		$template->assign("notifyConfirm", $template->getString("confirm_company_due_new_range"));
	}

	//mostramos la plantilla
	$template->display( "verpapelera.tpl");
?>
