<?php

require __DIR__ . '/../../api.php';

use Dokify\Application\Event\Group\Store as StoreEvent;
use Dokify\Events;

$template = new Plantilla();

if (!$uid = obtener_uid_seleccionado()) {
    exit;
}

$agrupamientoSeleccionado = new agrupamiento($uid);

if (isset($_REQUEST["send"])) {
    $agrupadorNuevo = new agrupador($_REQUEST, $usuario);

    if ($agrupadorNuevo->exists()) {

        $app   = \Dokify\Application::getInstance();
        $event = new StoreEvent($agrupadorNuevo->asDomainEntity());
        $app->dispatch(Events::POST_GROUP_STORE, $event);

        if ($usuario->isViewFilterByGroups()) {
            $usuario->asignarAgrupadores($agrupadorNuevo);
        }

        if (count($etiquetas = $usuario->obtenerEtiquetas())) {
            $agrupadorNuevo->actualizarEtiquetas($etiquetas->toIntList()->getArrayCopy());
        }

        $empresa = $usuario->getCompany();
        if ($corp = $empresa->perteneceCorporacion()) {
            $empresa->asignarAgrupadores($agrupadorNuevo);
        }

        $template->assign("acciones", array(
            array("href" => $_SERVER["PHP_SELF"] ."?poid={$uid}", "string" => "insertar_otro")
        ));
        $template->display("succes_form.tpl");

        exit;
    } else {
        $template->assign("error", $agrupadorNuevo);
    }
}

$template->assign("titulo", "titulo_nuevo_elemento");
$template->assign("boton", "crear");
$template->assign("elemento", $agrupamientoSeleccionado);
$template->assign("campos", agrupador::publicFields(elemento::PUBLIFIELDS_MODE_INIT));
$template->display("form.tpl");
