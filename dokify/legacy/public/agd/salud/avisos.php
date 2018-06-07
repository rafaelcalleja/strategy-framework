<?php
	/* LISTADO DE AVISOS PARA CITAS MEDICAS */

	include( "../../api.php");

	$template = Plantilla::singleton();

	if( !$usuario->accesoAccionConcreta("citamedica","Avisos")){ die("Inaccesible"); }

	if($uid = obtener_uid_seleccionado()){
		$citamedica = new citamedica($uid);
	} else{
		die("Inaccesible");
	}

	if( isset($_REQUEST["aviso"]) ){
		session_write_close();
		if( $data = $citamedica->sendEmailInfo($usuario, false, $_REQUEST["aviso"]) ){
			if( !is_traversable($data) && !count($data) ){
				$data = "envio_email_error";
			} 
		} else { $data = "envio_email_error"; }

		$template->assign("results_prefix", "envio_email_correcto");
		$template->assign("data", $data);
		$template->display("htmlinside_iframe.tpl");
		exit;			
	}
	
	if( $citamedica->obtenerEmpleado()->estaDeBaja() ){
		$template->assign("error", "error_empleado_baja");
	}

	$avisos = $citamedica->obtenerAvisos($usuario);
	$template->assign("title","desc_enviar_aviso_citamedica");
	$template->assign("elementos", $avisos);
	$template->display("listaacciones.tpl");
?>
