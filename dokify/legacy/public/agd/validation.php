<?php

require __DIR__ . '/../api.php';

db::singleton()->doctrineConnection()->connect('master');

$template = Plantilla::singleton();

$json = new jsonAGD();
$json->iface("validation");
$json->loadStyle(RESOURCES_DOMAIN . "/css/validation/style.css?". VKEY);

$tab = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : validation::TYPE_VALIDATION_URGENT;
if (!$usuario->esValidador() && !$usuario->esStaff()) {
    die('Innacesible');
}
$partner = $usuario->getCompany();
$force = false;
if (($tab == validation::TYPE_VALIDATION_STATS || $tab ==  validation::TYPE_VALIDATION_REVIEW) && $usuario->configValue("economicos") == 0) {
    header("Location: " .$_SERVER["PHP_SELF"]);
    exit;
}

$numUrgents = $partner->getDocumentsPendingValidation($usuario, true, false, true);
$numNormals = $partner->getDocumentsPendingValidation($usuario, false, false, true);
$numOthers = $partner->getDocumentsPendingValidation($usuario, false, false, true, true);
$numAudit = $partner->getDocumentsOfValidationsPendingAuditCount();

if ($tab === validation::TYPE_VALIDATION_NORMAL && $numUrgents) {
    print json_encode(array("action" => array("go" => "#validation.php"), "iface" => "validation"));
    exit;
}

$withAnyStatus = false;
$onlyWithRequests = true;

if (isset($_REQUEST["fileId"]) && $fileId = $_REQUEST["fileId"]) {
    $withAnyStatus = true;
    $json->addData("hideSelector", "#top-bar");
    $template->assign("force", $force);

    if ($module = fileId::getModuleOfFileId($fileId)) {
        $fileId = new fileId($fileId, $module);
        $force = true;

    } else {
        $template->assign('error', 'El documento solicitado no existe');
        $json->nuevoSelector("#main", '<div id="validation-content">' . $template->getHTML('error_string.tpl') . '</div>');
        $json->menuSeleccionado("validacion");
        $json->display();

        exit;
    }

} else {
    switch ($tab) {
        case validation::TYPE_VALIDATION_OTHERS:
            $fileIds = $partner->getDocumentsPendingValidation($usuario, false, 1, false, true);
            break;
        case validation::TYPE_VALIDATION_NORMAL:
            $clients = false;
            if (isset($_SESSION["CLIENTS"])) {
                $clients = explode(',', $_SESSION["CLIENTS"]);
            }
            $template->assign("filterClients", (bool)$clients);

            $reqtypeFilter = false;
            if (true === isset($_SESSION["REQTYPES"])) {
                $reqtypeFilter = explode(',', $_SESSION["REQTYPES"]);
            }
            $template->assign("filterReqtypes", (bool)$reqtypeFilter);

            $fileIds = $partner->getDocumentsPendingValidation($usuario, false, 1, false, false, false, false, $clients, $reqtypeFilter);
            break;
        case validation::TYPE_VALIDATION_AUDIT:
            $onlyWithRequests = false;
            $clientFilter = false;
            if (true === isset($_SESSION["AUDIT_CLIENTS"])) {
                $clientFilter = explode(',', $_SESSION["AUDIT_CLIENTS"]);
            }
            $template->assign("filterClients", (bool) $clientFilter);

            $reqtypeFilter = false;
            if (true === isset($_SESSION["AUDIT_REQTYPES"])) {
                $reqtypeFilter = explode(',', $_SESSION["AUDIT_REQTYPES"]);
            }
            $template->assign("filterReqtypes", (bool) $reqtypeFilter);

            $validation = $partner->getFirstValidationPendingAudit($usuario, $clientFilter, $reqtypeFilter);

            if (false === $validation) {
                $fileIds = new ArrayObjectList([]);
            } else {
                $template->assign("validation", $validation);
                $fileId = $validation->getFileId();
                $fileIds = new ArrayObjectList([$fileId]);
            }

            $withAnyStatus = true;
            break;
        case validation::TYPE_VALIDATION_URGENT:
            $fileIds = $partner->getDocumentsPendingValidation($usuario, true, 1, false);
            break;
        case validation::TYPE_VALIDATION_REVIEW:
            $fileIds = $partner->getDocumentsPendingValidation($usuario, false, 1, false, false, true);
            break;
        case validation::TYPE_VALIDATION_STATS:
            $todayValidationsUser = $monthValidationsUser = $lastMothValidationsUsers = array();
            $todayValidationsCompanies = $lastMothValidationsCompanies = $actualMothValidationsCompanies = array();
            $tpl = Plantilla::singleton();

            $validators = $partner->obtenerValidadores();
            $dayMonth = date("t");
            $lastMonth = date("m") -1;

            $today = new DateTime(date("Y-m-d"));
            $today = $today->getTimestamp();

            $firstDayActualMonth = new DateTime(date('Y-m-01'));
            $firstDayActualMonth = $firstDayActualMonth->getTimestamp();

            $lastDayActualMonth = new DateTime(date("Y-m-".$dayMonth." 23:59:59"));
            $lastDayActualMonth = $lastDayActualMonth->getTimestamp();

            $firstDayLastMonth = new DateTime(date("Y-". $lastMonth ."-01"));
            $lastDayLastMonth = new DateTime(date("Y-". $lastMonth ."-".date("t", $firstDayLastMonth->getTimestamp())." 23:59:59"));
            $firstDayLastMonth = $firstDayLastMonth->getTimestamp();
            $lastDayLastMonth = $lastDayLastMonth->getTimestamp();

            foreach ($validators as $validator) {
                $todayValidationsUser[] =  $validator->getValidations($partner, $today, null)->getResume();
                $monthValidationsUser[] = $validator->getValidations($partner, $firstDayActualMonth, $lastDayActualMonth)->getResume();
                $lastMothValidationsUsers[] = $validator->getValidations($partner, $firstDayLastMonth, $lastDayLastMonth)->getResume();
            }

            $amount = 0;

            $ownersCompaniesToday = $partner->getCompaniesValidatedByPartner($today, null);
            foreach ($ownersCompaniesToday as $company) {
                $todayValidationsCompanies[] = $company->getValidations($partner, $today, null)->getResume();
            }

            $ownersCompaniesActualMonth = $partner->getCompaniesValidatedByPartner($firstDayActualMonth, $lastDayActualMonth);
            foreach ($ownersCompaniesActualMonth as $company) {
                $actualMothValidationsCompanies[] = $company->getValidations($partner, $firstDayActualMonth, $lastDayActualMonth)->getResume();
            }

            $ownersCompaniesLastMonth = $partner->getCompaniesValidatedByPartner($firstDayLastMonth, $lastDayLastMonth);
            foreach ($ownersCompaniesLastMonth as $company) {
                $lastMothValidationsCompanies[] = $company->getValidations($partner, $firstDayLastMonth, $lastDayLastMonth)->getResume();
            }

            $statsValidation[] = array("items" => $todayValidationsUser, "title" =>"Usuarios validadores hoy", "type" => "usuario");
            $statsValidation[] = array("items" => $todayValidationsCompanies, "title" =>"Validaciones hoy", "type" => "empresa");
            $statsValidation[] = array("items" => $monthValidationsUser, "title" =>"Usuarios validadores este mes", "type" => "usuario");
            $statsValidation[] = array("items" => $actualMothValidationsCompanies, "title" =>"Validaciones mensual", "type" => "empresa");
            $statsValidation[] = array("items" => $lastMothValidationsUsers, "title" =>"Usuarios validadores mes pasado", "type" => "usuario");
            $statsValidation[] = array("items" => $lastMothValidationsCompanies, "title" =>"Validaciones del mes pasado", "type" => "empresa");
            $template->assign("statsValidation", $statsValidation);

            break;
    }

    if (isset($fileIds)
        && 0 === count($fileIds)
        && false === isset($_REQUEST["force"])
        && false === in_array($tab, [
            validation::TYPE_VALIDATION_NORMAL,
            validation::TYPE_VALIDATION_STATS,
            validation::TYPE_VALIDATION_REVIEW,
            validation::TYPE_VALIDATION_AUDIT,
        ])
    ) {
        print json_encode(array("action" => array("go" => "#validation.php?tab=normal"), "iface" => "validation"));
        exit;
    }

    if (isset($fileIds)) {
        $fileId = reset($fileIds);
    }
}

