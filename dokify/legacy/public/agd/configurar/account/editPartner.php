<?php

	include( "../../../api.php");

	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) ){ exit; }

	$company = new empresa( obtener_uid_seleccionado() );

	$template = new Plantilla();
	if( isset($_REQUEST["send"]) ){
		try{
			$REQUEST = $_REQUEST;
			$update = $company->update($REQUEST, empresa::PUBLIFIELDS_MODE_PARTNER);

			switch( $update ){
				case null:
					$template->assign ("error", $template->getString("error_modificar_no_opcion_seleccionada"));
				break;
				case false:
					$template->assign ("error", $template->getString("error_modificar_opciones"));
				break;
				default:
					$template->display("succes_form.tpl");
					exit;
			}

		} catch(Exception $e) {
			$template->assign ("error", $template->getString("error_texto"));
		}
	}

	$template->assign ("comefrom",empresa::PUBLIFIELDS_MODE_PARTNER);
	$template->assign ("titulo","titulo_nuevo_partner");
	$template->assign ("boton","boton_modificar");
	$template->assign ("elemento", $company);
	$template->display("form.tpl");
	
?>
