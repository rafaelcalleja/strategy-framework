<?php
	require_once("../../../api.php");

	$template = Plantilla::singleton();
	//instanciamos al usuario seleccionado
	$perfilSeleccionado = new perfil( obtener_uid_seleccionado() );


	if( isset($_REQUEST["send"]) ){
		//comprobacion de seguridad
		$perfiles = $perfilSeleccionado->getUser()->obtenerPerfiles(false);
		foreach($perfiles as $perfil){
			if( $perfil->getUID() == $perfilSeleccionado->getUID() ){
				if( $perfil->activate() ){
					$template->display("succes_form.tpl");
					exit;
				}
			}
		}
		
		/*
		$estado = $usuario->actualizarEtiquetas();
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}
		*/
	}

	$template->assign("perfil", $perfilSeleccionado);
	$template->display( "activar_perfil.tpl" );
?>
