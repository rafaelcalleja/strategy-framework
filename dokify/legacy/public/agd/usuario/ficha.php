<?php
	
	include_once( "../../api.php");

	$tpl = Plantilla::singleton();


	$usuarioSeleccionado = new usuario( obtener_uid_seleccionado() );

	if( isset($_REQUEST["usuario-permisos"]) ){
		$usuarioPermisos = new usuario($_REQUEST["usuario-permisos"]);
		if( $usuarioPermisos->getUID() ){
			$empresaClienteActual = $usuario->getCompany();
			$perfilClienteUsuario = $usuarioSeleccionado->perfilEmpresa( $empresaClienteActual );
			$perfilClientePermisos =  $usuarioPermisos->perfilEmpresa( $empresaClienteActual );
			$actualizacion = $perfilClienteUsuario->actualizarConPerfil( $perfilClientePermisos );
			if( $actualizacion === true ){
				$tpl->assign("succes","exito_texto");
			}
		}
	}



	$tpl->assign("usuarioActivo", $usuario);
	$tpl->assign("usuario", $usuarioSeleccionado);
	$tpl->assign("empresa", $usuarioSeleccionado->getCompany() );
	$tpl->display("ficha_usuario.tpl");
?>
