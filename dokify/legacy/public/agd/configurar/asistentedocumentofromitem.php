<?php

require_once "../../api.php";

if (($uid = obtener_uid_seleccionado()) && ($m=obtener_modulo_seleccionado())) {
    $template = new Plantilla();
    $company = $usuario->getCompany();

    // Definir el item y asignar
    $item = new $m($uid);
    $template->assign("item", $item);

    // Asignar los orígenes
    $origins = new ArrayObjectList;
    $origins[] = $company;
    $asignados = $item->obtenerAgrupadores(null, $usuario, false, false, true);
    $origins = $origins->merge($asignados);
    $template->assign("origins", $origins);

    // Documents
    $documents = config::obtenerArrayDocumentos();
    $template->assign("documents", config::obtenerArrayDocumentos());

    if (isset($_POST['send'])) {
        try {
            $origin = isset($_POST['origin']) ? $_POST['origin'] : false;
            if (!$origin = elemento::factory($origin)) {
                throw new Exception("error_origin", 1);
            }

            $document = isset($_POST['document']) ? $_POST['document'] : false;
            if ($document) {
                $document = new documento($document);
            }

            $data = [
                "nombre_documento" => $document->getUserVisibleName(),
                "documento_obligatorio" => isset($_POST["mandatory"]),
                "documento_duracion" => isset($_POST["duration"]) ? $_POST["duration"] : 0,
                "documento_grace_period" => isset($_POST["grace_period"]) ? $_POST["grace_period"] : 0,
                "documento_codigo" => "",
                "id_solicitante" => [$origin->getUID()],
                "tipo_documento" => $document->getUID(),
                "tipo_solicitante" => $origin->getType(),
                "tipo_receptores" => [$item->getType()],
            ];

            $status = documento_atributo::crearNuevo($data, $usuario);
            if (count($status) && $uid = reset($status)) {
                $item->actualizarSolicitudDocumentos();
                $template->display("succes_form.tpl");
            }

            exit;
        } catch (Exception $e) {
            $template->assign("error", $e->getMessage());
        }
    }


    $template->display("configurar/asistentenuevodocumentofromitem.tpl");
}
