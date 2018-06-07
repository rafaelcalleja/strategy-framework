<?php
	include( "../../../api.php");

	$template = Plantilla::singleton();


	if( isset($_REQUEST["send"] ) ){

		//tratamos de crear el usuario
		$nuevoUsuario = usuario::crearNuevo( $_REQUEST["usuario"] );
		if( $nuevoUsuario instanceof usuario ){

			//si se insertar el usuario creamos el perfil
			$uidnuevoperfil = $nuevoUsuario->crearPerfil($usuario, true);

			//si todo es correcto mostramos el ok por pantalla
			if( is_numeric($uidnuevoperfil) ){
				$template->display("succes_form.tpl");
				exit;
			} else {
			//si no, es un error

				//debemos borrar el usuario creado, para no confundir al usuario
				config::eliminarElemento( $nuevoUsuario->getUID(), TABLE_USUARIOS );

				$template->assign("error", $uidnuevoperfil );
			}
		} else {
		//si no, es un error
			$template->assign("error", $nuevoUsuario );
		}

	}


	$template->assign ("campos", usuario::publicFields() );
	$template->assign ("titulo", "nuevo_usuario" );
	$template->assign ("boton", "crear" );
	$template->display( "form.tpl");
?>
