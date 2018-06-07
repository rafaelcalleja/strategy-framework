<?php
	require_once("../../../api.php");

	//--------- Comprobamos el acceso global al modulo
	$datosAccesoModulo = $usuario->accesoModulo("perfil",1);

	if( !is_array($datosAccesoModulo) ){ die("Inaccesible");}
	
	$template = new Plantilla();
	$perfil = new perfil( obtener_uid_seleccionado() );

	//if( !$usuario->accesoElemento($perfil) ) { die("Inaccesible"); }	

	/* IF USADO PARA APLICAR LAS CARACTERISTICAS DE UN ROL DE CLIENTE A UN PERFIL DE USUARIO 
	if( isset($_REQUEST["action"]) ){
		$makePersitent = ( isset($_REQUEST["persitente"]) && $_REQUEST["persitente"] == "on" ) ? true : false;

		// COMPROBACION DE ACCESO AL MODULO Y DESPUES A LA ACCION EN MODO NORMAL
		if( isset($_GET["rol"]) && count($usuario->getAvailableOptionsForModule("rol",21,0)) ){

			$log = new log();


			if( is_numeric($_REQUEST["rol"]) ){
				$rol = new rol( $_REQUEST["rol"], false);
				$log->info("usuario", "aplicar rol " . $rol->getUserVisibleName() . " - ". $perfil->shortName(), $perfil->getUserName() );
				$resultadoCambio = $rol->actualizarPerfil($perfil->getUID(), $makePersitent);
			} else {
				$log->info("usuario", "quitar rol ", $perfil->getUserName() . " - ". $perfil->shortName() );
				$resultadoCambio = $perfil->unlinkRol();
				if( $resultadoCambio === NULL ){ $resultadoCambio = true; }
			}

			if( $resultadoCambio === true) {
				$log->resultado("ok ", true);
				die( $template->display("succes_form.tpl") );
			} else {
				$log->resultado("error ".$resultadoCambio, true);
				$template->assign("message", "error_texto" );
				die( $template->display("error.tpl") );	
			}
			

		}
		exit;
	}*/

	if( isset($_REQUEST["send"]) ){
		header("Content-type: text/plain");

		$log = new log();
		$log->info("usuario", "modificar perfil " . $perfil->shortName(), $perfil->getUserName() );

		$makePersitent = ( isset($_REQUEST["persitente"]) && $_REQUEST["persitente"] == "on" ) ? true : false;
		$rol = @$_REQUEST["rol"];
		$arrayDatos = (isset($_REQUEST["opciones"])) ? $_REQUEST["opciones"] : array();


		if (!$perfil->actualizarOpcionesExtra("perfil")) { 
			$log->resultado("error opciones extra", true);
			echo "Error al actualizar las opciones extra <br />"; 
		}
		

		if ($usuario->comprobarAccesoOpcion($arrayDatos)) {
			if (is_numeric($rol) && $makePersitent) {
				$rol = new rol($rol);
				
				$resultadoCambio = $rol->actualizarPerfil($perfil->getUID(), $makePersitent);
			} else {

				$resultadoCambio = $perfil->unlinkRol();
				if ($resultadoCambio === NULL) $resultadoCambio = true;
				if (!$estadoActualizacion = $perfil->actualizarOpciones($arrayDatos)) die($estadoActualizacion);
			}
		}

		die($template->getString("exito_titulo"));
		exit;
	}

	
	$template->assign("roles", rol::obtenerRolesGenericos() );
	$template->assign("usuario", $perfil->getUser() );
	$template->assign("perfil", $perfil );
	$optionHTML = $template->getHTML( "editarperfil.tpl" );


	$json = new jsonAGD();
	$json->establecerTipo("options");
	$json->nuevoSelector(".option-title", $template->getString("informacion_perfiles") );
	$json->nuevoSelector(".option-list", $optionHTML);
	$json->display();
?>
