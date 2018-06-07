<?php
/* ENVIAR ELEMENTOS A LA PAPELERA */
include( "../api.php");

if (!$modulo = obtener_modulo_seleccionado()) {
    die("Inaccesible");
}

$template = new Plantilla();
$empresaUsuario = $usuario->getCompany();

//$empresaSeleccionada = new $modulo( $_REQUEST["poid"] );
if (($list = obtener_uids_seleccionados()) !== null) {
    if ($empresaUsuario->hasTransferPending()) {
        header("Content-type: application/json");
        print json_encode(array("refresh" => true, "jGrowl" => $template("transfer_pending_bulk_action")));
        exit;
    }

    $clear = array();
    /**POR CADA ELEMENTO SELECCIONADO INSTANCIAMOS PARA PODER RECUPERAR SEU METODO DE ENVIAR A PAPELERA PARA LA EMPRESA ACTUAL**/
    foreach ($list as $uid) {
        $elementoSeleccionado = new $modulo( $uid );

        $parentItems = $elementoSeleccionado->obtenerElementosActivables($usuario);

        // Este elemento es el superior para cuya papelera debemos enviar este elemento, siguiendo misma lógica
        $parent = reset($parentItems);
        $result = $elementoSeleccionado->enviarPapelera($parent, $usuario);

        $elementoSeleccionado->writeLogUI(logui::ACTION_DISABLE, 'uid_empresa:'. $parent->getUID(), $usuario);

        if ($elementoSeleccionado instanceof solicitable) {
            $elementoSeleccionado->actualizarSolicitudDocumentos();
        }

        if ($elementoSeleccionado instanceof empresa) {
            // update requests, may be documents with contract reference
            $parent->actualizarSolicitudDocumentos();
        }

        if ($result) {
            $clear[] = $elementoSeleccionado->getUID();
        }
    }
    $clear = array_unique($clear);
    header("Content-type: application/json");
    print json_encode(array("refresh" => true, "clear" => array($modulo=>$clear), "jGrowl" => count($clear) . " enviados a la papelera"));
    exit;
}

if (!$uid = obtener_uid_seleccionado()) {
    die("Inaccesible");
}

if (isset($_REQUEST['m']) && in_array($_REQUEST['m'], ['empleado', 'maquina']) && $empresaUsuario->hasTransferPending()) {
    if (isset($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
            case 'accept':
                $empresaUsuario->setTransferPending(false);
                break;
        }
    } else {
        $template->assign('mensaje', $template("transfer_pending_general"));
        $template->assign('allow_accept', true);
        $template->display("transfer_pending.tpl");
        exit;
    }
}

$elementoSeleccionado = new $modulo($uid);

$parentItems = $elementoSeleccionado->obtenerElementosActivables($usuario);

