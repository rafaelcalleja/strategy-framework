<?php
require_once("../../api.php");


$template = new Plantilla();

$empresa = $usuario->getCompany();

if ($empresa->countOwnDocuments() > 9 && $empresa->needsPay()) {
    $template->display("paypal/docslimit.tpl");
    exit;
}

if (@$_REQUEST["step"] == 2 && count($_REQUEST["tipo_receptores"])==1) {
    $data = [];

    $data["alias"]                      = $_REQUEST["nombre_documento"];
    $data["uid_modulo_destino"]         = elemento::obtenerIdModulo($_REQUEST["tipo_receptores"][0]);
    $data["uid_empresa_propietaria"]    = $empresa->getUID();

    $template->assign("documentosEjemplo", documento_atributo::getPosiblesEjemplos($data));
}

if (@$_REQUEST["step"] == 3) {
    if (isset($_REQUEST["doc_ejemplo"]) && $_REQUEST["doc_ejemplo"]!= 0 && $uidDocEjemplo = $_REQUEST["doc_ejemplo"]) {
        $docEjemplo = new documento_atributo($uidDocEjemplo);

        $template->assign("nombreEjemplo", $docEjemplo->getUserVisibleName(false, false, true));
    }

    $reqTypes = documento_atributo::getRequirementTypes();
    $template->assign("nombreReqTipo", $reqTypes[$_REQUEST["req_type"]]);

    try {
        if (isset($_REQUEST["req_type"]) && $_REQUEST["req_type"] == documento_atributo::TYPE_ONLINE_SIGN) {
            $hasExample = isset($_REQUEST['doc_ejemplo']) && $_REQUEST['doc_ejemplo'];

            if (!$hasExample) {
                $_REQUEST["step"] = 1;
                throw new Exception("req_type_necesita_documento_ejemplo", 1);
            }

            // Vamos a verificar que el documento es una plantilla
            $attr = new documento_atributo($_REQUEST['doc_ejemplo']);
            $info = $attr->downloadFile(true);

            if ($info['ext'] !== 'html') {
                $_REQUEST["step"] = 1;
                throw new Exception("req_type_ejemplo_html", 1);
            }
        }
    } catch (Exception $e) {
        $template->assign("error", $template($e->getMessage()));
    }

}

/** GUARDAR LOS CAMBIOS **/
if (isset($_REQUEST["end"]) && isset($_REQUEST["step"]) && $_REQUEST["step"] == 4) {
    $log = new log();
    //aqui empezamos a comprobar los datos, deberian estar todos.

    $name = @$_REQUEST["nombre_documento"];
    $origen = @$_REQUEST["tipo_solicitante"];


    $log->info("documento_atributo", "nuevo atributo", $name." - ".$origen);



    $status = documento_atributo::crearNuevo($_REQUEST, $usuario);
    if (is_array($status) && count($status)) {
        if (($etiquetas = $usuario->obtenerEtiquetas()) && count($etiquetas)) {
            foreach ($status as $uid) {
                $attr = new documento_atributo($uid);
                $attr->actualizarEtiquetas($etiquetas->toIntList()->getArrayCopy());
            }
        }

        $log->resultado("ok", true);

        $template->display("succes_form.tpl");
        exit;
    } else {
        if (!($status)) {
            $status = "error_desconocido";
        }

        $log->nivel(4);
        $log->resultado("error $status", true);
        $template->assign("error", $status);
    }
}

//------ PRIMERO BUSCAMOS LOS SOLICITANTES GLOBALES, YA QUE SIEMPRE NOS HACEN FALTA
$solicitantesGlobales = config::obtenerAgrupamientosGlobales(false, $usuario, array(12));
//dump($solicitantesGlobales);exit;

//------ SI....
if (isset($_REQUEST["tipo_solicitante"]) && !empty($_REQUEST["tipo_solicitante"])) {
    foreach ($solicitantesGlobales as $solicitante) {
        if ($solicitante->getUserVisibleName() == $_REQUEST["tipo_solicitante"]) {
            $objetoSolicitante = $solicitante;
            break;
        };
    }

    $modulo = strtolower($objetoSolicitante->getModuleName());
    //SI EL SOLICITANTE ES UNA AGRUPAMIENTO
    if ($objetoSolicitante instanceof agrupamiento) {
        $elementos = $objetoSolicitante->obtenerAgrupadores($usuario);
    } else {
        //SI NO, SIGNIFICA EMPRESA
        $objetoSolicitante = $usuario->getCompany();
        $elementos = $objetoSolicitante->obtenerEmpresasInferioresMasActual(false, false, $usuario, 1);
    }

    $template->assign("parent", $objetoSolicitante);
    $template->assign("elementos", $elementos);

    //----- SI VENIMOS DEL PASO 3 Y SE HAN DADO LOS SOLICITANTES...
    if ((isset($_REQUEST["step"])&&$_REQUEST["step"]==3)) {
        if (isset($_REQUEST["agrupamiento"])) {
            $elementosSeleccionados = array($objetoSolicitante);
        } elseif (isset($_REQUEST["id_solicitante"])) {
            $elementosSeleccionados = array();
            foreach ($elementos as $elemento) {
                if (in_array($elemento->getUID(), $_REQUEST["id_solicitante"])) {
                    $elementosSeleccionados[] = $elemento;
                }
            }
        }

        if (isset($elementosSeleccionados) && count($elementosSeleccionados)) {
            $template->assign("elementosSeleccionados", $elementosSeleccionados);
        }
    }

}

if (isset($_REQUEST["poid"]) && isset($_REQUEST["m"])) {
    $m = obtener_modulo_seleccionado();
    $elemento = new $m( obtener_uid_seleccionado() );
    $template->assign("elemento", $elemento);
}

$onlyPublic = true;

if ($usuario->esStaff()) {
    $onlyPublic = false;
}

$documentos = config::obtenerArrayDocumentos(false, false, $onlyPublic);

$template->assign("solicitantes", $solicitantesGlobales);
$template->assign("solicitados", solicitable::getModules());
$template->assign("documentos", $documentos);
$template->assign("tiposRequisitos", documento_atributo::getRequirementTypes());
$template->display("configurar/asistentenuevodocumento.tpl");
