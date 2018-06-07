<?php

require __DIR__ . '/../../api.php';



try {
    if (!$uid = obtener_uid_seleccionado()) {
        throw new Exception('', 500);
    }

    $lang        = Plantilla::singleton();
    $company    = $usuario->getCompany();
    $employee    = new empleado($uid);
    $file        = archivo::getUploadedFile('file', $usuario->maxUploadSize());

    if ($file->ext != 'pdf') {
        throw new Exception($lang("enviar_alta_pdf"), 500);
    }


    $document    = documento::determine($file->path, $file->name, true);
    $determined = $document instanceof documento && ($document->getCustomId() == tipodocumento::TIPO_DOCUMENTO_ALTASS);

    // check if is a valid document
    if (!$determined) {
        throw new Exception($lang('enviar_alta_original'), 500);
    }

    $nif        = $employee->getId();
    $handler    = new pdfHandler($file->path, false);
    $matchNif    = $handler->getFirstCIF(true, $nif);


    if ($matchNif) {

        if ($employee->asignarEmpresa($company)) {

            // SACAMOS LOS AGRUPAMIENTOS CON REPLICA Y VEMOS QUE ELEMENTOS TIENEN ASIGNADOS PARA ASIGNARSELOS AL EMPLEADO
            if (in_array("empleado", agrupamiento::getModulesReplicables())) {
                $agrupamientos = $company->obtenerAgrupamientosPropios([$usuario]);

                foreach ($agrupamientos as $agrupamiento) {
                    if ($agrupamiento->configValue("replica_empleado")) {
                        $agrupamiento->asignarAgrupamientosAsignadosConReplica($company, $employee, $usuario);
                    }
                }
            }

            $response = ['open' => 'empleado/asignarexistente.php?comefrom=altass&oid=' . $employee->getUID()];

            asyncSend($response);
        } else {
            throw new Exception('Error desconocido!', 500);
        }
    } else {
        throw new Exception(sprintf($lang('alta_no_corresponde'), $employee->getUserVisibleName()), 500);
    }


    exit;
} catch (Exception $e) {
    asyncSend($e->getMessage(), $e);
    exit;
}


// --- something went wrong!
header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
asyncSend('', new Exception('', 500));
