<?php

require __DIR__ . '/../../api.php';

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (true === isset($_REQUEST["reqtypes"]) && $reqtypes = $_REQUEST["reqtypes"]) {
        $_SESSION["AUDIT_REQTYPES"] = implode(',', $reqtypes);
    } else {
        unset($_SESSION["AUDIT_REQTYPES"]);
    }

    $usuario->clearAuditValidationQueue();

    header("Location: /agd/#validation.php?tab=audit");
    exit;
}

$template = Plantilla::singleton();

$partner = $usuario->getCompany();
$allReqtypes = $partner->allReqtypesPendingAudit();

$selectedReqtypes = [];
if (true === isset($_SESSION["AUDIT_REQTYPES"])) {
    $selectedReqtypes = explode(',', $_SESSION["AUDIT_REQTYPES"]);
}

$allReqtypes = $allReqtypes->sort(function ($a, $b) use ($selectedReqtypes) {
    // order by name and selected first
    if (
        true === in_array($a->getUID(), $selectedReqtypes)
        || (
            ($a->getUserVisibleName() < $b->getUserVisibleName())
            && false === in_array($b->getUID(), $selectedReqtypes)
        )
    ) {
        return -1;
    }
    return 1;
});

$reqtypesData = [];
foreach ($allReqtypes as $reqtype) {
    $reqtypesData[] = [
        'uid' => $reqtype->getUID(),
        'name' => $reqtype->getUserVisibleName(),
        'selected' => in_array($reqtype->getUID(), $selectedReqtypes)
    ];
}

$template->assign("reqtypes", $reqtypesData);

$template->display('validation/auditReqtypeFilter.tpl');
