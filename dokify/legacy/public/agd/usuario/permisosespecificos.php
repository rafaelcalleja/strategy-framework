<?php 	
	include( "../../api.php");

	/* EDITAR UN USUARIO */
	if( !isset($_REQUEST["poid"]) ){ exit; }


	$template = new Plantilla();
	$m = obtener_modulo_seleccionado();
	$usuarioSeleccionado = new usuario( obtener_uid_seleccionado() );
	$elemento = new $m( $_GET["o"] );



	$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilEmpresa( $usuario->getCompany() );
	if( !$perfilUsuarioSeleccionado instanceof perfil ){
		$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilActivo( $usuario->getCompany() );
		if( !$perfilUsuarioSeleccionado instanceof perfil ){
			$perfilUsuarioSeleccionado = $usuarioSeleccionado->perfilActivo();
		}
	}



	if( !$usuario->accesoElemento( $perfilUsuarioSeleccionado ) ){
		die( "Innaccesible" );
	}
	
	if( isset($_REQUEST["rol"]) && is_numeric($_REQUEST["rol"]) ){
		$rol = new rol($_REQUEST["rol"]);
		if( $usuario->accesoElemento($rol) ){
			$listAcciones = array_keys($rol->obtenerOpcionesDisponibles());
			if( $perfilUsuarioSeleccionado->guardarOpcionesObjeto($elemento, $listAcciones) === true ){
				$template->assign("succes", "exito_texto");
			} else {
				$template->assign("error", "error_texto");
			}
		} else {
			$template->assign("error", "error_texto");
		}
	}

	if( isset($_GET["send"]) ){
		if( $perfilUsuarioSeleccionado->guardarOpcionesObjeto($elemento, $_REQUEST["accion"]) === true ){
			$template->assign("succes", "exito_texto");
		} else {
			$template->assign("error", "error_texto");
		}
	}



	$opciones = $usuario->perfilActivo()->obtenerOpcionesObjeto($elemento);
	$permisosactivos = $perfilUsuarioSeleccionado->obtenerOpcionesObjeto($elemento, true);

	$template->assign("objeto", $elemento );
	$template->assign("usuario", $usuarioSeleccionado );
	$template->assign("perfil", $perfilUsuarioSeleccionado );

	$template->assign("permisosactivos", $permisosactivos );
	$template->assign("permisos", $opciones );
	$template->display("permisosespecificos.tpl");
?>
