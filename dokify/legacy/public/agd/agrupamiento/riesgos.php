<?php
include_once( "../../api.php");

$template = new Plantilla();
$agrupadorSeleccionado = new agrupador(obtener_uid_seleccionado());

if (!$agrupadorSeleccionado->accesiblePara($usuario)) {
    die("ERROR ACCESO");
}

$filepath = DIR_RIESGOS . "empresa_". $usuario->getCompany()->getUID() . "/agrupador_". $agrupadorSeleccionado->getUID();

if (isset($_REQUEST["send"])) {
    $contenido = stripslashes($_REQUEST["contenido"]);
    if (archivo::escribir($filepath, $contenido, true)) {
        echo "Se guardó la información correctamente";
    } else {
        echo "Fallo al guardar";
    }
    exit;
}

if (archivo::is_readable($filepath)) {
    $html = archivo::leer($filepath);
    $template->assign("html", $html);
}

$json = new jsonAGD();
$json->establecerTipo("simple");
$json->informacionNavegacion(
    "inicio",
    $template->getString("agrupamientos"),
    $agrupadorSeleccionado->getUserVisibleName(),
    $template->getString("riesgos")
);
$json->nombreTabla("agrupador-evaluacion-{$agrupadorSeleccionado->getUID()}");
$json->nuevoSelector("#main", $template->getHTML("plantillahtml.tpl"));
$json->loadScript(RESOURCES_DOMAIN . "/js/tiny_mce/jquery.tinymce.js");
$json->display();
