<?php
//$continue = false;
include("../../api.php");

// Instanciar si existe
if ($uid = obtener_uid_seleccionado()) {
    $empleado = new empleado($uid);
} else {
    exit;
}

$rutaFoto = $empleado->getLogo();

if ($rutaFoto === false) {
	$rutaFoto = DIR_ROOT . "res/img/silhouette.png";
}

if (archivo::is_readable($rutaFoto)) {
	header("Content-type: image/png");
    print archivo::leer($rutaFoto);
} else {
    die("Error al cargar la imagen");
}
