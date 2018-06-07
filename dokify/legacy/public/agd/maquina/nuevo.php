<?php
	include( "../../api.php");

	$template = Plantilla::singleton();


	if ($uid = obtener_uid_seleccionado()) {
		$empresaActual = new empresa($uid);
	} else {
		$empresaActual = $usuario->getCompany();
	}

	if ($empresaActual->hasTransferPending()) {
		if( isset($_REQUEST['action']) ){
			switch ($_REQUEST['action']) {
				case 'accept':
					$empresaActual->setTransferPending(false);
					break;
			}
		} else {
			$template->assign('mensaje', $template("transfer_pending_general"));
			$template->assign('allow_accept', true);
			$template->display("transfer_pending.tpl");
			exit;		
		}
	}

	if (isset($_REQUEST["send"])) {
		$pars = isset($_REQUEST["oid"]) ? $_REQUEST["oid"] : $_REQUEST ; 
		$nuevaMaquina = new maquina( $pars, $usuario );



		if( $nuevaMaquina instanceof maquina ){
			if( !$nuevaMaquina->asignarEmpresa( $empresaActual ) ){
				$template->assign("error", "elemento_existente" );
			} else {
				if( $usuario->isViewFilterByGroups() ){
					$agrupadores = $usuario->obtenerAgrupadores();
					$nuevaMaquina->asignarAgrupadores($agrupadores->toIntList(), $usuario);
				}


				if (in_array('maquina', agrupamiento::getModulesReplicables())) {
					$agrupamientos = $empresaActual->obtenerAgrupamientosPropios([$usuario]);

					foreach($agrupamientos as $agrupamiento){
						if($agrupamiento->configValue("replica_maquina")){
							$agrupamiento->asignarAgrupamientosAsignadosConReplica($empresaActual, $nuevaMaquina, $usuario);
						}
					}
				}

				//$template->display( "succes_form.tpl");
				$nuevaMaquina->actualizarSolicitudDocumentos($usuario);

				$empresasCliente = $empresaActual->obtenerEmpresasCliente();
				if (count($empresasCliente)) {
					header("Location: ../asignarcliente.php?m=maquina&poid={$nuevaMaquina->getUID()}&comefrom=nuevo");
				} else {
					$ownGroupsByModule = $empresaActual->obtenerAgrupamientosPropios(array('modulo' => $nuevaMaquina->getModuleName(), $usuario));
					if (count($ownGroupsByModule)) {
						$response = array(
							"closebox" => true,
							"action" => array(
								"go" => "#asignacion.php?m=maquina&poid={$nuevaMaquina->getUID()}&comefrom=nuevo&return=3"
							)
						);

						header("Content-type: application/json");
						print json_encode($response);
						exit;
					} else {
						header("Location: ../ficha.php?m=maquina&poid={$nuevaMaquina->getUID()}&comefrom=nuevo");
					}
					
				}

				exit;
			}
		} else {
		//si no, es un error
			$template->assign("error", $nuevaMaquina );
		}

	}


	if ($empresaActual->dueOutRange()) {
		$template->assign("notify", sprintf($template->getString("company_due_new_range"), CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium'));
		$template->assign("notifyConfirm", $template->getString("confirm_company_due_new_range"));
	}

	$template->assign("campos", maquina::publicFields(elemento::PUBLIFIELDS_MODE_INIT, null, $usuario));
	$template->assign("titulo", "nueva_maquina");
	$template->assign("boton", "crear");
	$template->assign ("className", "async-form");
	$template->display("form.tpl");
?>
