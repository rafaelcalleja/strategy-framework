<?php

use Dokify\Application\Event\Assignment\Suggest as AssignmentSuggestEvent;
use Dokify\Events\Assignment\SuggestEvents as SuggestEvents;

include_once "../api.php";

if (($uid = obtener_uid_seleccionado()) && $m = obtener_modulo_seleccionado()) {
    $elemento = new $m($uid);
}

if (!isset($elemento) || !$usuario->accesoElemento($elemento)) {
    die('Inaccesible');
}

$log = new log(0, $usuario);
$template = new Plantilla();
$empresaUsuario = $usuario->getCompany();
$empresasSeleccionadas = [];
$app = \Dokify\Application::getInstance();

// posibles acciones si tenemos solicitud
if ($solicitud = @$_REQUEST["request"]) {
    $solicitud = new empresasolicitud($solicitud);

    if (!$list = $solicitud->getValue()) {
        error_log("No data for company request {$solicitud->getUID()} in sugeriragrupador.php");
        exit;
    }

    $selected = new ArrayIntList($list);
    $sugeridos = $selected->toObjectList('agrupador');
    $empresaSolicitud = $solicitud->getCompany();
    $solicitante = $solicitud->getSolicitante();

    if ($empresaUsuario->compareTo($solicitante)) {
        $template->assign("comment", false);
    } else {
        $template->assign("comment", true);
    }

    if (isset($_REQUEST["action"]) && $do = $_REQUEST["action"]) {
        if ($empresaUsuario->compareTo($empresaSolicitud)) {
            $log->info($elemento->getModuleName(), "contestar solicitud asignacion", $elemento->getUserVisibleName());
            switch ($do) {
                case 'accept':
                    $estado = $solicitud->aceptar(@$_REQUEST['response_message'], $usuario);
                    break;
                case 'reject':
                    if (!isset($_REQUEST['response_message']) || empty($_REQUEST['response_message'])) {
                        $template->assign('error', 'indicar_motivo_rechazo_solicitud');
                        $template->assign("solicitud", $solicitud);
                        $template->assign("list", $sugeridos);
                        $template->assign("elemento", $elemento);
                        $template->assign("action", "sugeriragrupador.php");
                        $template->display("sugeriragrupador.tpl");
                        exit;
                    }
                    $estado = $solicitud->rechazar(@$_REQUEST['response_message'], $usuario);
                    break;
            }
            $log->resultado("ok $do", true);
            $template->sendFlag("notification-complete", ["id" => 'aviso-'.$solicitud->getUID()]);
            $template->display('succes_form.tpl');
            exit;
        } elseif ($empresaUsuario->compareTo($solicitante)) {
            switch ($do) {
                case 'delete':
                    $log->info($elemento->getModuleName(), "cancelar solicitud asignacion", $elemento->getUserVisibleName());
                    $estado = $solicitud->cancelar(@$_REQUEST['response_message'], $usuario);
                    if ($estado) {
                        $template->display("succes_form.tpl");
                        $log->resultado("ok $do", true);
                        exit;
                    } else {
                        $template->assign("error", "error_texto");
                        $log->resultado("error $do", true);
                    }
                    break;
                case 'resend':
                    $event = new AssignmentSuggestEvent\Created($solicitud->asDomainEntity());

                    $app->dispatch(
                        SuggestEvents::ASSIGNMENT_SUGGEST_CREATED,
                        $event
                    );

                    $template->display("succes_form.tpl");
                    exit;
                break;
            }
        }
    }
                $template->assign("solicitud", $solicitud);
            $template->assign("list", $sugeridos);
            $template->assign("elemento", $elemento);
            $template->assign("action", "sugeriragrupador.php");
            $template->display("sugeriragrupador.tpl");
            exit;
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'create') {
    $selected = get_int_array_from_request('selected');
    $selected = new ArrayIntList($selected);
    $sugeridos = $selected->toObjectList('agrupador');

    if ($elemento instanceof empresa) {
        // TODO: este caso no debería darse nunca?, no se sugieren asignaciones a subcontratas...
        $empresasSeleccionadas[] = $elemento;
    } else {
        $uidsEmpresasUsuario = $empresaUsuario->getAllCompaniesIntList()->getArrayCopy();
        $uidsEmpresasElemento = $elemento->obtenerEmpresas()->toIntList()->getArrayCopy();

        $empresasCandidatas = array_intersect($uidsEmpresasUsuario, $uidsEmpresasElemento);
        if (!isset($_REQUEST['empresas_seleccionadas']) || empty($_REQUEST['empresas_seleccionadas'])) {
            $empresasCandidatas = new ArrayIntList($empresasCandidatas);
            $empresasCandidatas = $empresasCandidatas->toObjectList('empresa');
            $template->assign('error', 'selecciona_empresa_enviar_solicitud');
            $template->assign("empresas", $empresasCandidatas);
            $template->assign("list", $sugeridos);
            $template->assign("elemento", $elemento);
            $template->display("sugeriragrupador.tpl");
            exit;
        } else {
            $list = ($list = get_int_array_from_request('empresas_seleccionadas')) ? $list : [];
            $uidsEmpresasSeleccionadas = new ArrayIntList($list);
            $empresasSeleccionadas = $uidsEmpresasSeleccionadas->toObjectList('empresa');
        }
    }

    foreach ($empresasSeleccionadas as $empresaSolicitud) {
        $solicitud = $usuario->crearSolicitud($empresaSolicitud, $elemento, solicitud::TYPE_ASIGNAR, $selected->getArrayCopy());
        if ($empresaSolicitud->obtenerContactoPrincipal()) {
            $event = new AssignmentSuggestEvent\Created($solicitud->asDomainEntity());
            $app->dispatch(
                SuggestEvents::ASSIGNMENT_SUGGEST_CREATED,
                $event
            );
        }
    }

    $template->display("succes_form.tpl");
}
