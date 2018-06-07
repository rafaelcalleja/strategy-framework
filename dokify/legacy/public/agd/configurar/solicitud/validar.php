<?php
	include( "../../../api.php");

	$solicitud = new solicitud( obtener_uid_seleccionado() );

	$usuario = $solicitud->getUser();

	switch( $solicitud->getTypeOf() ){
		case "upload":
			if( $usuario->maxUploadSize( $solicitud->getValue(), 1) ){
				$solicitud->setState( solicitud::ESTADO_ACEPTADA );
			}
		break;
	}



?>
