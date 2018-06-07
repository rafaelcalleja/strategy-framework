<?php

require_once("../../../api.php");

$idSeleccionado = obtener_uid_seleccionado();
if (!is_numeric($idSeleccionado)) {
    exit;
}

$log = log::singleton();
$documento = documento::instanceFromAtribute($idSeleccionado, false);
$atributo = new documento_atributo($idSeleccionado);

if (false === $documento instanceof documento) {
    echo "Error al instanciar el documento $idSeleccionado";
    exit;
}

$elementoActual = $atributo->getElement();

$log->info(
    $elementoActual->getModuleName(),
    "descargar documento ".$atributo->getUserVisibleName(),
    $elementoActual->getUserVisibleName()
);

$template = Plantilla::singleton();

if (isset($_REQUEST["action"])) {
    if (isset($_REQUEST["m"]) && isset($_REQUEST["oid"])) {
        $log->resultado("ok", true);

        try {
            $extraData = [
                'context' => 'config',
            ];

            $atributo->downloadFile(false, $elementoActual, $usuario, null, $extraData);
        } catch (HTML2PDF_exception $e) {
            die("<script>alert('La plantilla que estás intentando descargar está mal formada');</script>");
        }
    }
    exit;
}

$template->assign("atributo", $atributo);
$template->assign("documento", $documento);
$template->assign("descarga", true);
$template->assign("usuario", $usuario);
$template->display("descargar.tpl");
