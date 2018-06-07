<?php

include_once( "../../../api.php");

$template = Plantilla::singleton();
$documentoAtributo = new documento_atributo(obtener_uid_seleccionado());
$documento = documento::instanceFromAtribute(obtener_uid_seleccionado());

if (!isset($_REQUEST["contenido"]) || !trim($_REQUEST['contenido'])) {
    die("Error, no hay nada que guardar");
}

$contenido = utf8_decode((stripcslashes($_REQUEST["contenido"])));

// replico el funcionamiento de la subida de archivos para poder reutilizar la funcion anexar
// sin cambios

$filename = archivo::getRandomName($documentoAtributo->getUserVisibleName().'.html', time());
//$path = $ruta = "/tmp/{$filename}";

if (archivo::tmp($filename, $contenido)) {
    $arrayDatosPlantilla = [
        'name'     => $documentoAtributo->getUserVisibleName().'.html',
        'size'     => strlen($contenido),
        'type'     => "html",
        'error'    => '',
        'tmp_name' => $filename,
        'md5_file' => md5_file(archivo::getLocalVersion($filename))
    ];

    try {
        if (($estado = $documento->anexar($arrayDatosPlantilla, true, $documentoAtributo, $usuario)) === true) {
            die('Guardado correctamente');
        } else {
            die('Error al anexar: ' . $estado);
        }
    } catch (Exception $e) {
        die($template($e->getMessage()));
    }
} else {
    die('Error al guardar');
}
