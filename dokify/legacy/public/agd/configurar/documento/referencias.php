<?php
require_once __DIR__ . "/../../../api.php";


//----- BUSCAMOS EL ID SELECCIONADO
$idSeleccionado = obtener_uid_seleccionado();
if (!is_numeric($idSeleccionado)) {
    exit;
}

$log = log::singleton();


//
$attr = new documento_atributo($idSeleccionado);

//
$log->info($attr->getModuleName(), "cambiar referencia documento ". $attr->getUserVisibleName(), $attr->getUserVisibleName());

//
$template = Plantilla::singleton();



if (isset($_REQUEST["referenciar_empresa"])) {
    $info = $attr->getInfo();
    $previous = $info['referenciar_empresa'];
    $new = @$_REQUEST['referenciar_empresa'];


    $data = array("referenciar_empresa" => @$_REQUEST['referenciar_empresa']);

    $update = $attr->update($data, elemento::PUBLIFIELDS_MODE_REFERENCIAR, $usuario);

    if ($update) {
        if (($previous == documento_atributo::REF_TYPE_NONE || $previous == documento_atributo::REF_TYPE_COMPANY)) {
            $debug = isset($_REQUEST["debug"]) ? $_REQUEST["debug"] : false;
            $attr->asyncUpdateReferenciaEmpresa((bool) $new, $debug);
        } else {
            $app = \Dokify\Application::getInstance();
            $entity = $attr->asDomainEntity();
            $event = new \Dokify\Application\Event\Requirement\UpdateReference($entity);
            $app->dispatch(\Dokify\Events\RequirementEvents::POST_REQUIREMENT_REFERENCE_UPDATE, $event);
        }

        $template->assign('succes', 'exito_texto');
    }


}




$template->assign("targetModule", $attr->getDestinyModuleName());
$template->assign("referencia", $attr->obtenerDato('referenciar_empresa'));
$template->display("referencias.tpl");
