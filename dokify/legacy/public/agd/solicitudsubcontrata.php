<?php

	include( "../api.php");
	$template = new Plantilla();
	$empresaUsuario = $usuario->getCompany();
	$solicitud = new empresasolicitud( @$_REQUEST["request"] );
	
	// Viene para aceptar/rechazar una asignacion
	if ( !$solicitud ) {
		$template->assign("error","desc_error_papelera_aviso");
		$template->display( "error_string.tpl" ); 
		exit;
	}
	$empresaSolicitud = $solicitud->getCompany();

	if ( !$empresaUsuario->getStartIntList()->contains( $empresaSolicitud->getUID() ) ) {
		$template->assign("error","desc_error_aviso_no_propiedad");
		$template->display( "error_string.tpl" ); //MENSAJE CONCRETO DE QUE NO TE PERTENCE LA SOLICITUD
		exit;
	}

	if( !count( $empresaUsuario->obtenerCadenasContratacion($solicitud->getItem(), array(3,4), array(1,2)) ) ){
		$solicitud->setState(solicitud::ESTADO_RECHAZADA);
		$template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()) );
		$template->assign("alert", "mensaje_subcontrata_no_asignada");
		$empresa = new empresa (@$_REQUEST["poid"]);
		$template->assign("empresa", $empresa);
		$template->display("solicitudsubcontrata.tpl");
		exit;
	}

	//SEND PARA TRATAR SOLICITUD
	if( isset($_REQUEST["send"]) ){
		//Hay tratar el estado de la solicitud
		if( $solicitud ){
			$status = $solicitud->getState();
			if( $status === solicitud::ESTADO_CREADA || $status === solicitud::ESTADO_CANCELADA ){
				$solicitud->setState(solicitud::ESTADO_RECHAZADA);
				$template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()) );
			}					
		}	
		//comprobar si mover arriba funciona ok y aqui poner un else de error 	
		$template->display( "succes_form.tpl" );
		exit;
	}

	$empresa = new empresa (@$_REQUEST["poid"]);
	$template->assign("empresa", $empresa);
	$template->display("solicitudsubcontrata.tpl");
		
?>