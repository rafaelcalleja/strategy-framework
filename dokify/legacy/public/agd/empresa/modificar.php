<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../../api.php");


	$empresaSeleccionada = new empresa( obtener_uid_seleccionado() );


	if( !$usuario->accesoElemento($empresaSeleccionada) ){ die("Inaccesible"); }

	$template = new Plantilla();
	$error = false;
	if( ($empresaSeleccionada instanceof empresa) && isset($_REQUEST["send"]) ){
		if (isset($_REQUEST["cif"]) && isset($_REQUEST["uid_pais"])) {
			if ($_REQUEST["uid_pais"] == pais::SPAIN_CODE && !vat::isValidSpainVAT($_REQUEST["cif"])) {
				$template->assign ("error", "signup_form_error_cif");
				$error = true;
			}	
		}

		if (!$error) {
			try {
				$update = $empresaSeleccionada->updateWithRequest(false, false, $usuario);
				switch( $update ){
					case null:
						$template->assign ("error", "No se modifico nada");
					break;
					case false:
						$template->assign ("error", "Error al intentar modificar");
					break;
					default:

						if( isset($_REQUEST["return"]) ){
							header("Location: ". $_REQUEST["return"] ); exit;
						}

						$template->display("succes_form.tpl");exit;
					break;
				}
			} catch(Exception $e) {
				$template->assign ("error", $e->getMessage());
			}
		}
		
	}

	// Simplificar la interfaz
	if (isset($_GET['edit']) && $edit = $_GET['edit']) {
		$campos = $empresaSeleccionada->getPublicFields(true, empresa::PUBLIFIELDS_MODE_EDIT, $usuario);

		if (isset($campos[$edit])) {
			$reduced = new FieldList;
			$reduced[$edit] = $campos[$edit];

			$template->assign("tip", array(
				"innerHTML" => "completa_campo_{$edit}_continuar"
			));

			$template->assign("campos", $reduced);	
		}
		
	}


	$template->assign ("titulo","titulo_modificar_subcontrata");
	$boton = isset($_REQUEST['return']) ? "continuar" : "boton_modificar_subcontrata";
	$template->assign ("boton", $boton);
	$template->assign ("elemento", $empresaSeleccionada);
	$template->display("form.tpl");

?>
