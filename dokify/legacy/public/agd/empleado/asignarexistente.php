<?php

	require __DIR__ . '/../../api.php';
	$template = new Plantilla();


	if (!$oid = @$_REQUEST["oid"]) exit;
	if (!is_numeric($oid)) die("Inaccesible");

	$comefrom 	= obtener_comefrom_seleccionado();
	$employee 	= new empleado($oid);
	$company 	= ($uid = obtener_uid_seleccionado()) ? new empresa($uid) : $usuario->getCompany();
	$companies 	= $employee->getCompanies();


	// If come from alta seguridad social
	if ($comefrom == 'altass') {
		$link = $employee->obtenerUrlFicha($employee->getUserVisibleName());
		$template->assign('textoextra', "<strong>{$link}</strong> ya aparece en tu lista de empleados");
		$template->display("succes_form.tpl");
		exit;
	}


	// if already in the list
	if ($companies->contains($company)) {
		$link = $employee->obtenerUrlFicha($employee->getUserVisibleName());
		$template->assign('message', 'Estás intentando crear un empleado que ya trabaja para ti. ' . $link);
		$template->display("error.tpl");
		exit;
	}

	// if is in out trash
	if (!count($companies) && $employee->inTrash($company) && $employee->restaurarPapelera($company, $usuario)) {
		$template->display("succes_form.tpl");
		exit;
	}


	if (isset($_REQUEST["send"])) {

		// If no other companies, only confirm
		if (count($companies) === 0) {
			if ($employee->asignarEmpresa($company)) {

				// SACAMOS LOS AGRUPAMIENTOS CON REPLICA Y VEMOS QUE ELEMENTOS TIENEN ASIGNADOS PARA ASIGNARSELOS AL EMPLEADO
				if (in_array("empleado", agrupamiento::getModulesReplicables())) {
					$agrupamientos = $company->obtenerAgrupamientosPropios([$usuario]);

					foreach ($agrupamientos as $agrupamiento) {
						if ($agrupamiento->configValue("replica_empleado")) {
							$agrupamiento->asignarAgrupamientosAsignadosConReplica($company, $employee, $usuario);
						}
					}
				}

				$template->assign('title', 'exito_titulo');
				$template->assign('succes', 'transferencia_automatica_ok');
				$template->display("succes_string.tpl");
				exit;
			} else {
				$template->assign('error', 'error_text');
			}
		} else {
			try {
				// Si no, preguntamos al propio empleado o a las empresas que lo tienen activo
				if ($status = $employee->solicitarTransferenciaEmpresa($company, $usuario)) {
					if (!is_bool($status)) {
						$template->assign('textoextra',$status);
					}

					$template->display("succes_form.tpl");
					exit;
				} else {
					throw new Exception('error_text');
				}
			} catch (Exception $e) {
				$template->assign('error', $e->getMessage());	
			}
		}
	}

	$template->assign('transferible', count($companies) === 0);
	$template->assign('companies', $companies);
	$template->assign('empleado', $employee);
	$template->display("empleado_existente.tpl");