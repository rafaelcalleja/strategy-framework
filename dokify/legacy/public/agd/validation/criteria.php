<?php

require __DIR__ . '/../../api.php';

$reqtype = new documento(obtener_uid_seleccionado());
$company = new empresa(obtener_comefrom_seleccionado());

$template = Plantilla::singleton();
$template->assign("criteria", $reqtype->getCriteria($company));
$template->display('validation/criteria.tpl');
