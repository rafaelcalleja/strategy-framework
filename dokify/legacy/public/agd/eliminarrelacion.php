<?php
	require_once("../api.php");

	//creamos la instancia de la plantilla que nos sirve para obtener las cadenas de idioma
	$template = new Plantilla();
	$modulo = obtener_modulo_seleccionado();
	$uid = obtener_uid_seleccionado();

	if( !in_array($_REQUEST["m"], util::getAllModules() ) ){ die("Error: Modulo no especificado!"); }

	
	//----- RECUPERAMOS OBJETOS
	$empresaActiva = new empresa($uid);
	$elementoActual = new $modulo( $_GET["oid"] );

	$log = new log();
	$log->info($modulo, "eliminar relacion", $elementoActual->getUserVisibleName());

	if (!$usuario->accesoElemento( $elementoActual, null, null) || !$usuario->getAvailableOptionsForModule($elementoActual->getModuleId(),29)) { 
		$log->nivel(6);
		$log->resultado("sin permiso", true);
		$template->display("erroracceso.tpl"); exit; 
	}

	//----- SI SE ENVIA EL FORMULARIO
	if( isset($_REQUEST["send"] ) ){


		if( isset($_REQUEST["confirm"]) ){
			$template->display("confirmacionborrar.tpl");
			exit;
		}

		//----- SI SE HA CONFIRMADO
		if( isset($_REQUEST["confirmed"]) ){
			if( $empresaActiva->borrarRelacionPara($elementoActual, $usuario) ){
				$elementoActual->writeLogUI(logui::ACTION_DISCONNECT, "from uid:" . $empresaActiva->getUID(), $usuario);
				$log->resultado("ok", true);
				$template->display("succes_form.tpl");
			} else {
				$log->resultado("error", true);
				$template->assign("message","error_texto");
				$template->display("error.tpl");
			}
			exit;
		}
	}
	

	$template->assign ("titulo", "borrar");
	$template->assign ("boton", "eliminar" );
	$template->display("borrarelemento.tpl");
?>
