<?php
	require_once("../../../api.php");
	set_time_limit(0);

	//----- BUSCAMOS EL ID SELECCIONADO
	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) ){ exit; }

	
	$log = log::singleton();


	//INSTANCIAMOS EL ELEMENTO SELECCIONADO DESDE UN ATRIBUTO
	$documento = new documento_atributo($idSeleccionado);

	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($documento->getModuleName(), "modificar documento ". $documento->getUserVisibleName(), $documento->getUserVisibleName());

	//----- INTANCIA DE LA PLANTILLA
	$template = Plantilla::singleton();


	if ($comefrom = obtener_comefrom_seleccionado()) {
		$constant = constant('documento_atributo::PUBLIFIELDS_MODE_'. strtoupper($comefrom));

		if ($constant) $comefrom = $constant;
		else die('Inaccesible');
	} else {
		$comefrom = documento_atributo::PUBLIFIELDS_MODE_EDIT;
	}

	if (isset($_REQUEST["send"])) {
		$data = $documento->getInfo(false);
		$recursividad = $data["recursividad"];
		$infoDoc = $documento->getInfo();
		try {
			$update = $documento->update(false, $comefrom, $usuario);
			switch ($update) {
				case null:
					$error = $template("error_modificar_no_opcion_seleccionada");
					$log->resultado("error $error", true);
					$template->assign ("error", $error);
				break;
				case false:
					$error = $template("error_modificar_opciones");
					$log->resultado("error $error", true);
					$template->assign ("error", $error);
				break;
				default:

					
					if (is_array($update)) {
						$mensaje = $template("mensaje_duracion_extra")." ".count($update["duracion"]);
					} else {
						$template->display("succes_form.tpl");exit;
					}

					$template->assign("succes", $mensaje);
				break;
			}
		} catch (Exception $e) {
			$error = $template($e->getMessage());
			$log->resultado("error $error", true);
			$template->assign ("error", $error);
		}

	}

	

				


	$template->assign ("comefrom", $comefrom);
	$template->assign ("no_wrap_description", true);
	$template->assign ("width","700px");
	$template->assign ("titulo", $documento->getUserVisibleName());
	$template->assign ("boton","boton_modificar");
	$template->assign ("elemento", $documento);
	$template->display("form.tpl");
