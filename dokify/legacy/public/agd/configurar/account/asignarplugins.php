<?php
	require_once("../../../api.php");

	//----- INSTANCIAMOS EL OBJETO LOG
	$log = log::singleton();

	//----- INSTANCIAMOS LA PLANTILLA
	$template = Plantilla::singleton();

	//----- COMPROBAMOS ACCESO AL MODULO
	if (!$usuario->esStaff()) {
		$log->resultado("error acceso modulo", true); exit;
	}

	if ($uid = obtener_uid_seleccionado()) {
		//----- INSTANCIAMOS AL OBJETO CLIENTE
		$empresa = new empresa($uid);

		if( isset($_REQUEST["send"]) ){
			$estado = $empresa->actualizarPlugins();
			if( $estado === true ){
				$template->assign("succes", "exito_titulo");
			} else {
				$template->assign( "error" , $estado  );
			}
		}
		$pluginsAsignados = $empresa->obtenerPlugins();
		$pluginsDisponibles = elemento::discriminarObjetos(plugin::getAll(), $pluginsAsignados);

		$template->assign( "asignados" , $pluginsAsignados  );
		$template->assign( "disponibles" , $pluginsDisponibles  );
		$template->display( "configurar/asignarsimple.tpl" );
	}
?>
