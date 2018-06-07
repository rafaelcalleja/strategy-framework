<?php
	/* EDITAR UN EMPLEADO */
	include( "../../api.php");


	if( $uid = obtener_uid_seleccionado() ){
		$empleado = new empleado($uid);
		if( $usuario->accesoElemento($empleado) ){
			$template = new Plantilla();
			$template->assign("elemento", $empleado);
			$template->display("ficha/empleado.tpl");
		}
	}
	/*
	$empleadoSeleccionado = new empleado( obtener_uid_seleccionado() );

	
	$template = new Plantilla();

	if( ($empleadoSeleccionado instanceof empleado) && isset($_REQUEST["send"]) ){
		$update = $empleadoSeleccionado->updateWithRequest(false, false, $usuario);
		switch( $update ){
			case null:
				$template->assign ("error", "No se modifico nada");
			break;
			case false:
				$template->assign ("error", "Error al intentar modificar");
			break;
			default:
				$template->display("succes_form.tpl"); exit;
			break;
		}

	}

	$template->assign ("titulo","titulo_modificar_empleado");
	$template->assign ("boton","boton_modificar_empleado");
	$template->assign ("elemento", $empleadoSeleccionado);
	$template->display("ficha/empleado.tpl");
	*/
	
?>
