<?php
	require_once("../../../api.php");

	//----- BUSCAMOS EL ID SELECCIONADO
	$idSeleccionado = obtener_uid_seleccionado();
	if( !is_numeric($idSeleccionado) ){ exit; }

	//----- INSTANCIAMOS EL LOG Y AL USUARIO
	$log = log::singleton();


	//INSTANCIAMOS EL ELEMENTO SELECCIONADO DESDE UN ATRIBUTO
	$campo = new campo( obtener_uid_seleccionado() );



	//----- DEFINIMOS EL EVENTO PARA EL LOG
	$log->info($campo->getModuleName(), "modificar campo", $campo->getUserVisibleName() );



	//----- INTANCIA DE LA PLANTILLA
	$template = Plantilla::singleton();


	if( isset($_REQUEST["send"]) ){
		$update = $campo->updateWithRequest();

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


	$template->assign ("titulo","titulo_modificar");
	$template->assign ("boton","boton_modificar");
	$template->assign ("elemento", $campo);
	$template->display("form.tpl");
?>
