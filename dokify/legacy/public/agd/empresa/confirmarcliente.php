<?php
// confirmar una solicitud de contratacion
include( "../../api.php");
$template = new Plantilla();
if (!$uid = obtener_uid_seleccionado()) {
    die('Inaccesible');
}

$request = new empresasolicitud($uid);
if (!$request instanceof empresasolicitud) {
    $template->assign("message", "solicitud_no_valida");
    $template->display("error.tpl");
    exit;
}

$solicitante = $request->getSolicitante();
if (!$solicitante instanceof empresa) {
    $template->assign("message", "empresa_no_valida");
    $template->display("error.tpl");
    exit;
}

// la empresa actual somos nosotros
$empresaActual      = $usuario->getCompany();
$requestedCompany   = $request->getCompany();

if ($requestedCompany && !$empresaActual->getStartIntList()->contains($requestedCompany->getUID())) {
    $template->assign("message", "solicitud_no_valida");
    $template->display("error.tpl");
    exit;
}

$empresaCliente = $request->getItem();
if (!$empresaCliente instanceof empresa) {
    $template->assign("message", "solicitud_no_valida");
    $template->display("error.tpl");
    exit;
}
$template->assign("empresa", $empresaCliente);

if (isset($_REQUEST["send"]) && $_REQUEST['action']) {
    try {
        switch ($_REQUEST['action']) {
            case 'accept':
                $estado = $request->aceptar(@$_REQUEST['response_message'], $usuario);

                if (!$empresaCliente->updateSignInRequest($requestedCompany, signinRequest::STATE_ACCEPTED)) {
                    $groups         = new ArrayObjectList;
                    $categories     = array(categoria::TYPE_PUESTO, categoria::TYPE_GRUPODERIESGO, categoria::TYPE_TIPOMAQUINARIA);
                    foreach ($categories as $category) {
                        if ($inCategory = $empresaCliente->obtenerAgrupadoresVisibles(array(new categoria($category), $requestedCompany))) {
                            $groups = $groups->merge($inCategory);
                        }
                    }

                    if ($intList = $groups->toIntList()) {
                        $requestedCompany->asignarAgrupadores($intList->getArrayCopy());
                        $requestedCompany->actualizarSolicitudDocumentos();
                    }
                }

                if (isset($_REQUEST['tipo_empresa'])) {
                    if (is_numeric($_REQUEST['tipo_empresa'])) {
                        $arrayIds = array($_REQUEST['tipo_empresa']);
                        $agrupadores = $requestedCompany->asignarAgrupadores($arrayIds, $usuario);
                    } else {
                        $agrupadores = $solicitante->obtenerAgrupadoresVisibles(new categoria(categoria::TYPE_TIPOEMPRESA));
                        if (count($agrupadores)) {
                            $template->assign("typesCompany", $agrupadores);
                        }
                        $template->assign('error', 'choose_category_client');
                        $template->display('confirmarcliente.tpl');
                        exit;
                    }
                }

                $requestedCompany->actualizarSolicitudDocumentos();

                // update requests, may be documents with contract reference
                $empresaCliente->actualizarSolicitudDocumentos();

                if (isset($_REQUEST['reanexar']) && $_REQUEST['reanexar']) {
                    $reattachCompany = $empresaActual->reattachAll($empresaCliente, $usuario);

                    $employees = $empresaActual->obtenerEmpleados();
                    $reattachEmployees = $employees->foreachCall("reattachAll", array($empresaCliente, $usuario));
                }
                break;
            case 'reject':
                if (!isset($_REQUEST['response_message']) || empty($_REQUEST['response_message'])) {
                    $template->assign('error', 'indicar_motivo_rechazo_solicitud');
                    $template->display('confirmarcliente.tpl');
                    exit;
                }

                $estado = $request->rechazar(@$_REQUEST['response_message'], $usuario);
                $empresaCliente->updateSignInRequest($requestedCompany, signinRequest::STATE_REJECTED);
                break;
        }
        if ($estado) {
            $template->sendFlag("notification-complete", array("id" => 'aviso-'.$request->getUID()));
            $template->display("succes_form.tpl");
        } else {
            throw new Exception("error_al_procesar_la_solicitud");
        }
    } catch (Exception $e) {
        $template->assign("message", $e->getMessage());
        $template->display("error.tpl");
    }


} else {
    $agrupadores = $solicitante->obtenerAgrupadoresVisibles(new categoria(categoria::TYPE_TIPOEMPRESA));
    if (count($agrupadores)) {
        $template->assign("typesCompany", $agrupadores);
    }
    $template->display("confirmarcliente.tpl");
}
