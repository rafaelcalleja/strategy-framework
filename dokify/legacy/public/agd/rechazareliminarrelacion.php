<?php

	include( "../api.php");
	$template = new Plantilla();
	$empresaUsuario = $usuario->getCompany();
	$solicitud = new empresasolicitud( @$_REQUEST["request"] );
	$empresaContrata = $solicitud->getSolicitante();
	$empresaSolicitud = $solicitud->getCompany();

	if ( !$solicitud ) {
		$template->assign("error","desc_error_papelera_aviso");
		$template->display( "error_string.tpl" ); 
		exit;
	}

	if ( !$empresaUsuario->myRequest($solicitud) ) {
		$template->assign("error","desc_error_aviso_no_propiedad");
		$template->display( "error_string.tpl" ); //MENSAJE CONCRETO DE QUE NO TE PERTENCE LA SOLICITUD
		exit;
	}

	$status = $solicitud->getState();
	if( $status === solicitud::ESTADO_CREADA || $status === solicitud::ESTADO_CANCELADA ){
		$solicitud->setState(solicitud::ESTADO_RECHAZADA);
		$template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()) );
		$solicitud->sendEmailDeniedDeleteRelationship($usuario);
	}				
	

	$template->display( "succes_form.tpl" );
	exit;

?>