if (isset($fileId) && $fileId instanceof fileId) {
    // --- si no estamos buscando un anexo determinado...
    if (false === $force) {
        if (true === $fileId->isAssignedToOther($usuario)) {
            print json_encode(array("refresh" => 1, "iface" => "validation"));
            exit;
        }

        if (validation::TYPE_VALIDATION_AUDIT === $tab && true === $validation->isAssignedToAuditOther($usuario)) {
            print json_encode(array("refresh" => 1, "iface" => "validation"));
            exit;
        }

        if (false === in_array($tab, [validation::TYPE_VALIDATION_REVIEW, validation::TYPE_VALIDATION_AUDIT])) {
            $fileId->assignToUser($usuario);
        }

        if (validation::TYPE_VALIDATION_AUDIT === $tab) {
            $validation->assignToAuditUser($usuario);
        }
    }

    $anexo = $fileId->getAnexo();
    $anexos = $fileId->getAttachmentsGroupingByCompany($partner, $tab==validation::TYPE_VALIDATION_OTHERS, null, $withAnyStatus, $onlyWithRequests);

    $template->assign("anexo", $anexo);
    $template->assign("anexos", $anexos);

    $elemento = $anexo->getElement();
    $solicitudes = $fileId->obtenerSolicitudDocumentos();
    $reqType = new requirementTypeRequest($solicitudes, $elemento);

    $comments = $reqType->getComments($usuario, false, false);
    if ($comments) {
        $template->assign("comments", $comments);
    }

    $commentsRemaining = $reqType->getComments($usuario, false, false, 1);
    $template->assign("commentsRemaining", $commentsRemaining);


    $template->assign("offset", 1);
    $template->assign("fileId", $fileId);

    $_SESSION["CURRENT_FILEID"] = $fileId->getUID();
}

$today = new DateTime(date("Y-m-d"));
$today = $today->getTimestamp();
$userSummary = $usuario->getValidations($partner, $today, null)->getResume();
$counter = (isset($userSummary) && isset($userSummary["count"])) ? $userSummary["count"] : 0;

$superValidator = ($usuario->configValue("economicos") == 1) ? true : false;
$template->assign("superValidator", $superValidator);
$template->assign("counter", $counter);
$template->assign("tab", $tab);
$template->assign("company", $partner);

if ($force == false) {
    $template->assign("validators", $partner->obtenerValidadores($usuario));
}

$template->assign("pendingUrgent", $numUrgents);
$template->assign("pendingNormal", $numNormals);
$template->assign("pendingOthers", $numOthers);
$template->assign("pendingAudit", $numAudit);
$template->assign("commonArguments", ValidationArgument::getAll());

$json->nuevoSelector("#main", $template->getHTML("validation/iface.tpl"));
$json->menuSeleccionado("validacion");
$json->display();
