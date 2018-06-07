<?php
	/* EDITAR UN USUARIO */
	include( "../../api.php");


	
	$usuarioSeleccionado = new usuario( $_REQUEST["poid"] );

	if (!($usuarioSeleccionado instanceof usuario)) { exit; }
	if (!$usuario->accesoAccionConcreta($usuario, 4) || ( $usuario->accesoAccionConcreta($usuario, 4) && !$usuario->getCompany()->compareTo($usuarioSeleccionado->getCompany()))) {
		die("Inaccesible");
	}

	$template = new Plantilla();
	if (isset($_REQUEST["send"])) {
		try {
			$update = $usuarioSeleccionado->update(false, false, $usuario);
			switch ($update){
				case null:
					$template->assign("info", "No se modifico nada");
				break;
				case false:
					$template->assign("message", "No se modifico nada");
					$template->display("error.tpl");
					exit;
				break;
				default:

					if (is_array($update) && isset($update["error"])){
						$template->assign("error", $update["error"]);
						break;
					}

					if (isset($_REQUEST['return']) && $return = trim($_REQUEST['return'])) {
						header("Location: $return");
						exit;
					}

					if ($address = $usuarioSeleccionado->getAddress()) {
						$coords = util::getCoordsFromAddress($address);
						if ($coords && isset($coords->latitude) && isset($coords->longitude)) {
							$usuarioSeleccionado->setLatLng($coords->latitude.",".$coords->longitude);
							$usuarioSeleccionado->updateCheckedEmployees();
						}
					}

					$template->display("succes_form.tpl");
					exit;
				break;
			}
		} catch (Exception $e) {
			$template->assign("error", $e->getMessage());
		}
	}

	// Simplificar la interfaz
	if (isset($_GET['edit']) && $edit = $_GET['edit']) {
		$campos = $usuarioSeleccionado->getPublicFields(true, usuario::PUBLIFIELDS_MODE_EDIT, $usuario);

		if (isset($campos[$edit])) {
			$reduced = new FieldList;
			$reduced[$edit] = $campos[$edit];

			$template->assign("tip", array(
				"innerHTML" => "completa_campo_{$edit}_continuar"
			));
			$template->assign("campos", $reduced);	
		}
		
	}
		
	$template->assign ("titulo","titulo_modificar_usuario");
	$template->assign ("boton","boton_modificar_usuario");
	$template->assign ("elemento", $usuarioSeleccionado);
	$template->display("form.tpl");
		
?>
