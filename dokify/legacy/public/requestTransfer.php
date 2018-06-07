<?php
	include("config.php");

	
	$template = new Plantilla(); //creamos la instancia de la plantilla
	$log = log::singleton();

	if (!isset($_GET["token"])) {
		header("Location: /sinacceso.html"); exit;
	}
	
	if (!$solicitudEmpleado = empleadosolicitud::getByToken($_GET["token"])) {
		header("Location: /sinacceso.html"); exit;
	}

	if ($answer = @$_GET["q"]) {
		$img = RESOURCES_DOMAIN . "/img/dokify-google-logo.png";

		$log->info("transferencia propio empleado", "acceso para transferir empleado ", log::getIPAddress());
		$log->resultado("ok", true);
		$template->assign("request", $solicitudEmpleado);

		if ($solicitudEmpleado->isCreatedStatus()) {
			switch ($answer) {
				case 'ac':
					$solicitudEmpleado->share();
					$template->display( "acceptedRequestTransfer.tpl" );exit; 
					break;
				case 'dn':
					$solicitudEmpleado->rechazar();
					$template->display( "deniedRequestTransfer.tpl" );exit; 
					break;
				
				default:
					header("Location: /sinacceso.html"); exit;				
					break;
			}
		} else {
			switch ($solicitudEmpleado->getState()) {
				case solicitud::ESTADO_EXPIRED:
					$template->display( "expiredRequestTransfer.tpl" );exit;
					break;

				case solicitud::ESTADO_SHARED:
					$template->assign("title", sprintf($template->getString('solicitud_procesada')));
					$template->assign("message", sprintf($template->getString('request_transfer_accepted_already'), $solicitudEmpleado->getSolicitante()->getUserVisibleName()));
					$template->display( "processedRequestTransfer.tpl" );exit;
					break;

				case solicitud::ESTADO_RECHAZADA:
					$template->assign("title", sprintf($template->getString('solicitud_procesada')));
					$template->assign("message", sprintf($template->getString('request_transfer_denied_already'), $solicitudEmpleado->getSolicitante()->getUserVisibleName()));
					$template->display( "processedRequestTransfer.tpl" );exit;
					break;
				
				default:
					header("Location: /sinacceso.html"); exit;
					break;
			}
		} 
		
	} else {
		header("Location: /sinacceso.html"); exit;
	}

?>