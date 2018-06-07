<?php
	require_once("../../api.php");


	$template = Plantilla::singleton();



	//instanciamos al usuario seleccionado
	$elemento = new buscador( obtener_uid_seleccionado() );


	if( isset($_REQUEST["send"]) ){

		$estado = $elemento->actualizarUsuarios();
		if( $estado === true ){
			$template->assign("succes", "exito_titulo");
		} else {
			$template->assign( "error" , $estado  );
		}

	}

	$asignados = $elemento->obtenerUsuariosConAcceso();
	$disponibles = elemento::discriminarObjetos($usuario->obtenerHermanos(), $asignados);


	//$template->assign( "title", $elemento->getUserVisibleName() );
	$template->assign( "asignados" ,  $asignados );
	$template->assign( "disponibles" , $disponibles );
	$template->display( "configurar/asignarsimple.tpl" );
?>
