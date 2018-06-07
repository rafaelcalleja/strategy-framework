<?php

require __DIR__ . '/../../api.php';

if (false === isset($_REQUEST["fileId"])) {
    die("Inaccesible");
}

$tab = $_REQUEST["tab"] ?? validation::TYPE_VALIDATION_URGENT;
$fileId = new fileId($_REQUEST["fileId"]);
$requirementType = $fileId->getDocument();
$partner = $usuario->getCompany();
$attachments = $fileId->getAttachments($partner, $tab === validation::TYPE_VALIDATION_OTHERS, null, false, true);
$attachment = $attachments->getFirst();
$requirementRequest = $attachment->getSolicitud();
$requirement = $requirementRequest->obtenerDocumentoAtributo();

$manualExpiring = (bool) $requirement->obtenerDato('caducidad_manual');
$duration = $requirement->obtenerDuraciones();

$template = Plantilla::singleton();
$template->assign("fileId", $fileId);
$template->assign("requirement", $requirement);
$template->assign("manualExpiring", $manualExpiring);
$template->assign("duration", $duration);
$template->display('validation/updateDate.tpl');
