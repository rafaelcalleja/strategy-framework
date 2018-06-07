<?php
	/* CREAR UN NUEVO EMPLEADO */

	include( "../../api.php");
	$template = new Plantilla();

	if( !$usuario->accesoAccionConcreta(8,10, null,'dni') ){
		$template = Plantilla::singleton();
		$template->assign('title', 'error');
		$template->assign('html', 'sin_acceso_campo_dni');
		$template->display('simplebox.tpl');
		exit;
	}


	if ($uid = obtener_uid_seleccionado()) {
		$empresaActual = new empresa($uid);
	} else {
		$empresaActual = $usuario->getCompany();
	}

	$log = new log();

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
		if ( $empresaActual instanceof empresa ) {

		$campos = empleado::publicFields( elemento::PUBLIFIELDS_MODE_INIT, null, $usuario );

			if ( isset($_REQUEST["oid"]) ) {
				// el empleado existe
				$parametroEmpleado = db::scape( $_REQUEST["oid"] );
			} else {
				// creamos el empleado
				$parametroEmpleado = $_REQUEST;


				// comprobar si existe (para llegar aqui se ha debido saltar el control js)
				if (isset($parametroEmpleado["dni"]) && $exists = empleado::login($parametroEmpleado["dni"])) {
					$params = array(
						"txt" => $exists->getUserVisibleName(),
						"oid" => $exists->getUID(),
						"poid" => $empresaActual->getUID(),
						"back" => $_SERVER["PHP_SELF"] . "?poid={$empresaActual->getUID()}"
					);

					$location = "/agd/empleado/asignarexistente.php?" . http_build_query($params);
					header("Location: $location");
					exit;
				}
			}

			try{
				$nuevoEmpleado = new empleado($parametroEmpleado, $usuario);
			} catch(Exception $e){
				
				if ($empresaActual->dueOutRange()) {
					$template->assign("notify", sprintf($template->getString("company_due_new_range"), CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium'));
					$template->assign("notifyConfirm", $template->getString("confirm_company_due_new_range"));
				}

				$template->assign("error", $e->getMessage());
				$template->assign ("title", $empresaActual->getUserVisibleName() );
				$template->assign ("request", $_REQUEST);
				$template->assign ("titulo", "titulo_nuevo_empleado");
				$template->assign ("boton", "boton_nuevo_empleado");
				$template->assign ("campos", empleado::publicFields( elemento::PUBLIFIELDS_MODE_INIT, null, $usuario ));
				$template->display("form.tpl");		
				exit;
			}


			//----- DEFINIMOS EL EVENTO PARA EL LOG
			$log->info("empresa","crear empleado ". $nuevoEmpleado->getUserVisibleName(), $empresaActual->getUserVisibleName() );
			if( !$nuevoEmpleado->asignarEmpresa( $empresaActual ) ){
				if( !isset($_REQUEST["oid"]) ){
					$nuevoEmpleado->eliminar($usuario);
				}

				$log->nivel(3);
				$log->resultado("error asignar", true);
				$template->assign("error","error_asignar_empleado_empresa");
			} else {

				if( $usuario->isViewFilterByGroups() ){
					$agrupadores = $usuario->obtenerAgrupadores();
					$nuevoEmpleado->asignarAgrupadores($agrupadores->toIntList(), $usuario);
				}

				/* SACAMOS LOS AGRUPAMIENTOS CON REPLICA Y VEMOS QUE ELEMENTOS TIENEN ASIGNADOS PARA ASIGNARSELOS AL EMPLEADO */
				if( in_array("empleado", agrupamiento::getModulesReplicables())){
					$agrupamientos = $empresaActual->obtenerAgrupamientosPropios([$usuario]);
			
					if ($agrupamientos && count($agrupamientos)) {
						foreach($agrupamientos as $agrupamiento){
							if($agrupamiento->configValue("replica_empleado")){
								$agrupamiento->asignarAgrupamientosAsignadosConReplica($empresaActual, $nuevoEmpleado, $usuario);
							}
						}
					}
				}

				$log->nivel(1);
				$log->resultado("ok", true);
				$nuevoEmpleado->actualizarSolicitudDocumentos($usuario);

				$empresasCliente = $empresaActual->obtenerEmpresasCliente();
				if (count($empresasCliente)) {
					header("Location: ../asignarcliente.php?m=empleado&poid={$nuevoEmpleado->getUID()}&comefrom=nuevo");
				} else {
					$ownGroupsByModule = $empresaActual->obtenerAgrupamientosPropios(array('modulo' => $nuevoEmpleado->getModuleName(), $usuario));
					if (count($ownGroupsByModule)) {
						$response = array(
							"closebox" => true,
							"action" => array(
								"go" => "#asignacion.php?m=empleado&poid={$nuevoEmpleado->getUID()}&comefrom=nuevo&return=3"
							)
						);

						header("Content-type: application/json");
						print json_encode($response);
						exit;
					} else {
						header("Location: ../ficha.php?m=empleado&poid={$nuevoEmpleado->getUID()}&comefrom=nuevo");
					}
					
				}

				exit;
			}
			

			
		} else {
			$log->nivel(3);
			$log->resultado("error asignar", true);
			$template->assign("error","error_asignar_empleado_empresa");
		}
	}

	if ($empresaActual->dueOutRange()) {
		$template->assign("notify", sprintf($template->getString("company_due_new_range"), CURRENT_DOMAIN.'/licencias-plataforma-CAE-coordinacion-actividades-empresariales.php#/toggle/premium'));
		$template->assign("notifyConfirm", $template->getString("confirm_company_due_new_range"));
	}

	$template->assign("title", $empresaActual->getUserVisibleName());
	$template->assign("titulo","titulo_nuevo_empleado");
	$template->assign("boton","boton_nuevo_empleado");
	$template->assign ("className", "async-form");
	$template->assign("campos", empleado::publicFields(elemento::PUBLIFIELDS_MODE_INIT, null, $usuario));
	$template->display("form.tpl");
	
?>
