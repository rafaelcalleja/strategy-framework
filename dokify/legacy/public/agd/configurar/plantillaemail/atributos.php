<?php
	require_once("../../../api.php");

	if( !$usuario->esSATI() && !$usuario->esAdministrador() ){ die("Inaccesible"); }

	$template = new Plantilla();

	$empresa = $usuario->getCompany();
	$plantillaemail = new plantillaemail( obtener_uid_seleccionado() );
	$atributo = $plantillaemail->obtenerPlantillaAtributo($empresa);

	if( isset($_REQUEST["send"]) ){
		$update = $atributo->updateWithRequest(false, false, $usuario);
		switch( $update ){
			case null:
				$template->assign ("error", "No se modifico nada");
			break;
			case false:
				$template->assign ("error", "Error al intentar modificar");
			break;
			default:
				$template->display("succes_form.tpl");exit;
			break;
		}
	}



	//$template->assign ("titulo","titulo_modificar_subcontrata");
	//$template->assign ("boton","boton_modificar_subcontrata");
	$template->assign ("elemento", $atributo);
	$template->display("form.tpl");
?>
