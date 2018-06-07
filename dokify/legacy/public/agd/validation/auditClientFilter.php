<?php

require __DIR__ . '/../../api.php';

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (true === isset($_REQUEST["clients"]) && $clients = $_REQUEST["clients"]) {
        $_SESSION["AUDIT_CLIENTS"] = implode(',', $clients);
    } else {
        unset($_SESSION["AUDIT_CLIENTS"]);
    }

    $usuario->clearAuditValidationQueue();

    header("Location: /agd/#validation.php?tab=audit");
    exit;
}

$template = Plantilla::singleton();

$partner= $usuario->getCompany();
$allClients = $partner->allClientsPendingsAudit();

$selectedClients = [];
if (true === isset($_SESSION["AUDIT_CLIENTS"])) {
    $selectedClients = explode(',', $_SESSION["AUDIT_CLIENTS"]);
}

$allClients = $allClients->sort(function ($a, $b) use ($selectedClients) {
    // order by name and selected first
    if (
        true === in_array($a->getUID(), $selectedClients)
        || (
            ($a->getUserVisibleName() < $b->getUserVisibleName())
            && false === in_array($b->getUID(), $selectedClients)
        )
    ) {
        return -1;
    }
    return 1;
});

$clientsData = [];
foreach ($allClients as $client) {
    $clientsData[] = [
        'uid' => $client->getUID(),
        'name' => $client->getUserVisibleName(),
        'selected' => in_array($client->getUID(), $selectedClients)
    ];
}

$template->assign("clients", $clientsData);

$template->display('validation/auditClientFilter.tpl');
