<?php
// confirmar solicitud de transferencia de un empleado
include( "../../api.php");
$template = new Plantilla();
if (!$uid = obtener_uid_seleccionado()) {
	die('Inaccesible');
}

$transferencia = new empresasolicitud($uid);
if (!$transferencia instanceof empresasolicitud) {
	$template->assign("message", "solicitud_no_valida");
	$template->display("error.tpl");
	exit;
} 


// la empresa que ha solicitado el empleado es la empresaDestino.
$empresaDestino = $transferencia->getSolicitante();
if (!$empresaDestino instanceof empresa) {
	$template->assign("message", "empresa_no_valida");
	$template->display("error.tpl");
	exit;
}

// la empresa actual somos nosotros
$empresaActual = $usuario->getCompany();
if (!$empresaActual->getStartIntList()->contains($transferencia->getCompany()->getUID())) {
	$template->assign("message", "solicitud_no_valida");
	$template->display("error.tpl");
	exit;	
}

$empleadoTransferencia = $transferencia->getItem();
if (!$empleadoTransferencia instanceof empleado) {
	$template->assign("message", "solicitud_no_valida");
	$template->display("error.tpl");
	exit;	
}
$template->assign("empleado", $empleadoTransferencia);



if( isset($_REQUEST["send"]) && isset($_REQUEST["action"]) && $_REQUEST['action']){
	switch ($_REQUEST['action']) {
		case 'transfer':
			$estado = $transferencia->aceptar(@$_REQUEST['response_message'],$usuario);
		break;
		case 'share':
			$estado = $transferencia->share(@$_REQUEST['response_message'],$usuario);
		break;
		case 'cancel':
			if (!isset($_REQUEST['response_message']) || empty($_REQUEST['response_message'])) {
				$template->assign('error','indicar_motivo_rechazo_solicitud');
				$template->display('empleado/confirmartransferencia.tpl');
				exit;
			}
			$estado = $transferencia->rechazar(@$_REQUEST['response_message'],$usuario);
		break;
	}

	if ($estado) {
		$template->sendFlag("notification-complete", array("id" => 'aviso-'.$transferencia->getUID()) );
		$template->display("succes_form.tpl");
	} else {
		$template->assign("message", "error_al_procesar_la_solicitud");
		$template->display("error.tpl");	
	}
} else {
	$template->display("empleado/confirmartransferencia.tpl");
}