if (is_traversable($parentItems) && count($parentItems)) {
    if (count($parentItems) > 1) {

        /** SI VIENE DE SELECCIONAR VARIOS OBJETOS PARA ENVIAR A LA PAPELERA ELEMENTOS, NO VIENE VACIO, SINO LE MOSTRAMOS DE NUEVO PLANTILLA SELECCION */
        if (isset($_REQUEST["elementos"]) && $_REQUEST["elementos"] && is_array($_REQUEST["elementos"]) && $items = new ArrayIntList($_REQUEST["elementos"])) {
            // Ahora mismo esto solo se usa para empresas... pero deberiamos usar un método estatico, o similar
            $parentItems = $items->toObjectList('empresa');
        } else {
            $template->assign("noSelectAll", 'true');
            $template->assign("title", "desc_titulo_seleccion_objetos_papelera");
            $template->assign("elementos", $parentItems);
            $template->assign("inputtype", "radio");
            $template->display("simplelist.tpl");
            exit;
        }
    }
} else {
    if ($elementoSeleccionado instanceof empresa) {

        if (!$usuario->accesoAccionConcreta(1, 5)) {
            die("Inaccesible");
        }

        if ($empresaUsuario->compareTo($elementoSeleccionado)) {
            $template->assign("message", "mensaje_autoeliminarse");
            $template->display("error.tpl");
            exit;
        }

        if (isset($_REQUEST["send"])) {
            //Si vengo de tratar una solicitud hay que tenerlo en cuenta para cambiar el estado de dicha solicitud
            if ($uidSolicitud = @$_REQUEST["request"]) {
                $solicitud = new empresasolicitud($uidSolicitud);
                if ($empresaUsuario->getStartIntList()->contains($solicitud->getCompany()->getUID())) {
                    $status = $solicitud->getState();
                    if ($status === solicitud::ESTADO_CREADA || $status === solicitud::ESTADO_CANCELADA) {
                        $solicitud->setState(solicitud::ESTADO_RECHAZADA);
                        $template->sendFlag("notification-complete", array("id" => $solicitud->getTypeOf().'-'.$solicitud->getUID()));
                    }
                } else {
                    $template->assign("error", "desc_error_papelera_aviso");
                }
            }

            if ($listaUidEmpresasContratacion = get_int_array_from_request("list")) {

                $empresaContratacionSelected = new ArrayIntList($listaUidEmpresasContratacion);

                $listaEmpresaContratacion = $empresaContratacionSelected->toObjectList('empresaContratacion');

                foreach ($listaEmpresaContratacion as $cadenaContratacion) {
                    //Send mail to intermediates companies in order to inform about deleting subcontractor chain
                    $empresasFiltro = new ArrayObjectList;
                    $empresasFiltro[] = $empresaUsuario;
                    $empresasFiltro[] = $cadenaContratacion->getCompanyTail();
                    $remainderCompanies = $cadenaContratacion->filterCompanies($empresasFiltro);
                    foreach ($remainderCompanies as $remainderCompany) {
                        $remainderCompany->deletedSubcontractorNotification($cadenaContratacion, $usuario);
                    }
                    // Deleting residual subcontractor chain
                    if ($cadenasResidual = $cadenaContratacion->getResidualChains()) {
                        foreach ($cadenasResidual as $cadenaResidual) {
                            $cadenaResidual->delete();
                        }
                    }

                    $cadenaContratacion->delete();
                }

                $template->display("succes_form.tpl");
                exit;
            } else {
                $template->assign("info", "mensaje_sin_cambios");
            }

        }

        $cadenasContratacion = $empresaUsuario->obtenerCadenasContratacion($elementoSeleccionado, array(3), array(1), false);
        $cadenasContratacion2 = $empresaUsuario->obtenerCadenasContratacion($elementoSeleccionado, array(4), array(1));
        if (count($cadenasContratacion) && count($cadenasContratacion2)) {
            $cadenasContratacion = $cadenasContratacion->merge($cadenasContratacion2)->unique();
        } else if (!count($cadenasContratacion)) {
            $cadenasContratacion = $cadenasContratacion2;
        }
        $mensajeAlert = false;
        foreach ($cadenasContratacion as $key => $cadenaContratacion) {
            // If we have residual chains we have to show a message
            if ($cadenaContratacion->getResidualChains() && !$mensajeAlert) {
                $template->assign("alert", "mensaje_eliminar_cadenas_residuales");
                $mensajeAlert = true;
            }
        }

        $template->assign("cadenasContratacion", $cadenasContratacion);
        $template->display("papelerasubcontrata.tpl");

        exit;
    }

    $template->assign("error", "desc_error_papelera");
    $template->display("error_string.tpl");
    exit;
}

// ULTIMA COMPROBACION POR SEGURIDAD
if (!count($parentItems)) {
    $template->assign("message", "desc_error_papelera");
    $template->display("error.tpl");
    exit;
}

// Este elemento es el superior para a cuya papelera debemos enviar este elemento
$parent = reset($parentItems);
$deactivable = $elementoSeleccionado->isDeactivable($parent, $usuario);
if ($deactivable === true) {
    if (isset($_REQUEST["send"])) {
        try {
            $elementoSeleccionado->enviarPapelera($parent, $usuario);

            $elementoSeleccionado->writeLogUI(logui::ACTION_DISABLE, 'uid_empresa:'.$parent->getUID(), $usuario);

            // We need to check what documents we dont have to request
            if ($elementoSeleccionado instanceof solicitable) {
                $elementoSeleccionado->actualizarSolicitudDocumentos();
            }

            if ($elementoSeleccionado instanceof empresa) {
                // update requests, may be documents with contract reference
                $parent->actualizarSolicitudDocumentos();
            }


            $template->display("succes_form.tpl");
            exit;
        } catch (Exception $e) {
            $template->assign("error", $e->getMessage());
            $template->display("error_string.tpl");
            exit;
        }
    }

    if ($confirm = $elementoSeleccionado->needsConfirmationBeforeTrash($parent, $usuario)) {
        $template->assign('textoextra', $confirm);
    }
    $template->display("enviarpapelera.tpl");
    exit;
} else {
    if ($elementoSeleccionado instanceof empresa) {
        include("solicitudeliminarcliente.php");
        exit;
    }
    $template->assign("message", $deactivable);
    $template->display("error.tpl");
    exit;
}
