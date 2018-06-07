<?php

require_once("../../api.php");

$template = new Plantilla();

$m = obtener_modulo_seleccionado();
$item = new $m( obtener_uid_seleccionado() );

if (true === isset($_REQUEST["field"])) {
    $idioma = new traductor(obtener_uid_seleccionado(), $item, $_REQUEST["field"]);
} else {
    $idioma = new traductor(obtener_uid_seleccionado(), $item);
}

if (isset($_REQUEST["send"])) {
    $update = $idioma->update();
    switch ($update) {
        case null:
            $template->assign("error", "No se modifico nada");
            break;
        case false:
            $template->assign("error", "Error al intentar modificar");
            break;
        default:
            $template->assign("succes", "exito_titulo");
            break;
    }
}

$template->assign("titulo", "titulo_modificar_idiomas");
$template->assign("boton", "boton_modificar");
$template->assign("title", $item->getUserVisibleName());
$template->assign("campos", $idioma->publicFields());
$template->display("form.tpl");
