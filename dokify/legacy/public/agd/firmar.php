<?php

require_once('../api.php');

$log = log::singleton(); // --- Log de datos
$template = Plantilla::singleton(); // --- Plantilla de salida

$modulo = obtener_modulo_seleccionado();

if (isset($_REQUEST["o"]) && $uid=$_REQUEST["o"]) {
    $elementoActual = new $modulo($uid);
} else {
    die('inaccesible');
}

$documento = new documento(obtener_uid_seleccionado(), $elementoActual);
$log->info($modulo, "firmar documento {$documento->getUserVisibleName()}", $elementoActual->getUserVisibleName());

if (!$elementoActual instanceof solicitable) {
    $log->nivel(5);
    $log->resultado('modulo no valido', true);
    die('Error: Modulo no especificado!');
}

if (!$usuario->accesoElemento($elementoActual)) {
    if ($usuario->esStaff()) {
        $template->display("sin_acceso_perfil.tpl");
    } else {
        die("Inaccesible");
    }
}

$availableOptions = $usuario->getAvailableOptionsForModule($documento, "firmar");
$action = reset($availableOptions);

if (!$usuario->accesoElemento($elementoActual) || !$action) {
    if ($usuario->esStaff()) {
        $template->display("sin_acceso_perfil.tpl");
    } else {
        die("Inaccesible");
    }
}

// --- read "req" param
$req = (isset($_REQUEST['req']) && is_numeric($_REQUEST['req'])) ? new solicituddocumento($_REQUEST['req']) : null;

if (isset($_REQUEST['action'])) {
    include('uploadactions.php');
    exit;
}

$empresas = $documento->obtenerEmpresasSolicitantes($elementoActual, $usuario, 1, $req);

foreach ($empresas as $empresa) {
    $solicitudes = $documento->obtenerSolicitudDocumentos($elementoActual, $usuario, $empresa, 1, $req);
    foreach ($solicitudes as $solicitud) {
        $attr = $solicitud->obtenerDocumentoAtributo();
        $attrModelo = $attr->obtenerDocumentoEjemplo();

        if ($attrModelo) {
            try {
                $data = array();
                $data[Ilistable::DATA_CONTEXT] = Ilistable::DATA_CONTEXT_FIRM;
                $data["req"] = isset($_REQUEST["req"]) ? $_REQUEST["req"] : null;
                $info = $attrModelo->downloadFile(true, $elementoActual, $usuario, $solicitud, $data);

            // we cant parse the document because we something to do first
            } catch (Exception $e) {
                $missAssignmentDuration     = $e->getCode() === documento_atributo::ECODE_MISSING_ASSIGNMENT_DURATION;
                $missLegalRepresentative    = $e->getCode() === documento_atributo::ECODE_MISSING_LEGAL_REPRESENTATIVE;
                if ($missLegalRepresentative || $missAssignmentDuration) {
                    header('Location: ' . $e->getMessage());
                } else {
                    die($e->getMessage());
                }

                exit;
            }
        }
    }
}


if (isset($_REQUEST["send"])) {
    // Extract selected "solicituddocumento" uid list
    $list = obtener_uids_seleccionados();

    try {
        if (!$list instanceof ArrayObject) {
            throw new Exception("error_seleccionar_destinatarios");
        }

        $solicitudes = $list->toObjectList("solicituddocumento");

        foreach ($solicitudes as $solicitud) {
            $attr = $solicitud->obtenerDocumentoAtributo();
            $attrModelo = $attr->obtenerDocumentoEjemplo();

            if ($attrModelo) {
                try {
                    $info = $attrModelo->downloadFile(true, $elementoActual, $usuario, $solicitud);

                // we cant parse the document because we something to do first
                } catch (Exception $e) {
                    header('Location: ' . $e->getMessage());
                    exit;
                }

                $mime = $info['ext'];
                $md5 = $info['hash'];

                if ($documento->upload(basename($info['path']), $md5, $info['nombrefichero'], @$_REQUEST["fecha"], $elementoActual, array($solicitud), $usuario, @$_REQUEST["comentario"], @$_REQUEST["caducidad"], $mime)) {
                    $log->resultado('ok', true);

                    $anexo = $solicitud->getAnexo();
                    if (!$documento->updateStatus(array($anexo), documento::ESTADO_VALIDADO, $usuario)) {
                        error_log('No se puede validar el anexo ' . $anexo->getUID());
                    }

                    if ($attr->isCertification()) {
                        $elementoActual->actualizarSolicitudDocumentos();
                    }

                    $attachments = new ArrayAnexoList([$anexo]);
                    $attachments->saveComment('', $usuario, comment::ACTION_SIGN, false);

                    if (is_mobile_device()) {
                        print json_encode(array("action" => array("go" => "#documentos.php?m={$elementoActual->getModuleName()}&poid={$elementoActual->getUID()}")));
                        exit;
                    }

                    $template->display('succes_form.tpl');
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $log->resultado('error '.$error, true);
        $template->assign('error', $error);
    }
}


if (isset($_SERVER["HTTP_X_LAST_PAGE"]) && preg_match('/\#(.*)/', $_SERVER["HTTP_X_LAST_PAGE"], $matches) && $page = $matches[1]) {
    $page = parse_url($page);
    parse_str(@$page['query'], $query);
    $template->assign('comefrom', @$query['comefrom']);
}

$template->assign('selectedRequest', $req);
$template->assign('elemento', $elementoActual);
$template->assign('documento', $documento);

$hasDNI = $usuario->getId();
$template->assign('canSign', (bool) trim($hasDNI));

$company = $usuario->getCompany();
if ($company->isFree()) {
    $template->display('firmar_premium.tpl');
} else {
    $template->display('firmar.tpl');
}
