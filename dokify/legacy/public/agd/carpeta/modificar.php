<?php
	/*MENSAJE DE INICIO DE AGD*/
	include( "../../api.php");

	$modulo = obtener_modulo_seleccionado();

	$elemento = new $modulo( obtener_uid_seleccionado() );

	$template = new Plantilla();

	if( is_numeric($elemento->getUID()) && isset($_REQUEST["send"]) ){

		$update = $elemento->updateWithRequest(false, false, $usuario);

		if ($elemento->getModuleName()=='carpeta') {
			$elemento->indexar();
		}
		
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
	$template->assign ("elemento", $elemento);
	$template->display("form.tpl");

?>
