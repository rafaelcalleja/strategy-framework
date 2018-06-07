<?php

require_once("../../../api.php");

$template = new Plantilla();

$currentUIDAtributodocumento = obtener_uid_seleccionado();
//$documento = documento::instanceFromAtribute( $currentUIDAtributodocumento ) or die("No se puede instanciar el documento");

$documentoAtributo = new documento_atributo($currentUIDAtributodocumento);

//UTILIZAMOS LOS VALORES DEL REQUEST
if (isset($_REQUEST["send"])) {
    $assignedElements = [];
    if (isset($_REQUEST["elementos-asignados"])) {
        $assignedElements = $_REQUEST["elementos-asignados"];
    }

    $estado = $documentoAtributo->actualizarFormato($assignedElements);

    if ($estado === true) {
        $template->assign("succes", "exito_titulo");
    } else {
        $template->assign("error", $estado);
    }
}

//no pasar el parametro, es redundante
$formatos = $documentoAtributo->obtenerFormatosDisponibles($currentUIDAtributodocumento);
$formatosAsignados = $documentoAtributo->obtenerFormatosAsignados($currentUIDAtributodocumento);

$formatosDisponibles = elemento::discriminarObjetos($formatos, $formatosAsignados);

$template->assign("disponibles", $formatosDisponibles);
$template->assign("asignados", $formatosAsignados);
$template->display("configurar/asignarsimple.tpl");
