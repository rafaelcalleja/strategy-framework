<?php

include("../../api.php");

if (!$oid = @$_REQUEST["oid"]) {
    exit;
}

$machine = new maquina($oid);
$uidEmpresa = ( is_numeric($_GET["poid"]) ) ? obtener_uid_seleccionado() : $usuario->getCompany()->getUID();

$template = new Plantilla();
$template->assign("poid", $uidEmpresa);
$template->assign("modulo", "maquina");
$template->assign("machine", $machine);
$template->display("elemento_existente.tpl");
