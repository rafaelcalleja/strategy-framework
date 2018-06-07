<?php
	require_once("../../api.php");

	$template = Plantilla::singleton();

	$usuarioSeleccionado = new usuario( obtener_uid_seleccionado() );

	if( isset($_SESSION["OBJETO_EMPRESA"]) ){
		$empresaActual = unserialize($_SESSION["OBJETO_EMPRESA"]);
		$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilEmpresa( $empresaActual );
	}

	if (!isset($perfilUsuarioSeleccionado)) {
		$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilEmpresa( $usuario->getCompany() );
		if( !$perfilUsuarioSeleccionado instanceof perfil ){
			$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilActivo( $usuario->getCompany() );
			if( !$perfilUsuarioSeleccionado instanceof perfil ){
				$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilActivo();
			}
		}
	}


	if( !$usuario->accesoElemento($perfilUsuarioSeleccionado) ){
		die( "Innaccesible" );
	}

	
	/***************
		SE CAMBIA SIEMPRE EL PERFIL ACTIVO!!! HAY QUE BUSCAR EL PERFIL CORRECTO EN FUNCION DEL CLIENTE
	***************/

	if( $usuario->getUID() == $usuarioSeleccionado->getUID() && !$usuarioSeleccionado->esStaff() ){
		$template->assign("viewonly", true );
		if( isset($_REQUEST["send"]) ){
			header("Content-type: text/plain");
			die("No no no...");
			exit;
		}
	}

	if( isset($_REQUEST["send"]) ){
		$perfil =  $usuarioSeleccionado->perfilActivo();

		$log = new log();
		$log->info("usuario", "modificar perfil " . $perfil->shortName(), $usuarioSeleccionado->getUserVisibleName() );

		$extra = $perfil->actualizarOpcionesExtra("perfil");
		if( !$extra ){ echo "Error al actualizar las opciones extra <br />"; }

		$arrayDatos = ( isset($_REQUEST["opciones"]) ) ? $_REQUEST["opciones"] : array();
		if ($usuario->comprobarAccesoOpcion($arrayDatos)) {
			if (!$perfilUsuarioSeleccionado instanceof perfil) { die("El usuario seleccionado no tiene un perfil válido para ". $empresaActual->getUserVisibleName()); }
			$totalAssigned = count($perfilUsuarioSeleccionado->obtenerOpcionesDisponibles(null));

			if ($totalAssigned != count($arrayDatos)) {
				$numActions = count($arrayDatos) - $totalAssigned;
				if ($numActions < 0) $valueLogui = logui::STRING_ACTION_REMOVE ." = '".abs($numActions)."'";
				else $valueLogui = logui::STRING_ACTION_ADD ." = '{$numActions}'";
			
				$usuarioSeleccionado->writeLogUI(logui::ACTION_SET_ACCESS_ACTIONS,  $valueLogui, $usuario);
			}

			$estadoActualizacion = $perfilUsuarioSeleccionado->actualizarOpciones($arrayDatos);
			if ($estadoActualizacion === true) {

				$canEditUserRol = count($usuario->getAvailableOptionsForModule("rol",21));

				if ($canEditUserRol) {
					$makePersitent = (bool) (isset($_REQUEST["persitente"]) && $_REQUEST["persitente"] == "on");
					if (is_numeric(@$_REQUEST["rol"]) && $makePersitent) {
						$rol = new rol($_REQUEST["rol"]);
						$oldRol = $perfilUsuarioSeleccionado->getActiveRol();
						if ($rol instanceof rol && $oldRol instanceof rol && !$rol->compareTo($oldRol)) {
							$valueLogui = logui::STRING_ACTION_OLD ." = '{$rol->getUID()}',".logui::STRING_ACTION_NEW ." = '{$oldRol->getUID()}'";
							$usuarioSeleccionado->writeLogUI(logui::ACTION_NEW_ROL, $valueLogui, $usuario);
						} elseif ($rol instanceof rol) {
							$valueLogui = logui::STRING_ACTION_NEW ." = '{$rol->getUID()}'";
							$usuarioSeleccionado->writeLogUI(logui::ACTION_NEW_ROL, $valueLogui, $usuario);
						}
						$resultadoCambio = $rol->actualizarPerfil($perfil->getUID(), $makePersitent);
					} else {
						$oldRol = $perfilUsuarioSeleccionado->getActiveRol();
						if ($oldRol instanceof rol) {
							$valueLogui = logui::STRING_ACTION_OLD ." = '{$oldRol->getUID()}'";;
							$usuarioSeleccionado->writeLogUI(logui::ACTION_NEW_ROL, $valueLogui, $usuario);
						}
						$resultadoCambio = $perfil->unlinkRol();
						if ($resultadoCambio === NULL) $resultadoCambio = true;
					}
				}
				
				$log->resultado("ok ", true);
				header("Content-type: text/plain");
				die ($template->getString("exito_titulo"));
			} else {
				$log->resultado("error ".$estadoActualizacion, true);
				header("Content-type: text/plain");
				die ($estadoActualizacion);
			}
		}
		header("Content-type: text/plain");
		die("Ocurrió un error. No se efectuó ningun cambio.");
	}	

	/* Juntamos los roles por empresa y los genericos */
	$template->assign("roles", rol::obtenerRolesGenericos() );

	$template->assign("perfil", $perfilUsuarioSeleccionado );
	$template->assign("usuario", $usuarioSeleccionado );
	$optionHTML = $template->getHTML( "editarperfil.tpl" );


	$json = new jsonAGD();
	$json->establecerTipo("simple");
	$json->nuevoSelector("#main", $optionHTML);
	$json->nombreTabla("configurar-perfil");
	$json->display();
?>
