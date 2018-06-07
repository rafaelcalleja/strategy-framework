<?php

include( "../api.php");
$template = new Plantilla();
$empresaUsuario = $usuario->getCompany();
$solicitud = new empresasolicitud(@$_REQUEST["request"]);
$empresaSolicitante = $solicitud->getSolicitante();
$empresaElemento = $solicitud->getItem();

if (!$solicitud) {
    $template->assign("error", "desc_error_papelera_aviso");
    $template->display("error_string.tpl");
    exit;
}

if (!$empresaUsuario->myRequest($solicitud)) {
    $template->assign("error", "desc_error_aviso_no_propiedad");
    $template->display("error_string.tpl"); //MENSAJE CONCRETO DE QUE NO TE PERTENCE LA SOLICITUD
    exit;
}

//SEND PARA TRATAR SOLICITUD
if (isset($_REQUEST["send"]) && $_REQUEST['action']) {
    switch ($_REQUEST['action']) {
        case 'accept':
            if ($solicitud) {
                $message = null;
                if (isset($_REQUEST['response_message']) && !empty($_REQUEST['response_message'])) {
                    $message = $_REQUEST['response_message'];
                    $solicitud->setMessage($message);
                }
                $status = $solicitud->getState();
                if ($status === solicitud::ESTADO_CREADA || $status === solicitud::ESTADO_CANCELADA) {
                    $solicitud->setState(solicitud::ESTADO_ACEPTADA);
                    $template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()));
                }
                switch ($solicitud->getTypeOf()) {
                    case solicitud::TYPE_ELIMINARCLIENTE:
                        $empresaElemento->enviarPapelera($empresaSolicitante, $usuario);
                        $empresaElemento->actualizarSolicitudDocumentos();

                        // update requests, may be documents with contract reference
                        $empresaSolicitante->actualizarSolicitudDocumentos();

                        $empresaElemento->writeLogUI(logui::ACTION_DISABLE, 'uid_empresa:'.$empresaSolicitante->getUID(), $usuario);
                        break;
                    case solicitud::TYPE_ELIMINARCONTRATA:
                        $empresaSolicitante->enviarPapelera($empresaElemento, $usuario);
                        $empresaSolicitante->actualizarSolicitudDocumentos();

                        // update requests, may be documents with contract reference
                        $empresaElemento->actualizarSolicitudDocumentos();

                        $empresaSolicitante->writeLogUI(logui::ACTION_DISABLE, 'uid_empresa:'.$empresaElemento->getUID(), $usuario);
                        break;
                    default:
                        //MENSAJE CONCRETO DE QUE NO TE PERTENCE LA SOLICITUD
                        $template->assign("error", "desc_error_aviso_no_propiedad");
                        $template->display("error_string.tpl");
                        exit;
                        break;
                }
                $solicitud->sendEmailAcceptedDeleteRelationship($usuario, $message);
            }
            $template->display("succes_form.tpl");
            exit;
        break;
        case 'reject':
            if (!isset($_REQUEST['response_message']) || empty($_REQUEST['response_message'])) {
                switch ($solicitud->getTypeOf()) {
                    case solicitud::TYPE_ELIMINARCLIENTE:
                        if (($num = $empresaElemento->numSubcontracts($empresaSolicitante)) > 15) {
                            $template->assign('alert', sprintf($template->getString("demasiadas_cadenas_activas_cliente"), $num, $empresaSolicitante->getUserVisibleName()));
                        }
                        $mensaje = sprintf($template->getString('confirmar_eliminar_cliente'), $empresaSolicitante->getUserVisibleName());
                        break;
                    case solicitud::TYPE_ELIMINARCONTRATA:
                        $mensaje = sprintf($template->getString('confirmar_eliminar_contrata'), $empresaElemento->getUserVisibleName(), $empresaSolicitante->getUserVisibleName());
                        break;
                    default:
                        $template->assign("error", "desc_error_aviso_no_propiedad");
                        $template->display("error_string.tpl");
                        exit;
                        break;
                }
                $template->assign('mensaje', $mensaje);
                $template->assign('solicitud', $solicitud);
                $template->assign('error', 'indicar_motivo_rechazo_solicitud');
                $template->display('confirmareliminarrelacion.tpl');
                exit;
            }
            $status = $solicitud->getState();
            if ($status === solicitud::ESTADO_CREADA || $status === solicitud::ESTADO_CANCELADA) {
                $solicitud->setMessage($_REQUEST['response_message']);
                $solicitud->setState(solicitud::ESTADO_RECHAZADA);
                $template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()));
                $solicitud->sendEmailDeniedDeleteRelationship($usuario);
            }
            $template->display("succes_form.tpl");
            exit;
        break;
    }

}
switch ($solicitud->getTypeOf()) {
    case solicitud::TYPE_ELIMINARCLIENTE:
        if (($num = $empresaElemento->numSubcontracts($empresaSolicitante)) > 15) {
            $template->assign('alert', sprintf($template->getString("demasiadas_cadenas_activas_cliente"), $num, $empresaSolicitante->getUserVisibleName()));
        }
        $mensaje = sprintf($template->getString('confirmar_eliminar_cliente'), $empresaSolicitante->getUserVisibleName());
        break;
    case solicitud::TYPE_ELIMINARCONTRATA:
        $mensaje = sprintf($template->getString('confirmar_eliminar_contrata'), $empresaElemento->getUserVisibleName(), $empresaSolicitante->getUserVisibleName());
        break;
    default:
        $template->assign("error", "desc_error_aviso_no_propiedad");
        $template->display("error_string.tpl");
        exit;
        break;
}

$template->assign('mensaje', $mensaje);
$template->assign('solicitud', $solicitud);
$template->display("confirmareliminarrelacion.tpl");
